<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class QueryObjectModel implements \PHPCR\Query\QOM\QueryObjectModelInterface
{
    protected $source = null;
    protected $constraint = null;
    protected $orderings = null;
    protected $columns = null;

    public function __construct(\PHPCR\Query\QOM\SourceInterface $source,
        \PHPCR\Query\QOM\ConstraintInterface $constraint = null, array $orderings, array $columns)    
    {
        $this->source = $source;
        $this->constraint = $constraint;
        $this->orderings = $orderings;
        $this->columns = $columns;
    }

    /**
     * {@inheritDoc}
    */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
    */
    public function getConstraint() 
    {
        return $this->constraint;
    }

    /**
     * {@inheritDoc}
    */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * {@inheritDoc}
    */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * {@inheritDoc}
     */
    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function setLimit($limit)
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function setOffset($offset)
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function getStatement()
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguage()
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function getStoredQuerypath()
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }

    /**
     * {@inheritDoc}
     */
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
    }
}
