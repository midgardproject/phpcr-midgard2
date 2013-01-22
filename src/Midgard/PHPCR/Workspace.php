<?php
namespace Midgard\PHPCR;

use \MidgardWorkspace;
use \MidgardWorkspaceContext;
use \MidgardWorkspaceManager;
use \MidgardConnection;
use \MidgardQueryStorage;
use \MidgardQuerySelect;
use \MidgardQueryConstraint;
use \MidgardQueryValue;
use \MidgardQueryProperty;
use \MidgardTransaction;
use \midgard_node;
use \PHPCR\PropertyType;

class Workspace implements \PHPCR\WorkspaceInterface
{
    protected $session = null;
    protected $query_manager = null;
    protected $namespace_registry = null;
    protected $name = "";
    protected $midgard_workspace = null;
    protected $nodeTypeManager = null;

    public function __construct (\Midgard\PHPCR\Session $session)
    {
        $this->session = $session;
        $workspace = \midgard_connection::get_instance()->get_workspace();
        if (is_object($workspace))
        {
            $this->midgard_workspace = $workspace;
        }            
    }

    public function getSession()
    {
        return $this->session; 
    }

    public function getName()
    {
        if ($this->midgard_workspace == null)
        {
            return "";
        } 

        return $this->midgard_workspace->name;
    }

    private function recursiveCopy($srcNode, $dstNode)
    {
        $newNode = $dstNode->addNode($srcNode->getName(), $srcNode->getPrimaryNodeType()->getName());
        /* Copy properties */
        foreach ($srcNode->getProperties() as $name => $value) {
            $type = $srcNode->getProperty($name)->getType();
            if ($type == PropertyType::NAME) {
                $value = $srcNode->getProperty($name)->getString();
            } else {
                $value = $srcNode->getPropertyValue($name, $type);
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    $newNode->setProperty($name, $v, $type);
                }
            } else {
                $newNode->setProperty($name, $value, $type);
            }
        }

        /* Register node as properties has been added after registration */
        $this->session->getNodeRegistry()->registerNode($newNode);

