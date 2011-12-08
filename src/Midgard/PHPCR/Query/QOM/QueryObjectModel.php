<?php

namespace Midgard\PHPCR\Query\QOM;
use Midgard\PHPCR\Session;
use Midgard\PHPCR\Query\SQLQuery;

/**
 * {@inheritDoc}
 */
class QueryObjectModel extends SQLQuery implements \PHPCR\Query\QOM\QueryObjectModelInterface
{
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

    public function getLanguage()
    {
        return \PHPCR\Query\QueryInterface::JCR_JQOM;
    }
}
