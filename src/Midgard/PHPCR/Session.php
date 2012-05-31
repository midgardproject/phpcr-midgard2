<?php
namespace Midgard\PHPCR;

use PHPCR\SessionInterface;
use PHPCR\CredentialsInterface;
use PHPCR\GuestCredentials;
use PHPCR\ItemNotFoundException;
use PHPCR\PathNotFoundException;
use PHPCR\RepositoryException;
use midgard_connection;
use midgard_object;
use midgard_user;
use Exception;
use DomDocument;

class Session implements SessionInterface
{
    protected $connection = null;
    protected $repository = null;
    protected $user = null;
    protected $workspace = null;
    protected $removeNodes = array();
    private $transaction = null;
    private $nsregistry = null;
    private $credentials = null;
    private $isAnonymous = true;
    private $nodeRegistry = null;
    private $name = null;
    private $sessionTracker = null;
    private $outsideTransaction = false;

    public function __construct(midgard_connection $connection, Repository $repository, midgard_user $user = null, midgard_object $rootObject, CredentialsInterface $credentials = null)
    {
        $this->connection = $connection;
        $this->repository = $repository;
        $this->user = $user;
        $this->credentials = $credentials;
        if ($credentials && !($credentials instanceof GuestCredentials)) {
            $this->isAnonymous = false;
        }
        $this->name = uniqid();
        $this->sessionTracker = new SessionTracker($this);
        $this->nodeRegistry = new NodeRegistry($rootObject, $this);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSessionTracker()
    {
        return $this->sessionTracker;
    }

    public function getNodeRegistry()
    {
        return $this->nodeRegistry;
    }

    public function getRepository()
    {
        return $this->repository;
    }
   
    public function getTransactionManager()
    {
        return $this->repository->getTransactionManager();
    }

    public function getUserID()
    {
        if (!$this->user) {
            return null;
        }
        
        return $this->user->login;
    }

    public function getAttributeNames()
    {
        return $this->credentials->getAttributeNames();
    }
    
    public function getAttribute($name)
    {
        return $this->credentials->getAttribute($name);
    }
    
    public function getWorkspace()
    {
        if ($this->workspace == null) { 
            $this->workspace = new \Midgard\PHPCR\Workspace($this);
        }

        return $this->workspace;
    }
    
    public function getRootNode()
    {
        return $this->nodeRegistry->getByPath('/');
    }
    
    public function impersonate(\PHPCR\CredentialsInterface $credentials)
    {
        throw new \PHPCR\LoginException('Not supported'); 
    }
    
    public function getNodeByIdentifier($id)
    {
        return $this->nodeRegistry->getByUuid($id);
    }

    public function getNodesByIdentifier($ids)
    {
        $ret = array();
        foreach ($ids as $id) {
            $ret[] = $this->getNodeByIdentifier($id);
        }
        return $ret;
    }

    public function getItem($absPath)
    {
        if (substr($absPath, 0, 1) != '/') {
            throw new PathNotFoundException("Expected absolute path. Given one '{$absPath}' is relative");
        }

        if ($this->nodeExists($absPath)) {
            return $this->getNode($absPath);
        }
        if ($this->propertyExists($absPath)) {
            return $this->getProperty($absPath);
        }
        throw new PathNotFoundException("No item matches path '{$absPath}'");
    }

    private function validatePath($absPath)
    {
        if (substr($absPath, 0, 1) != '/') {
            throw new RepositoryException("Full path required. Given one is '{$absPath}'");
        }

        if (strpos($absPath, '//') !== false) {
            throw new RepositoryException("Invalid path '{$absPath}'");
        }
    }
    
    public function getNode($absPath)
    {
        // Special case when node is expected to exists at '/' path.
        // Which means we can treat root node with special meaning here.
        if ($absPath == '/') {
            return $this->getRootNode();
        }

        $this->validatePath($absPath);
        return $this->getRootNode()->getNode(substr($absPath, 1));
    }

    public function getNodes($absPaths)
    {
        $nodes = array();
        foreach ($absPaths as $absPath) {
            try {
                $nodes[$absPath] = $this->getNode($absPath);
            } catch (Exception $e) {
                continue;
            }
        }
        return $nodes;
    }

    public function getProperty($absPath)
    {
        $this->validatePath($absPath);
        return $this->getRootNode()->getProperty(substr($absPath, 1));
    }
    
    public function itemExists($absPath)
    {
        if ($this->nodeExists($absPath)) {
            return true;
        }
        return $this->propertyExists($absPath);
    }
    
    public function nodeExists($absPath)
    {
        try {
            $this->getNode($absPath);
            return true;
        } catch (PathNotFoundException $e) {
            return false;
        }
    }
    
    public function propertyExists($absPath)
    {
        try {
            $this->getProperty($absPath);
            return true;
        } catch (PathNotFoundException $e) {
            return false;
        }
    }
    
    public function move($srcAbsPath, $destAbsPath)
    {
        /* RepositoryException - If the last element of destAbsPath has an index or if another error occurs. */
        if (strpos($destAbsPath, '[') !== false) {
            throw new \PHPCR\RepositoryException("Index not allowed in destination path");
        }

        $node = $this->getNode($srcAbsPath);

        /* No need to check destination node, source one exists and path is invalid */
        if ($srcAbsPath == $destAbsPath) {
            throw new \PHPCR\ItemExistsException("Source and destination paths are equal");
        }

        /* If paths are different, check if destination exists */
        if ($this->nodeExists($destAbsPath)) {
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
        try {
            $property = $this->getProperty($absPath);
            $property->remove();
        }
        catch (\PHPCR\PathNotFoundException $e)  {
            $node = $this->getNode($absPath);
            $node->remove();
        }
    }

    public function removeNode($node)
    {
        $this->removeNodes[] = $node;
    }

    public function removeNodeUndo($path) 
    {
        if (empty($this->removeNodes)) {
            return;
        }

        if (array_key_exists($path, $this->removeNodes)) {
            $this->removeNodes[$path] = null;
            unset($this->removeNodes[$path]);
        } 
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
        
        $t = $this->getTransactionManager();
        if ($t->inTransaction() == false && $this->outsideTransaction == false) {
            $t->begin();
        } else {
            $this->outsideTransaction = true;
        }

        $tracker = $this->getSessionTracker();
        $tracker->removeNodes();

        // Delete all removed nodes that don't have hard refs
        $removeAfter = array();
        foreach ($this->removeNodes as $node) {
            try {
                $node->removeMidgard2Node();
            } catch (\Exception $e) {
                $removeAfter[] = $node; 
            }
        }

        try {
            $root_node = $this->getRootNode();
            $root_node->save();
        
            $children = $root_node->getNodes();
        } catch (\Exception $e) {
            $t->rollback();
            throw $e;
        }
        foreach ($children as $name => $child) 
        { 
            /* FIXME DO NOT EXPECT BOOLEAN, CATCH EXCEPTION */
            if ($this->_node_save ($child) === false) {
                $midgard_errcode = \midgard_connection::get_instance()->get_error();
                $midgard_errstr = \midgard_connection::get_instance()->get_error_string();
                switch ($midgard_errcode) 
                {
                case MGD_ERR_OBJECT_NAME_EXISTS:
                    $t->rollback();
                    throw new \PHPCR\ItemExistsException($midgard_errstr);
                    break;
                case MGD_ERR_INVALID_NAME:
                case MGD_ERR_INVALID_OBJECT:
                case MGD_ERR_OBJECT_NO_PARENT:
                case MGD_ERR_INVALID_PROPERTY_VALUE:
                case MGD_ERR_INVALID_PROPERTY:
                case MGD_ERR_TREE_IS_CIRCULAR:
                    $t->rollback();
                    throw new \PHPCR\InvalidItemStateException($midgard_errstr);
                    break;
                case MGD_ERR_INTERNAL:
                    $t->rollback();
                    throw new \PHPCR\RepositoryException($midgard_errstr);
                }
            }
        }

        try {
            /* Remove nodes marked as removed */
            foreach ($removeAfter as $node) {
                $node->removeMidgard2Node();
            }
        } catch (\Exception $e) {
            $t->rollback();
            throw $e;
        }

        if ($this->outsideTransaction == false) {
            $t->commit();
        }

        unset($this->removeNodes);
        $this->removeNodes = array();

        //NoSuchNodeTypeException
        //ReferentialIntegrityException
    }

    public function refresh($keepChanges)
    {
        if ($this->hasPendingChanges() === false) {
            return;
        }

        if ($keepChanges === false) {
            $this->removeNodes = array();
        }
        $this->getRootNode()->refresh($keepChanges);
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
        /* Check if any node should be removed */
        if (!empty($this->removeNodes)) {
            return true;
        }

        /* Check new or modified nodes */
        $root_node = $this->getRootNode();
        $children = $root_node->getNodes();
        foreach ($children as $name => $child) 
        {
            if ($this->_check_pending_changes ($child) === true)
            {
                return true;
            }
        }

        return false;
    }
    
    public function hasPermission($absPath, $actions)
    {
        $hasPermission = false;
        $acts = explode(',', $actions);
        foreach ($acts as $action) {

            /* TODO, refactor permission cases and extend them */

            /* read */
            if ($action == 'read')
                $hasPermission = true;

            /* add_node */
            if ($action == 'add_node'
                && $this->isAnonymous == false)
                $hasPermission = true;

            /* remove */
            if ($action == 'remove'
                && $this->isAnonymous == false)
                $hasPermission = true;

            /* set_property */
            if ($action == 'set_property'
                && $this->isAnonymous == false)
                $hasPermission = true;

        }
        return $hasPermission;
    }
    
    public function checkPermission($absPath, $actions)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function hasCapability($methodName, $target, array $arguments)
    {
        return true;
    }
    
    public function getImportContentHandler($parentAbsPath, $uuidBehavior)
    {
        return null;
    }
    
    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        if ($doc->load($in) === false) {
            throw new \PHPCR\InvalidSerializedDataException("Can not parse given '{$in}' xml file");
        }

        /* PathNotFoundException may be thrown */
        $node = $this->getNode($parentAbsPath);

        if ($doc->documentElement->localName == 'node') {
            /* SystemView xml */
            $importer = new XMLSystemViewImporter($node, $doc, $uuidBehavior);
        } else {
            /* DocumentView xml */
            $importer = new XMLDocumentViewImporter($node, $doc, $uuidBehavior);
        }

        $importer->import();
        /* Do some validation, and throw proper exception */
    }
    
    public function exportSystemView($absPath, $out, $skipBinary, $noRecurse)
    {
        $this->setNamespacePrefix('sv', 'http://www.jcp.org/jcr/sv/1.0');
        $node = $this->getNode($absPath);
        $exporter = new XMLSystemViewExporter($node, $skipBinary, $noRecurse);

        fwrite($out, $exporter->getXMLBuffer());
    }
    
    public function exportDocumentView($absPath, $out, $skipBinary, $noRecurse)
    {
        $this->setNamespacePrefix('sv', 'http://www.jcp.org/jcr/sv/1.0');
        $node = $this->getNode($absPath);
        $exporter = new XMLDocumentViewExporter($node, $skipBinary, $noRecurse);

        fwrite($out, $exporter->getXMLBuffer());
    }

    private function populateNamespaces()
    {
        if ($this->nsregistry != null)
            return;

        $nsReg = $this->getWorkspace()->getNamespaceRegistry();
        $prefixes = $nsReg->getPrefixes();
        foreach ($prefixes as $prefix)
        {
            $this->nsregistry[$prefix] = $nsReg->getURI($prefix); 
        }
    }

    public function setNamespacePrefix($prefix, $uri)
    {
        if (!$prefix || !$uri) 
        {
            throw new \PHPCR\NamespaceException("Can not set namespace with empty prefix or empty uri");
        }
        
        if (strpos(strtolower($prefix), 'xml') !== false)
        {
            throw new \PHPCR\NamespaceException("Can not set prefix. Reserved 'xml' included");
        }

        $this->populateNamespaces();
        $this->nsregistry[$prefix] = $uri;
    }
    
    public function getNamespacePrefixes()
    {
        $this->populateNamespaces();
        return (array_keys($this->nsregistry));
    }
    
    public function getNamespaceURI($prefix)
    {
        $this->populateNamespaces();
        if (!isset($this->nsregistry[$prefix]))
        {
            throw new \PHPCR\NamespaceException ("URI for given {$prefix} prefix not found");
        }
        return $this->nsregistry[$prefix];
    }
    
    public function getNamespacePrefix($uri)
    {
        $this->populateNamespaces();
        $prefix = array_search($uri, $this->nsregistry);
        if ($prefix === false)
        {
            throw new \PHPCR\NamespaceException ("Prefix for given {$uri} uri not found");
        }
        return $prefix;
    }
    
    public function logout()
    {
        if ($this->connection->get_user()) {
            $this->connection->get_user()->logout();
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
