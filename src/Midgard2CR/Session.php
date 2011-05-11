<?php
namespace Midgard2CR;

class Session implements \PHPCR\SessionInterface
{
    protected $connection = null;
    protected $repository = null;
    protected $user = null;
    protected $rootObject = null;
    protected $rootNode = null;
    protected $workspace = null;

    public function __construct(\midgard_connection $connection, Repository $repository, \midgard_user $user = null, \midgard_object $rootObject)
    {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->user = $user;
        $this->rootObject = $rootObject;
    }

    public function getRepository()
    {
        return $this->repository;
    }
    
    public function getUserID()
    {
        if (!$this->user)
        {
            return null;
        }
        
        return $this->user->login;
    }
    
    public function getAttributeNames()
    {
        return array();
    }
    
    public function getAttribute($name)
    {
        return '';
    }
    
    public function getWorkspace()
    {
        if ($this->workspace == null)
        {
            $this->workspace = new \Midgard2CR\Workspace($this);
        }

        return $this->workspace;
    }
    
    public function getRootNode()
    {
        if ($this->rootNode === null)
        {
            $this->rootNode = new Node($this->rootObject, null, $this);
        }

        return $this->rootNode;
    }
    
    public function impersonate(\PHPCR\CredentialsInterface $credentials)
    {
        return new Session($this->repository);
    }
    
    public function getNodeByIdentifier($id)
    {
        throw new \PHPCR\RepositoryException("Not implemented");
    }
    
    public function getItem($absPath)
    {
        if ($this->nodeExists($absPath))
        {
            return $this->getNode($absPath);
        }
        if ($this->propertyExists($absPath))
        {
            return $this->getProperty($absPath);
        }
        throw new \PHPCR\PathNotFoundException('No item matches path');
    }

    private function validatePath($absPath)
    {
        if (substr($absPath, 0, 1) != '/')
        {
            throw new \PHPCR\RepositoryException('Full path required');
        }

        if (strpos($absPath, '//') !== false)
        {
            throw new \PHPCR\RepositoryException('Invalid path');
        }
    }
    
    public function getNode($absPath)
    {
        // Special case when node is expected to exists at '/' path.
        // Which means we can treat root node with special meaning here.
        if ($absPath == '/')
        {
            return $this->getRootNode();
        }

        $this->validatePath($absPath);
        return $this->getRootNode()->getNode(substr($absPath, 1));
    }
    
    public function getProperty($absPath)
    {
        $this->validatePath($absPath);
        return $this->getRootNode()->getProperty(substr($absPath, 1));
    }
    
    public function itemExists($absPath)
    {
        if ($this->nodeExists($absPath))
        {
            return true;
        }
        return $this->propertyExists($absPath);
    }
    
    public function nodeExists($absPath)
    {
        try
        {
            $this->getNode($absPath);
            return true;
        }
        catch (\PHPCR\PathNotFoundException $e)
        {
            return false;
        }
    }
    
    public function propertyExists($absPath)
    {
        try
        {
            $this->getProperty($absPath);
            return true;
        }
        catch (\PHPCR\PathNotFoundException $e)
        {
            return false;
        }
    }
    
    public function move($srcAbsPath, $destAbsPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function removeItem($absPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    private function _node_save (Node $node, Node $parent)
    {
        // Create 
        if ($node->isNew() === true)
        {
            $mobject = $node->getMidgard2Object();
            $mobject->up = $parent->getMidgard2Object()->id;
            if ($mobject->create() === true)
            {
                return true;
            }

            return false;
        }

        // Update
        if ($node->isModified() === true)
        {
            $mobject = $node->getMidgard2Object();
            if ($mobject->update() === true)
            {
                return true;
            }

            return false;
        }

        $children = $node->getNodes();
        foreach ($children as $name => $child) 
        {
            if ($this->_node_save ($child, $node) === false)
            {
                return false;
            }
        }

        // Nothing to do, return success
        return true;
    }

    public function save()
    {
        // ConstraintViolationException
        // TODO
        
        // AccessDeniedException
        // TODO

        // LockException
        // TODO
         
        // VersionException
        // TODO

        $root_node = $this->getRootNode();
        $children = $root_node->getNodes();
        foreach ($children as $name => $child) 
        {
            if ($this->_node_save ($child, $root_node) === false)
            {
                $midgard_errcode = midgard_connection::get_instance()->get_error();
                $midgard_errstr = midgard_connection::get_instance()->get_error_string();
                switch ($midgard_errcode) 
                {
                case MGD_ERR_NAME_EXISTS:
                    throw new \PHPCR\ItemExistsException($midgard_errstr);
                    break;
                case MGD_ERR_INVALID_NAME:
                case MGD_ERR_INVALID_OBJECT:
                case MGD_ERR_OBJECT_NO_PARENT:
                case MGD_ERR_INVALID_PROPERTY_VALUE:
                case MGD_ERR_INVALID_PROPERTY:
                case MGD_ERR_TREE_IS_CIRCULAR:
                    throw new \PHPCR\InvalidItemStateException($midgard_errstr);
                    break;
                case MGD_ERR_INTERNAL:
                    throw new \PHPCR\RepositoryException($midgard_errstr);
                }
            }
        }

        //NoSuchNodeTypeException
        //ReferentialIntegrityException
    }
    
    public function refresh($keepChanges)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function clear()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    private function _check_pending_changes (Node $node)
    {
        if ($node->isNew() === true
            || $node->isModified() === true) 
        {
            return true;
        }

        $children = $node->getNodes();
        foreach ($children as $name => $child)
        {
            if ($this->_check_pending_changes ($child) === true)
            {
                return true;
            }
        }

        return false;
    }

    public function hasPendingChanges()
    {
        $root_node = $this->getRootNode();
        $children = $root_node->getNodes();
        foreach ($children as $name => $child) 
        {
            if ($this->_check_pending_change ($child) === true)
            {
                return true;
            }
        }

        return false;
    }
    
    public function hasPermission($absPath, $actions)
    {
        return false;
    }
    
    public function checkPermission($absPath, $actions)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function hasCapability($methodName, $target, array $arguments)
    {
        return false;
    }
    
    public function getImportContentHandler($parentAbsPath, $uuidBehavior)
    {
        return null;
    }
    
    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function exportSystemView($absPath, $out, $skipBinary, $noRecurse)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function exportDocumentView($absPath, $out, $skipBinary, $noRecurse)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function setNamespacePrefix($prefix, $uri)
    {
    }
    
    public function getNamespacePrefixes()
    {
        return array();
    }
    
    public function getNamespaceURI($prefix)
    {
        return '';
    }
    
    public function getNamespacePrefix($uri)
    {
        return '';
    }
    
    public function logout()
    {
        if ($this->user)
        {
            $this->user->logout();
            $this->user = null;
        }
    }
    
    public function isLive()
    {
        if ($this->user)
        {
            return true;
        }
        return false;
    }
    
    public function getAccessControlManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function getRetentionManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
}
