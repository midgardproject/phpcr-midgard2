<?php
namespace Midgard2CR;

class Workspace implements \PHPCR\WorkspaceInterface
{
    protected $session = null;
    protected $query_manager = null;
    protected $namespace_registry = null;
    protected $name = "";
    protected $midgard_workspace = null;
    protected $nodeTypeManager = null;

    public function __construct (\Midgard2CR\Session $session)
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

    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = NULL)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
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
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function getQueryManager()
    {
        if ($this->query_manager == null)
        {
            $this->query_manager = new \Midgard2CR\Query\QueryManager($this->session);
        }

        return $this->query_manager;
    }

    public function getNamespaceRegistry()
    {
        if ($this->namespace_registry == null)
        {
            $this->namespace_registry = new \Midgard2CR\NamespaceRegistry($this->session);
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
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function getVersionManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException("Not supported");        
    }

    public function getAccessibleWorkspaceNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function getImportContentHandler($parentAbsPath, $uuidBehavior)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function createWorkspace($name, $srcWorkspace = NULL)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }

    public function deleteWorkspace($name)
    {
        throw new \PHPCR\RepositoryException("Not supported");        
    }
}
