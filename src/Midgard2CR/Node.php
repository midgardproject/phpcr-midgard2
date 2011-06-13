<?php
namespace Midgard2CR;

class Node extends Item implements \IteratorAggregate, \PHPCR\NodeInterface
{
    protected $children = null;
    protected $properties = null;
    protected $propertyManager = null;

    public function addNode($relPath, $primaryNodeTypeName = NULL)
    {
        $parent_node = $this;
        $object_name = $relPath;

        // ItemExistsException
        // Node at given path exists.
        try 
        {
            $parent_node = $this->getNode ($relPath);
            throw new \PHPCR\ItemExistsException("Node at given path {$relPath} exists");
        } 
        catch (\PHPCR\PathNotFoundException $e) 
        {
            // Do nothing 
        }
        
        // PathNotFoundException
        // At least one (not last) node at given path doesn't exist
        if (strpos($relPath, '/') !== false)
        {
            $parts = explode ('/', $relPath);
            $object_name = end($parts);
            $parent_path = array_pop ($parts);
            $parent_node = $this->getNode ($parent_path);
        }

        // ConstraintViolationException
        // Underying Midgard2 object has no tree support and path contains at least two elements
        // TODO, Determine Midgard2 type from given NodeTypeName
        // FIXME, Get unique property once midgard_reflector_object::get_property_unique is added to PHP extension

        // LockException
        // TODO

        // VersionException 
        // TODO

        $mobject = \midgard_object_class::factory (get_class($this->getMidgard2Object()));
        $mobject->name = $object_name;
        $new_node = new Node($mobject, $parent_node, $parent_node->getSession());
        $new_new->is_new = true; 
        $parent_node->children[$object_name] = $new_node;
        
        // FIXME, Catch exception before returning new node
        return $new_node;

        // RepositoryException
        // Unspecified yet.
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getPropertyManager()
    {
        $this->populateProperties();
        return $this->propertyManager;
    }

    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function setProperty($name, $value, $type = NULL)
    {
        try 
        {
            $property = $this->getProperty($name);
        } 
        catch (\PHPCR\PathNotFoundException $e)
        { 
            $property = new Property ($this, $name, $this->propertyManager);
            $this->properties[$name] = $property;
        }
        $property->setValue ($value, $type);
        return $property;
    }

    private function getMgdSchemas()
    {
        $mgdschemas = array();
        $re = new \ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        foreach ($classes as $refclass)
        {
            $parent_class = $refclass->getParentClass();
            if (!$parent_class)
            {
                continue;
            }

            if ($parent_class->getName() != 'midgard_object')
            {
                continue;
            }
            $mgdschemas[] = $refclass->getName();
        }
        return $mgdschemas;
    }

    private function getChildTypes()
    {
        $mgdschemas = $this->getMgdSchemas();
        $child_types = array();
        foreach ($mgdschemas as $mgdschema)
        {
            if ($mgdschema == 'midgard_parameter')
            {
                continue;
            }

            $link_properties = array
            (
                'parent' => \midgard_object_class::get_property_parent($mgdschema),
                'up' => \midgard_object_class::get_property_up($mgdschema),
            );

            $ref = new \midgard_reflection_property($mgdschema);
            foreach ($link_properties as $type => $property)
            {
                $link_class = $ref->get_link_name($property);
                if (   empty($link_class)
                    && $ref->get_midgard_type($property) === MGD_TYPE_GUID)
                {
                    $child_types[] = $mgdschema;
                    continue;
                }

                if ($link_class == get_class($this->object))
                {
                    $child_types[] = $mgdschema;
                }
            }
        }
        return $child_types;
    }

    private function populateChildren()
    {
        if (!is_null($this->children))
        {
            return;
        }

        //\midgard_connection::get_instance()->set_loglevel("debug");

        $this->children = array();
        $childTypes = $this->getChildTypes();
        foreach ($childTypes as $childType)
        {
            if ($childType == get_class($this->object))
            {
                $children = $this->object->list();
            }
            else
            {
                $children = $this->object->list_children($childType);
            }
            foreach ($children as $child)
            {
                $this->children[$child->name] = new Node($child, $this, $this->getSession());
            }
        }

        /* Add attachments */
        $attachments = $this->object->list_attachments();
        foreach ($attachments as $child)
        {
            $this->children[$child->name] = new Node($child, $this, $this->getSession());
        }
    }

    public function getNode($relPath)
    {
        $remainingPath = '';
        $pos = strpos($relPath, '/');
        /* Convert to relative path when absolute one has been given */
        if ($pos === 0)
        {
            $relPath = substr($relPath, 1);
        }
        else if ($pos !== false)
        {
            $parts = explode('/', $relPath);
            $relPath = array_shift($parts);
            $remainingPath = implode('/', $parts);
        }

        if (!isset($this->children[$relPath]))
        {
            $this->populateChildren();
            if (!isset($this->children[$relPath]))
            {
                /* Try special case: '..' */
                if ($relPath == '..')
                {
                    if ($remainingPath)
                    {
                        return $this->parent->getNode($remainingPath);
                    } 
                    return $this->parent;
                }
                throw new \PHPCR\PathNotFoundException("Node at path '{$relPath}' not found. {$remainingPath}");
            }
        }

        if ($remainingPath)
        {
            return $this->children[$relPath]->getNode($remainingPath);
        }
        return $this->children[$relPath];        
    }

    private function getItemsSimilar($items, $name)
    {
        $ret = array();

        $nsregistry = $this->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();

        $prefix = $nsmanager->getPrefix($name);
        if ($prefix == null)
        {
            return $ret;
        }

        foreach ($items as $n => $o)
        {
            $node_prefix = $nsmanager->getPrefix($o->getName());
            if ($node_prefix == $prefix)
            {
                $ret[] = $o;
            }
        }

        return $ret;
    }

    private function getItemsEqual($items, $name)
    {
        $ret = array();

        if (array_key_exists($name, $this->children))
        {
            $ret[] = $items[$name];
        }
            
        return $ret;
    }

    private function getFiltersFromString($filter)
    {
        $filters = array();
        $parts = explode('|', $filter);
        if (!isset($parts[1]))
        {
            $filters[] = $filter;
        }
        else 
        {
            foreach($parts as $p)
            {
                $filters[] = trim($p);
            }
        }
        return $filters;
    }

    private function getFiltersFromArray($filter)
    {
        $allFilters = array();
        foreach ($filter as $p)
        {
            $allFilters = array_merge($allFilters, $this->getFiltersFromString($p));
        }
        return $allFilters;
    }

    private function getItemsFiltered($items, $filter)
    {
        /* TODO wildcards '*filter*' */

        $filteredItems = array();

        if (is_string($filter))
        {
            $filters = $this->getFiltersFromString($filter);
        }

        if(is_array($filter))
        {
            $filters = $this->getFiltersFromArray($filter);
        }

        foreach ($filters as $f)
        {
            if (strpos($f, '*') !== false)
            {
                $filteredItems = array_merge($filteredItems, $this->getItemsSimilar($items, $f));
            }
            else 
            {
                $filteredItems = array_merge($filteredItems, $this->getItemsEqual($items, $f));
            }
        }
        return new \ArrayIterator($filteredItems);   
    }

    public function getNodes($filter = null)
    {
        $this->populateChildren();

        if ($filter == null) 
        {
            return new \ArrayIterator($this->children);
        }

        return $this->getItemsFiltered($this->children, $filter); 
    }

    private function populateProperties()
    {
        if (!is_null($this->properties))
        {
            return;
        }

        foreach ($this->object as $property => $value)
        {
            $this->properties["mgd:{$property}"] = new Property($this, "mgd:{$property}", null);
        }

        $this->propertyManager = new \Midgard2CR\PropertyManager($this->object);
        foreach ($this->propertyManager->listModels() as $name => $model)
        {
            if ($model->prefix == 'phpcr:undefined')
            {
                $this->properties[$model->name] = new Property($this, $model->name, $this->propertyManager);
                continue;
            }
            $this->properties[$name] = new Property($this, $name, $this->propertyManager);
        }
    }

    public function getProperty($relPath)
    {
        $remainingPath = '';
        if (strpos($relPath, '/') !== false)
        {
            $parts = explode('/', $relPath);
            $property_name = array_pop($parts);
            $remainingPath = implode('/', $parts); 
            return $this->getNode($remainingPath)->getProperty($property_name);
        }

        if (!isset($this->properties[$relPath]))
        {
            $this->populateProperties();
            if (!isset($this->properties[$relPath]))
            {
                throw new \PHPCR\PathNotFoundException("Property at path '{$relPath}' not found");
            }
        }

        return $this->properties[$relPath];
    }
    
    public function getPropertyValue($name, $type=null)
    {
        return $this->getProperty($name)->getNativeValue();
    }
    
    public function getProperties($filter = NULL)
    {
        $this->populateProperties();

        if ($filter == null) 
        {
            return new \ArrayIterator($this->properties);
        }

        return $this->getItemsFiltered($this->properties, $filter); 
    }

    public function getPropertiesValues($filter=null)
    {
    }   
    
    public function getPrimaryItem()
    {
        try
        {
            $primaryType = $this->getPropertyValue('jcr:primaryType');
            $ntm = $this->session->getWorkspace()->getNodeTypeManager();
            $nt = $ntm->getNodeType($primaryType);
            $primaryItem = $nt->getPrimaryItemName();
            if ($primaryItem == null)
            {
                throw new \PHPCR\ItemNotFoundException("PrimaryItem not found for {$this->getName()} node");
            }
        }
        catch (\PHPCR\PathNotFoundException $e)
        {
                throw new \PHPCR\ItemNotFoundException("primaryType property not found for {$this->getName()} node");
        }
        return $primaryItem;
    }
    
    public function getIdentifier()
    {
        $this->populateProperties();

        /* Try uuid first */
        $uuid = $this->propertyManager->getProperty("uuid", "jcr");
        if ($uuid != null) 
        {
            $values = $uuid->getLiterals();
            return $values[0];
        }

        /* Return guid if uuid is not found */
        return $this->object->guid;
    }
    
    public function getIndex()
    {
        /* We do not support same name siblings */
        return 1;
    }
    
    public function getReferences($name = NULL)
    {
        /* TODO:
         * If node has jcr:uuid property
         *  get it's value
         *  query properties with such value, which are declared as reference property model
         *  query references
         * If not, return empty iterator */
        return new \ArrayIterator(array());
    }
    
    public function getWeakReferences($name = NULL)
    {
        /* TODO:
         * Check getReferences comments */
        return  new \ArrayIterator(array());
    }
    
    public function hasNode($relPath)
    {
        $pos = strpos($relPath, '/');
        if ($pos === 0)
        {
            throw new \InvalidArgumentException("Expected relative path. Absolute given");
            /* Take few glasses if Absolute given ;) */
        }

        try 
        {
            $this->getNode($relPath);
            return true;
        }
        catch (\PHPCR\PathNotFoundException $e) 
        {
            return false;
        }
    }
    
    public function hasProperty($relPath)
    {
        $pos = strpos($relPath, '/');
        if ($pos === 0)
        {
            throw new \InvalidArgumentException("Expected relative path. Absolute given");
        }

        try {
            $this->getProperty($relPath);
            return true;
        }
        catch (\PHPCR\PathNotFoundException $e) 
        {
            return false;
        }
    }
    
    public function hasNodes()
    {
        $this->populateChildren();
        if (empty($this->children))
        {
            return false;
        }

        return true;
    }
    
    public function hasProperties()
    {
        $this->populateProperties();
        if (empty($this->properties))
        {
            return false;
        }

        return true;
    }
    
    public function getPrimaryNodeType()
    {
    }
    
    public function getMixinNodeTypes()
    {
        return array();
    }
    
    public function isNodeType($nodeTypeName)
    {
        return false;
    }
    
    public function setPrimaryType($nodeTypeName)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function addMixin($mixinName)
    {
    }
    
    public function removeMixin($mixinName)
    {
    }
    
    public function canAddMixin($mixinName)
    {
        return false;
    }
    
    public function getDefinition()
    {
        return null;
    }
    
    public function update($srcWorkspace)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getCorrespondingNodePath($workspaceName)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getSharedSet()
    {
        return new \ArrayIterator(array($this));
    }
    
    public function removeSharedSet()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function removeShare()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function isCheckedOut()
    {
        return false;
    }

    public function isLocked()
    {
        return false;
    }

    public function followLifecycleTransition($transition)
    {
        throw new \UnsupportedRepositoryOperationException();
    }

    public function getAllowedLifecycleTransitions()
    {
        throw new \UnsupportedRepositoryOperationException();
    }

    public function getIterator()
    {
        return $this->getNodes();
    }

    public function isSame(\PHPCR\ItemInterface $item)
    {
        if (!$item instanceof \PHPCR\NodeInterface)
        {
            return false;
        }

        /* TODO */
        /* Check session */

        if ($this->object->guid == $item->object->guid)
        {
            return true;
        }

        return false;
    }

    private function getMidgardRelativePath($object)
    {
        $storage = new \midgard_query_storage('midgardmvc_core_node');

        /* By default we prepare to join core node and blob.
         * SELECT t1.id, ... FROM midgardmvc_core_node AS t1 JOIN blobs AS t2 ON t1.guid = t2.parent_guid WHERE t2.name='NAME';
         */ 
        $joined_storage = new \midgard_query_storage('midgard_attachment');
        $left_property_join = new \midgard_query_property('guid');
        $right_property_join = new \midgard_query_property('parentguid', $joined_storage);    

        if (is_a($object, 'midgardmvc_core_node'))
        {
            /* Join nodes 
             * SELECT t1.id, ... FROM midgardmvc_core_node AS t1 JOIN midgardmvc_core_node AS t2 ON t1.id = t2.up WHERE t2.name='NAME'
             */ 
            $joined_storage = new \midgard_query_storage('midgardmvc_core_node');
            $left_property_join = new \midgard_query_property('id');
            $right_property_join = new \midgard_query_property('up', $joined_storage);    
        }

        $q = new \midgard_query_select($storage);
        $q->add_join("INNER", $left_property_join, $right_property_join);

        /* Set name constraint */
        $q->set_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('name', $joined_storage), 
                '=', 
                new \midgard_query_value($object->name)
            )
        );
        
        $q->execute();

        /* Relative path is : $returnedobjects[0]->name / $object->name */

        return $q->list_objects();
    }

    public static function getMidgardPath($object)
    {
        $elements = array();

        /* We expect last object to have up property = 0.
         * Last object is root node. */
        do 
        {
            array_unshift($elements, $object->name);
            $objects = self::getMidgardRelativePath($object);
            if (empty($objects))
            {
                break;
            }
            $object = $objects[0];

        } while (!empty($objects) && $object->up != 0);

        return '/' . implode("/", $elements);
    }
}
