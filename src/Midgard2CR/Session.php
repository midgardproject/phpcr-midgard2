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
    protected $removeNodes = array();
    private $transaction = null;

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
   
    public function getTransactionManager()
    {
        if ($this->transaction == null)
        {
            $this->transaction = new \Midgard2CR\Transaction\Transaction();
        }
        return $this->transaction;
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
        /* TODO
         * Try to get midgard object by guid if required */

        $propertyStorage = new \midgard_query_storage('midgard_node_property');
        $q = new \midgard_query_select(new \midgard_query_storage('midgard_node'));
        $q->add_join(
            'INNER',
            new \midgard_query_property('id'),
            new \midgard_query_property('parent', $propertyStorage)
        );
        $group = new \midgard_query_constraint_group('AND');
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('value', $propertyStorage), 
                '=', 
                new \midgard_query_value($id)
            )
        );
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('title', $propertyStorage), 
                '=', 
                new \midgard_query_value('jcr:uuid')
            )
        ); 
        $q->set_constraint($group);
        $q->execute();
       
        if ($q->get_results_count() < 1)
        {
            throw new \PHPCR\ItemNotFoundException("Node identified by {$id} not found");
        }

        $midgardNode = current($q->list_objects());

        try 
        { 
            $midgard_path = \Midgard2CR\Node::getMidgardPath($midgardNode);
            /* Convert to JCR path */
            $midgard_path = str_replace('/jackalope', '', $midgard_path);
            $node = $this->getNode($midgard_path);
            return $node;
        }
        catch (\midgard_error_exception $e)
        {
             throw new \PHPCR\ItemNotFoundException("Storage node identified by {$id} not found : " . $e->getMessage()); 
        }

        throw new \PHPCR\RepositoryException("Answer the question three and the node you will see");
    }

    public function getNodesByIdentifier($ids)
    {
        return array();
    }

    public function getItem($absPath)
    {
        if (substr($absPath, 0, 1) != '/') 
        {
            throw new \PHPCR\PathNotFoundException("Expected absoulte path. Given one '{$absPath}' is relative");
        }

        if ($this->nodeExists($absPath))
        {
            return $this->getNode($absPath);
        }
        if ($this->propertyExists($absPath))
        {
            return $this->getProperty($absPath);
        }
        throw new \PHPCR\PathNotFoundException("No item matches path '{$absPath}'");
    }

    private function validatePath($absPath)
    {
        if (substr($absPath, 0, 1) != '/')
        {
            throw new \PHPCR\RepositoryException("Full path required. Given one is '{$absPath}'");
        }

        if (strpos($absPath, '//') !== false)
        {
            throw new \PHPCR\RepositoryException("Invalid path '{$absPath}'");
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

    public function getNodes($absPaths)
    {
        return null;
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
        /* RepositoryException - If the last element of destAbsPath has an index or if another error occurs. */
        if (strpos($destAbsPath, '[') !== false)
        {
            throw new \PHPCR\RepositoryException("Index not allowed in destination path");
        }

        $node = $this->getNode($srcAbsPath);

        /* No need to check destination node, source one exists and path is invalid */
        if ($srcAbsPath == $destAbsPath)
        {
            throw new \PHPCR\ItemExistsException("Source and destination paths are equal");
        }

        /* If paths are different, check if destination exists */
        if ($this->nodeExists($destAbsPath))
        {
            throw new \PHPCR\ItemExistsException("Node at destination path {$destAbsPath} exists");
        }

        $dest = mb_substr($destAbsPath,0,-mb_strlen(strrchr($destAbsPath,'/')));
        $destName = substr(strrchr($destAbsPath, '/'), 1); 
        $destNode = $this->getNode($dest);
        $node->move($destNode, $destName);
    }
    
    public function removeItem($absPath)
    {
        /* Try property first */
        try 
        {
            $property = $this->getProperty($absPath);
            $property->remove();
        }
        catch (\PHPCR\PathNotFoundException $e) 
        {
            $node = $this->getNode($absPath);
            $node->remove();
            $this->removeNode($node);
        }
    }

    public function removeNode($node)
    {
        $this->removeNodes[] = $node;
    }

    private function _node_save (Node $node)
    {
        $node->save();
        $children = $node->getNodes();
        foreach ($children as $name => $child) 
        {
            $this->_node_save($child);
        }
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

        /* Remove nodes marked as removed */
        foreach ($this->removeNodes as $node)
        {
            $node->removeMidgard2Node();
        }

        $root_node = $this->getRootNode();
        $root_node->save();
        $children = $root_node->getNodes();
        foreach ($children as $name => $child) 
        { 
            /* FIXME DO NOT EXPECT BOOLEAN, CATCH EXCEPTION */
            if ($this->_node_save ($child) === false)
            {
                $midgard_errcode = \midgard_connection::get_instance()->get_error();
                $midgard_errstr = \midgard_connection::get_instance()->get_error_string();
                switch ($midgard_errcode) 
                {
                case MGD_ERR_OBJECT_NAME_EXISTS:
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
        $nsReg = $this->getWorkspace()->getNamespaceRegistry();
        $nsReg->registerNamespace($prefix, $uri);
    }
    
    public function getNamespacePrefixes()
    {
        $nsReg = $this->getWorkspace()->getNamespaceRegistry();
        return $nsReg->getPrefixes();
    }
    
    public function getNamespaceURI($prefix)
    {
        $nsReg = $this->getWorkspace()->getNamespaceRegistry();
        return $nsReg->getUri($prefix);
    }
    
    public function getNamespacePrefix($uri)
    {
        $nsReg = $this->getWorkspace()->getNamespaceRegistry();
        return $nsReg->getPrefix($uri);   
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
