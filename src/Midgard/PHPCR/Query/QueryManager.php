<?php
namespace Midgard\PHPCR\Query;

class QueryManager implements \PHPCR\Query\QueryManagerInterface 
{
    protected $qb = null;
    protected $session = null;
    protected $query = null;
    protected $supportedLanguages = null;
    protected $QOMFactory = null;

    public function __construct (\Midgard\PHPCR\Session $session)
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

        return new SQLQuery($this->session, $statement);

        /*if ($this->query == null)
        {
            $this->query = new SQLQuery($this->session, $statement);
        }
        return $this->query; */
    }

    public function getQOMFactory()
    {   
        if ($this->QOMFactory == null)
            $this->QOMFactory = new QOM\QueryObjectModelFactory ($this->session);

        return $this->QOMFactory;
    }

    public function getQuery($node)
    {
        $valid = true;
        try
        {
            $type = $node->getPrimaryNodeType();
            if (!$type || $type->getName() != 'nt:query') {
                $valid = false;
            }
        }
        catch (\PHPCR\PathNotFoundException $e)
        {
            $valid = false;
        }
        if ($valid == false)
        {
            throw new \PHPCR\Query\InvalidQueryException("Invalid node. Expected 'nt:query' type");
        }

        $statement = $node->getPropertyValue('jcr:statement');
        $language = $node->getPropertyValue('jcr:language');

        $query = $this->createQuery($statement, $language);
        $query->setNode($node);
        return $query;
    }

    public function getSupportedQueryLanguages()
    {
        return $this->supportedLanguages;
    }
}
?>
