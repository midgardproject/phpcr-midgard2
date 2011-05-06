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

    private function populateChildren()
    {
        if (!is_null($this->children))
        {
            return;
        }

        $q = new \midgard_query_select(new \midgard_query_storage('midgardmvc_core_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('up'), '=', new \midgard_query_value($this->object->id)));
        $q->execute();

        if ($q->get_results_count() == 0)
        {
            return;
        }

        $children = $q->list_objects();
        foreach ($children as $child)
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
                throw new \PHPCR\PathNotFoundException();
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
    }

    public function getProperty($relPath)
    {
        $remainingPath = '';
        if (strpos($relPath, '/') !== false)
        {
            $parts = explode('/', $relPath);
            $relPath = array_shift($parts);
            $remainingPath = implode('/', $parts);
            return $this->getNode($relPath)->getProperty($remainingPath);
        }

        if (!isset($this->properties[$relPath]))
        {
            $this->populateProperties();
            if (!isset($this->properties[$relPath]))
            {
                throw new \PHPCR\PathNotFoundException();
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
        return false;
    }
    
    public function hasNodes()
    {
        if (empty($this->children))
        {
            return false;
        }

        return true;
    }
    
    public function hasProperties()
    {
        return false;
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
