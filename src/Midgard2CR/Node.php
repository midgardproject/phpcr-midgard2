<?php
namespace Midgard2CR;

class Node extends Item implements \IteratorAggregate, \PHPCR\NodeInterface
{
    protected $children = null;
    protected $properties = null;

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
    
    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function setProperty($name, $value, $type = NULL)
    {
        throw new \PHPCR\RepositoryException("Not supported");
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

        \midgard_connection::get_instance()->set_loglevel("debug");

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
        if (strpos($relPath, '/') !== false)
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
                throw new \PHPCR\PathNotFoundException("Node at path '{$relPath}' not found");
            }
        }

        if ($remainingPath)
        {
            return $this->children[$relPath]->getNode($remainingPath);
        }
        return $this->children[$relPath];        
    }
    
    public function getNodes($filter = NULL)
    {
        // TODO: Filtering support
        $this->populateChildren();
        return new \ArrayIterator($this->children);
    }

    private function populateProperties()
    {
        if (!is_null($this->properties))
        {
            return;
        }

        foreach ($this->object as $property => $value)
        {
            $this->properties["mgd:{$property}"] = new Property($this, "mgd:{$property}");
        }

        $params = $this->object->list_parameters();
        foreach ($params as $param)
        {
            if ($param->domain == 'phpcr:undefined')
            {
                $this->properties[$param->name] = new Property($this, $param->name);
                continue;
            }
            $this->properties["{$param->domain}:{$param->name}"] = new Property($this, "{$param->domain}:{$param->name}");
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
    }

    public function getPropertiesValues($filter=null)
    {
    }   
    
    public function getPrimaryItem()
    {
        throw new \PHPCR\ItemNotFoundException();
    }
    
    public function getIdentifier()
    {
        return $this->object->guid;
    }
    
    public function getIndex()
    {
       throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getReferences($name = NULL)
    {
    }
    
    public function getWeakReferences($name = NULL)
    {
    }
    
    public function hasNode($relPath)
    {
        try {
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
    }
    
    public function removeSharedSet()
    {
    }
    
    public function removeShare()
    {
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
}