        /* Copy all children nodes */
        $children = $srcNode->getNodes();
        foreach ($children as $node) {
            $this->recursiveCopy($node, $newNode);
        }
    }

    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = NULL)
    {
        $mgd = MidgardConnection::get_instance();
        $srcWs = null;
        $wmanager = new MidgardWorkspaceManager($mgd);
        /*Get current workspace */
        $currentWs = $mgd->get_workspace();
       
        if ($srcWorkspace != null) {
            $srcWs = new MidgardWorkspace();
            try {
                $wmanager->get_workspace_by_path($srcWs, $srcWorkspace);
            } catch (\Exception $e) {
                throw new \PHPCR\NoSuchWorkspaceException($e->getMessage() . " Workspace {$srcWorkspace} doesn't exist");
            }
            $mgd->set_workspace($srcWs);
        }
    
        /* Get source node */     
        $srcNode = $this->session->getNode($srcAbsPath);

        /* Get destination node, if it doesn't exist, create */
        try {
            $dstNode = $this->session->getNode($destAbsPath);
        } catch (\PHPCR\PathNotFoundException $e) {
            $pos = strrpos($destAbsPath, "/");
            $node = $this->session->getNode(substr($destAbsPath, 0, $pos));
            $dstNode = $node->addNode(substr($destAbsPath, $pos+1));
        }

        $srcMgdObj = $srcNode->getMidgard2ContentObject();
        $dstMgdObj = $dstNode->getMidgard2ContentObject();

        if ($srcWorkspace == null) {
            $this->recursiveCopy($srcNode, $dstNode);
        } else {
            $this->cloneWorkspace('midgard_node', $srcMgdObj->id, $dstMgdObj->id, $srcWs, $currentWs);
        }

        /* Fallback to previous workspace */
        $mgd->set_workspace($currentWs);
    }

    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function move($srcAbsPath, $destAbsPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function getLockManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException("Not supported");        
    }

    public function getQueryManager()
    {
        if ($this->query_manager == null)
        {
            $this->query_manager = new \Midgard\PHPCR\Query\QueryManager($this->session);
        }

        return $this->query_manager;
    }

    public function getTransactionManager()
    {
        return $this->session->getTransactionManager();
    }

    public function getNamespaceRegistry()
    {
        if ($this->namespace_registry == null)
        {
            $this->namespace_registry = new \Midgard\PHPCR\NamespaceRegistry($this->session);
        }

        return $this->namespace_registry;
    }

    public function getNodeTypeManager()
    {
        if ($this->nodeTypeManager == null)
        {
            $this->nodeTypeManager = new NodeType\NodeTypeManager();
        }
        return $this->nodeTypeManager;
    }

    public function getObservationManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException("Not supported");        
    }

    public function getVersionManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException("Not supported");        
    }

    public function getAccessibleWorkspaceNames()
    {
        $storage = new MidgardQueryStorage("MidgardWorkspace");
        $qs = new MidgardQuerySelect($storage);
        $qs->toggle_readonly(false);
        $qs->set_constraint(
            new \MidgardQueryConstraint(
                new \MidgardQueryProperty('up'),
                '=',
                new \MidgardQueryValue(0)
            )
        );
        try {
            $qs->execute();
        } catch (\Exception $e) {
            throw new \PHPCR\RepositoryException($e->getMessage());
        }
        $objects = $qs->list_objects();

        $ret = array();

        foreach ($objects as $o) {
            if (strpos($o->name, '__PURGED') == false) {
                $ret[] = $o->name;
            }
        }

        return $ret;
    }

    public function getImportContentHandler($parentAbsPath, $uuidBehavior)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    private function cloneWorkspace($type, $oldParent, $newParent, $srcWs, $dstWs)
    {
        /* Get connection and set source workspace */
        $mgd = MidgardConnection::get_instance();
        $mgd->set_workspace($srcWs);

        /* Query objects in given workspace */
        $storage = new MidgardQueryStorage($type);
        $qs = new MidgardQuerySelect($storage);
        $qs->toggle_readonly(false);
        $qs->set_constraint(
            new \MidgardQueryConstraint(
                new \MidgardQueryProperty('parent'),
                '=',
                new \MidgardQueryValue($oldParent)
            )
        );
        $qs->execute();
        $objects = $qs->list_objects();

        foreach ($objects as $o) {
            /* Set new parent, new workspace and update object */
            $mgd->set_workspace($dstWs);
            
            $oldID = $o->id;
            $o->parent = $newParent;
            $o->update();
            
            /* Throw base exception on failure */
            if ($mgd->get_error_string() != "MGD_ERR_OK") {
                throw new \Exception($mgd->get_error_string());
            }

            /* if this is a node, update child objects  */
            if ($type == 'midgard_node') {
                $this->cloneWorkspace('midgard_node', $oldID, $o->id, $srcWs, $dstWs);
                $this->cloneWorkspace('midgard_node_property', $oldID, $o->id, $srcWs, $dstWs);
            }
        }
    }

    public function createWorkspace($name, $srcWorkspace = null)
    {
        $mgd = MidgardConnection::get_instance();
        $srcWs = null;
        $wmanager = new MidgardWorkspaceManager($mgd);
       
        if ($srcWorkspace != null) {
            $srcWs = new MidgardWorkspace();
            try {
                $wmanager->get_workspace_by_path($srcWs, $srcWorkspace);
            } catch (\Exception $e) {
                throw new \PHPCR\NoSuchWorkspaceException($e->getMessage() . " Workspace {$srcWorkspace} doesn't exist");
            }
        }

        if ($wmanager->path_exists($name)) {
            throw new \PHPCR\RepositoryException("Can not create workspace. {$name} already exists.");
        }

        $dstWs = new MidgardWorkspace();
        $dstWs->name = $name;
        $wmanager->create_workspace($dstWs, '');

        if ($srcWs != null) {
            $this->cloneWorkspace('midgard_node', 0, 0, $srcWs, $dstWs);
            /* Fallback to previous workspace */
            $mgd->set_workspace($srcWs);
        }
    }

    private function purgeObjects($type)
    {
        $storage = new MidgardQueryStorage($type);
        $qs = new MidgardQuerySelect($storage);
        $qs->toggle_readonly(false);
        $qs->execute();
        $objects = $qs->list_objects();

        foreach ($objects as $o) {
            $o->purge(false);
        }

    }

    public function deleteWorkspace($name)
    {
        $mgd = MidgardConnection::get_instance();
        $currentWs = $mgd->get_workspace();
        $wmanager = new MidgardWorkspaceManager($mgd);

        $delWs = new MidgardWorkspace();
        try {
            $wmanager->get_workspace_by_path($delWs, $name);
        } catch (\Exception $e) {
            throw new \PHPCR\NoSuchWorkspaceException($e->getMessage() . " Workspace {$name} doesn't exist");
        }

        /* Set the global workspace scope */
        $mgd->set_workspace($delWs);

        /* Purge all objects we use in phpcr */
        $this->purgeObjects("midgard_attachment");
        $this->purgeObjects("midgard_node_property");
        $this->purgeObjects("midgard_node");

        /* midgard core doesn't support workspace purge, so try workaround */
        $delWs->name .= "__PURGED";
        $wmanager->update_workspace($delWs);

        $mgd->set_workspace($currentWs);
    }

    public function removeItem($absPath)
    {

    }

    public function getRepositoryManager()
    {

    }
}
