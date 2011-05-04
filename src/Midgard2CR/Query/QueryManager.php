<?php
namespace Midgard2CR\Query;

class QueryManager implements \PHPCR\Query\QueryManagerInterface 
{
    protected $qb = null;
    protected $session = null;

    public function QueryManager (\MidgardCR2\Session $session)
    {
        $this->session = $session;
    }

    public function createQuery($statement, $language)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getQOMFactory()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getQuery($node)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getSupportedQueryLanguages()
    {
        return array ("SQL");
    }
}
?>
