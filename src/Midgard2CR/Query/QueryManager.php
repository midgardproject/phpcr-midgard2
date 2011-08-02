<?php
namespace Midgard2CR\Query;

class QueryManager implements \PHPCR\Query\QueryManagerInterface 
{
    protected $qb = null;
    protected $session = null;
    protected $query = null;
    protected $supportedLanguages = null;

    public function __construct (\Midgard2CR\Session $session)
    {
        $this->session = $session;
        $this->supportedLanguages = array (
            \PHPCR\Query\QueryInterface::JCR_SQL2,
            \PHPCR\Query\QueryInterface::JCR_JQOM
        );
    }

    public function createQuery($statement, $language)
    {
        if ($language != \PHPCR\Query\QueryInterface::JCR_SQL2)
        {
            throw new \PHPCR\Query\InvalidQueryException("Unsupported '{$language}' language");
        }

        if ($this->query == null)
        {
            $this->query = new SQLQuery($this->session, $statement);
        }
        return $this->query;
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
        return $this->supportedLanguages;
    }
}
?>
