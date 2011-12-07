<?php

namespace Midgard\PHPCR\Query\QOM;
use Midgard\PHPCR\Session;
use Midgard\PHPCR\Query\SQLQuery;

/**
 * {@inheritDoc}
 */
class QueryObjectModel extends SQLQuery
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
}
