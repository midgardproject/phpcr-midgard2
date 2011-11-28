<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class QueryObjectModel implements \PHPCR\Query\QOM\QueryObjectModelInterface
{
    public function __construct(\PHPCR\Query\QOM\SourceInterface $source,
        \PHPCR\Query\QOM\ConstraintInterface $constraint = null, array $orderings, array $columns)    
    {

    }

    /**
     * {@inheritDoc}
    */
    public function getSource()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
    */
    public function getConstraint() 
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
    */
    public function getOrderings()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
    */
    public function getColumns()
    {
        throw new \PHPCR\RepositoryException("Not supported"); 
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
