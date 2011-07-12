<?php
namespace Midgard2CR;

use ArrayIterator;

class Node extends Item implements \IteratorAggregate, \PHPCR\NodeInterface
{
    protected $children = null;
    protected $properties = null;
    protected $propertyManager = null;
    protected $primaryNodeTypeName = null;
    protected $remove = false;

    public function __construct(\midgard_tree_node $midgardNode = null, Node $parent = null, Session $session)
    {
          $this->parent = $parent;
          $this->midgardNode = $midgardNode;
          $this->session = $session;
    }

    /* TODO, move this to ContentObjectFactory */
    private function contentObjectFactory(\midgard_tree_node $midgardNode,  $primaryNodeTypeName = null)
    {
        $guid = $midgardNode->objectguid;
        /* FIXME, set proper type name */
        if ($midgardNode->typename == null
            || $midgardNode->typename == '')
        {
            $midgardNode->typename = 'nt_folder';
        }
        $this->contentObject = \midgard_object_class::factory($midgardNode->typename, $guid ? $guid : null);
        if ($primaryNodeTypeName != null)
        {
            $this->setProperty('jcr:primaryType', $primaryNodeTypeName, \PHPCR\PropertyType::NAME);
        }

        if ($this->hasProperty('jcr:created'))
        {
            $this->setProperty('jcr:created',  new \DateTime('now'), \PHPCR\PropertyType::DATE);
        }
    }

    private function appendNode($relPath, $primaryNodeTypeName = null)
    {
        /* ItemExistsException, Node at given path exists.*/
        try 
        {
            $node_exists = $this->getNode ($relPath);
            throw new \PHPCR\ItemExistsException("Node at given path {$relPath} exists");
        } 
        catch (\PHPCR\PathNotFoundException $e) 
        {
            // Do nothing 
        }

        // ConstraintViolationException
        // Underying Midgard2 object has no tree support and path contains at least two elements
        // TODO, Determine Midgard2 type from given NodeTypeName
        // FIXME, Get unique property once midgard_reflector_object::get_property_unique is added to PHP extension

        // LockException
        // TODO

        // VersionException 
        // TODO

        //$typename = get_class($this->getMidgard2ContentObject());
        /* TODO, factory is probably needed.
         * Get namespace and prefix from namespace manager */
        /*if ($primaryNodeTypeName == 'nt:file')
        {
            $mobject = new \midgard_attachment();
            $mobject->mimetype = 'nt:file';
        }
        else 
        {
            $mobject = \midgard_object_class::factory ($typename);
        }
        $mobject->name = $object_name;*/
        $midgardNode = new \midgard_tree_node();
        $midgardNode->typename = str_replace(':', '_', $primaryNodeTypeName);
        $midgardNode->name = $relPath;

        $new_node = new \Midgard2CR\Node($midgardNode, $this, $this->getSession());
        $new_node->is_new = true; 
        $new_node->primaryNodeTypeName = $primaryNodeTypeName;
        $this->children[$relPath] = $new_node;

        // FIXME, Catch exception before returning new node
        return $new_node;

        // RepositoryException
        // Unspecified yet.
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function addNode($relPath, $primaryNodeTypeName = NULL)
    {
        $pos = strpos('/', $relPath);
        if ($pos === 0)
        {
            throw new \InvalidArgumentException("Can not add Node at absolute path"); 
        }

        /* RepositoryException - If the last element of relPath has an index or if another error occurs. */
        if (strpos($relPath, '[') !== false)
        {
            throw new \PHPCR\RepositoryException("Index not allowed");
        }

        if ($primaryNodeTypeName == null)
        {
            $def = $this->getDefinition();
            $primaryNodeTypeName = $def->getDefaultPrimaryTypeName();
            if ($primaryNodeTypeName == null)
            {
                /* ConstraintViolationException - if a node type or implementation-specific constraint 
                 * is violated or if an attempt is made to add a node as the child of a property and 
                 * this implementation performs this validation immediately.*/ 
                throw new \PHPCR\NodeType\ConstraintViolationException("Can not determine default node type name for " . $this->getName());
            }
        }
        else
        {
            $ntm = $this->session->getWorkspace()->getNodeTypeManager();
            $nt = $ntm->getNodeType($primaryNodeTypeName);
        }

        $parts = explode('/', $relPath);
        $pathElements = count($parts);
        if ($pathElements == 1)
        {
            return $this->appendNode($relPath, $primaryNodeTypeName);
        }

        $node = $this->getNode($parts[0]);
        for ($i = 1; $i < $pathElements; $i++)
        {
            $node = $node->appendNode($parts[$i], $primaryNodeTypeName);
        }
        return $node;
    }

    public function getPropertyManager()
    {
        if (!$this->propertyManager)
        {
            $this->populateProperties();
        }
        return $this->propertyManager;
    }

    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function setProperty($name, $value, $type = null)
    {
        if ($value == null)
        {
            if (isset($this->properties[$name]))
            {
                /* TODO, FIXME, remove property from Propertymanager */
                unset($this->properties[$name]);
                return;
            }
        }

        if ($type != null)
        {
            $pDef = new \Midgard2CR\NodeType\PropertyDefinition($this, $name);
            $requiredType = $pDef->getRequiredType();

            if ($requiredType != 0
                && ($requiredType != null && $requiredType != $type))
            {
                throw new \PHPCR\NodeType\ConstraintViolationException("Wrong type for {$name} property. " . \PHPCR\PropertyType::nameFromValue($type) . " given. Expected " . \PHPCR\PropertyType::nameFromValue($requiredType));
            }
        }

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
        
        /* TODO, for performance reason, we could check if property's value has been changed.
         * By default, it's modified */
        $this->is_modified = true;

        return $property;
    }


    private function populateChildren()
    {
        if (!is_null($this->children))
        {
            return;
        }

        /* Node is not saved, so DO NOT list children of the same type */
        if (!$this->midgardNode->guid)
        {
            return;
        }

        $children = $this->midgardNode->list();
        foreach ($children as $child)
        {
            $this->children[$child->name] = new Node($child, $this, $this->getSession());
        }
    }

    public function getNode($relPath)
    {
        $remainingPath = '';
        $pos = strpos($relPath, '/');
        
        /* Convert to relative path when absolute one has been given */
        /* FIXME, Remove this part once absolute path is considered invalid
         * https://github.com/phpcr/phpcr-api-tests/issues/9 */
        if ($pos === 0)
        {
            $relPath = substr($relPath, 1);
            $pos = strpos($relPath, '/');
        }

        if ($pos !== false)
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

                /* ConstraintViolationException:
                 * "if a node type or implementation-specific constraint is violated or 
                 * if an attempt is made to add a node as the child of a property and 
                 * this implementation performs this validation immediately." */
                if ($this->hasProperty($relPath))
                {
                    throw new \PHPCR\NodeType\ConstraintViolationException("Can not add node to '{$relPath}' Item which is a Property");
                }

                $absPath = $this->getPath();
                //$guid = $this->getMidgard2Object()->guid;
                $guid = '';

                throw new \PHPCR\PathNotFoundException("Node at path '{$relPath}' not found. ({$remainingPath}). Requested at node {$absPath} with possible guid identifier '{$guid}'." . print_r($this->children ? array_keys($this->children) : array(), true));
            }
        }

