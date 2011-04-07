<?php
namespace Midgard2CR;

class Node extends Item implements \IteratorAggregate, \PHPCR\NodeInterface
{
 
    public function addNode($relPath, $primaryNodeTypeName = NULL)
    {
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
    
    public function getNode($relPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getNodes($filter = NULL)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getProperty($relPath)
    {
        return new Property($this, $relPath);
    }
    
    public function getPropertyValue($name, $type=null)
    {
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
        return false;
    }
    
    public function hasProperty($relPath)
    {
        return false;
    }
    
    public function hasNodes()
    {
        return false;
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
