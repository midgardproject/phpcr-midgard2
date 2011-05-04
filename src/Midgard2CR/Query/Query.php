<?php
namespace Midgard2CR\Query;

class Query implements \PHPCR\Query\QueryInterface
{
    protected $session = null;
    protected $qb = null;
    
    public function Query (\Midgard2CR\Session $session)
    {
        $this->session = $session;
    }

    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function execute()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function setLimit($limit)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
   
    public function setOffset($offset)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
  
    public function getStatement()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
     
    public function getLanguage()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
     
    public function getStoredQueryPath()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
  
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

}