        if ($remainingPath != '') 
        {
            return $this->children[$relPath]->getNode($remainingPath);
        }

        return $this->children[$relPath];        
    }

    private function getItemsSimilar($items, $nsname)
    {
        $ret = array();

        $nsregistry = $this->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();

        foreach ($items as $n => $o)
        {
            $prefixMatch = false;
            $nameMatch = false;
            $itemName = $o->getName();
            $node_prefix = $nsmanager->getPrefix($itemName);
            $prefix = $nsname[0];
            $name = $nsname[1];

            if ($prefix != "")
            {
                /* Compare prefix */
                if ($node_prefix == $prefix)
                {
                    $prefixMatch = true;
                }
            }
            else if ($prefix == '*'
                || $prefix == '')
            {
                /* Empty prefix or wildcard, everything matches */
                $prefixMatch = true;
            }

            if ($name != '' && $name != '*') 
            {
                /* Clean given name and item name:
                 * From name remove wildcard and from item's one - prefix. */
                $name = str_replace('*', '', $name);
                $itemName = str_replace($prefix . ':' , '', $itemName);
                $pos = strpos($itemName, $name);
               
                if ($pos !== false)
                {
                    $nameMatch = true;
                }
            }
            else if ($name == '*')
            {
                /* Wildcard so everything matches */
                $nameMatch = true;
            }

            if ($prefixMatch == true && $nameMatch == true)
            {
                $ret[$o->getName()] = $o;
            }
        }

        return $ret;
    }

    private function getItemsEqual($items, $nsnames)
    {
        $ret = array();

        $prefix = $nsnames[0];
        $name = $nsnames[1];

        if (array_key_exists($name, $this->children))
        {
            $ret[$name] = $items[$name];
        }
            
        return $ret;
    }

    /* Return array of prefixes and names.
     * Any prefix or name might be empty or null:
     * 
     * jcr:*
     * prefix = "jcr", name = "*"
     *
     * my doc
     * prefix = "", name = "my doc"
     *
     * jcr:created
     * prefix = "jcr", name = "created"
     */
    private function getFiltersFromString($filter)
    {
        $filters = array();
        $filtered = array();
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
        foreach ($filters as $f)
        {
            $parts = explode(':', $f);
            $prefix = "";
            $name = "";
            if (isset($parts[1]))
            {
                $prefix = $parts[0];
                $name = $parts[1]; 
            }
            else 
            {
                $name = $parts[0];
            }
            $filtered[] = array($prefix, $name);
        }

        return $filtered; 
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
        if ($filter == null)
        {
            return $items;
        } 

        $filteredItems = array();

        if (is_string($filter))
        {
            $filters = $this->getFiltersFromString($filter);
        }

        if(is_array($filter))
        {
            $filters = $this->getFiltersFromArray($filter);
        } 

        foreach ($filters as $i => $f)
        {
            if (strpos($f[0], '*') !== false 
                || strpos($f[1], '*') !== false)
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
            return new \ArrayIterator($this->children ? $this->children : array());
        }

        return $this->getItemsFiltered($this->children ? $this->children : array(), $filter); 
    }

    private function populateProperties()
    {
        if ($this->contentObject == null)
        {
            $this->contentObjectFactory($this->midgardNode, $this->primaryNodeTypeName);
        }

        if (!is_null($this->properties))
        {
            return;
        }

        $this->propertyManager = new \Midgard2CR\PropertyManager($this->contentObject);

        foreach ($this->contentObject as $property => $value)
        {
            if (strpos($property, '-') === false)
            {
                $this->properties["mgd:{$property}"] = new Property($this, "mgd:{$property}", null);
            }
            else 
            {
                $parts = explode('-', $property);
                $this->properties["{$parts[0]}:{$parts[1]}"] = new Property($this, "{$parts[0]}:{$parts[1]}", null);
            }
        }

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
                throw new \PHPCR\PathNotFoundException("Property at path '{$relPath}' not found at node " . $this->getName() . " at path " . $this->getPath());
            }
        }

        return $this->properties[$relPath];
    }
    
    public function getPropertyValue($name, $type=null)
    {
        return $this->getProperty($name)->getNativeValue();
    }
    
    public function getProperties($filter = null)
    {
        $this->populateProperties();
        $ret = $this->getItemsFiltered($this->properties, $filter);
        foreach ($ret as $property)
        {
            $name = $property->getName();
            $ret[$name] = $this->properties[$name]; 
        }
        return new \ArrayIterator($ret);
    }

    public function getPropertiesValues($filter = null, $dereference = true)
    {
        $properties = $this->getProperties($filter);
        $ret = array();
        foreach ($properties as $name => $o)
        {
            $type = $this->properties[$name]->getType();
            if ($type == \PHPCR\PropertyType::WEAKREFERENCE
                || $type == \PHPCR\PropertyType::REFERENCE
                || $type == \PHPCR\PropertyType::PATH)
            {
                if ($dereference == true)
                {
                    $ret[$name] = $this->properties[$name]->getNode();
                }
                else
                {
                    $ret[$name] = $this->properties[$name]->getString();
                }
            }
            else 
            {
                $ret[$name] = $this->properties[$name]->getValue(); 
            }
        }
        return $ret;
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
        if ($this->hasNode($primaryItem))
        {
            return $this->getNode($primaryItem);
        }
        
        return $this->getProperty($primaryItem);
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
        return $this->contentObject->guid;
    }
    
    public function getIndex()
    {
        /* We do not support same name siblings */
        return 1;
    }
    
    private function getReferencesByType($name = null, $type)
    {
        $ret = array();
        $this->populateProperties();
        /* If node has jcr:uuid property */
        if (!$this->hasProperty('jcr:uuid'))
        {
            return new \ArrayIterator($ret);
        }
         
        /* get its value */
        $uuid = $this->getPropertyValue('jcr:uuid');

        if ($uuid === null || $uuid === "")
        {
            throw new \PHPCR\RepositoryException("Invalid empty uuid value");
        }

        /* FIXME
         * Generalize this routine and move it to session 
         * see Session.getNodeByIdentifier
         */ 
        /*  query properties with such value, which are declared as reference property model */
        $storage = new \midgard_query_storage('midgard_property_view');
        $qs = new \midgard_query_select($storage);
        $group = new \midgard_query_constraint_group('AND');
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('value'),
                '=',
                new \midgard_query_value($uuid)
            )
        );

        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('type'),
                '=',
                new \midgard_query_value($type)
            )
        );

        if ($name != null)
        {
            $group->add_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property('name'),
                    '=',
                    new \midgard_query_value($name)
                )
            );
        }

        $qs->set_constraint($group);
        $qs->execute();
        if ($qs->get_results_count() < 1)
        {
            return new \ArrayIterator($ret);
        }

        $properties = $qs->list_objects();

        /* query references */
        foreach ($properties as $property)
        {
            $q = new \midgard_query_select(new \midgard_query_storage('midgard_tree_node'));        
            $q->set_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property('objectguid'), 
                    '=', 
                    new \midgard_query_value($property->objectguid)
                )
            );         
            $q->execute();
            $nodes = $q->list_objects();

            $path = self::getMidgardPath($nodes[0]);
            /* Convert to JCR path */
            $path = str_replace ('/jackalope', '', $path);
            $node = $this->session->getNode($path);
            //$node = $this->getSession()->getNodeByIdentifier($property->value);
            $ret[] = $node->getProperty($property->name);
        } 
        return new \ArrayIterator($ret);
    }

    public function getReferences($name = null)
    {
        return $this->getReferencesByType($name, 'Reference');
    }

    public function getWeakReferences($name = NULL)
    {
        return $this->getReferencesByType($name, 'WeakReference');
    }
    
    public function hasNode($relPath)
    {
        /* FIXME, optimize this.
         * Do not get node, check children array instead */
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

        /* Try native property first */
        if (property_exists($this->midgardNode->typename, str_replace(':', '-', $relPath)))
        {
            return true;
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
        $primaryType = $this->getPropertyValue('jcr:primaryType');
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        $nt = $ntm->getNodeType($primaryType);

        if (!$nt)
        {
            $name = $this->getName();
            throw new \PHPCR\RepositoryException("Failed to get NodeType from current '{$name}' node ({$primaryType})");
        }
        return $nt;
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
        return new NodeType\NodeDefinition($this);
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

        if ($this->contentObject->guid == $item->contentObject->guid)
        {
            return true;
        }

        return false;
    }

    private function getMidgardRelativePath($object)
    {
        $storage = new \midgard_query_storage('midgard_tree_node');

        $joined_storage = new \midgard_query_storage('midgard_tree_node');
        $left_property_join = new \midgard_query_property('id');
        $right_property_join = new \midgard_query_property('parent', $joined_storage);    

        $q = new \midgard_query_select($storage);
        $q->add_join("INNER", $left_property_join, $right_property_join);

        /* Set name and guid constraints */
        $group = new \midgard_query_constraint_group('AND');
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('name', $joined_storage), 
                '=', 
                new \midgard_query_value($object->name)
            )
        );
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('id', $joined_storage), 
                '=', 
                new \midgard_query_value($object->id)
            )
        );

        $q->set_constraint($group);
        
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
            $objects = self::getMidgardRelativePath($object, null);
            if (empty($objects))
            {
                break;
            }
            $object = $objects[0];

        } while (!empty($objects));

        return '/' . implode("/", $elements);
    }

    public function save()
    {
        $mobject = $this->getMidgard2ContentObject();
        $midgardNode = $this->getMidgard2Node();

        if ($this->remove == true)
        {
            $self::removeFromStorage($this);
            return;
        }

        /* Create */
        if ($this->isNew() === true)
        {
            if ($mobject->create() === true)
            {
                $this->getPropertyManager()->save();
                $midgardNode->typename = get_class($mobject);
                $midgardNode->objectguid = $mobject->guid;

                $parentNode = $this->parent->getMidgard2Node();
                $midgardNode->parentguid = $parentNode->guid;
                $midgardNode->parent = $parentNode->id;
                $midgardNode->create();
            }
            else 
            {
                die (\midgard_connection::get_instance()->get_error_string());
            }
        }

        if ($this->isModified() === true)
        { 
            if ($mobject->update() === true)
            {
                $this->getPropertyManager()->save();
                $midgardNode->update();
            }
        }

        $this->is_modified = true;
        $this->is_new = false;
    }

    public function remove()
    {
        $this->remove = true;
    }

    private function removeFromStorage($node)
    { 
        $mobject = $node->getMidgard2ContentObject();
        $midgardNode = $node->getMidgard2Node();

        if ($mobject->purge() == true)
        {
            $midgardNode->purge();
            /* TODO, FIXME, Remove properties from Propertymanager */
        }

        $children = $node->getNodes();
        foreach ($children as $child)
        {
            self::removeFromStorage($child);
        }
    }
}
