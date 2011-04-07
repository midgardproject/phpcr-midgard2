<?php
namespace Midgard2CR;

class Session implements \PHPCR\SessionInterface
{
    protected $connection = null;
    protected $repository = null;
    protected $user = null;
    protected $rootObject = null;

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
        return null;
    }
    
    public function getRootNode()
    {
        return new Node($this->rootObject, null, $this);
    }
    
    public function impersonate(\PHPCR\CredentialsInterface $credentials)
    {
        return new Session($this->repository);
    }
    
    public function getNodeByIdentifier($id)
    {
        return null;
    }
    
    public function getItem($absPath)
    {
        return null;
    }
    
    public function getNode($absPath)
    {
        if (substr($absPath, 0, 1) == '/')
        {
            $absPath = substr($absPath, 1);
        }
        return $this->getRootNode()->getNode($absPath);
    }
    
    public function getProperty($absPath)
    {
        if (substr($absPath, 0, 1) == '/')
        {
            $absPath = substr($absPath, 1);
        }
        return $this->getRootNode()->getProperty($absPath);
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
    
    public function save()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function refresh($keepChanges)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function clear()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function hasPendingChanges()
    {
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
        // TODO: Check Midgard connection and is_user
        return true;
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
