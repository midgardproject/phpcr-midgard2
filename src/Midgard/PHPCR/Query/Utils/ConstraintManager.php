<?php
namespace Midgard\PHPCR\Query\Utils;
use Midgard\PHPCR\Query\SQLQuery;

class ConstraintManager
{
    protected $query = null;
    protected $constraintIface = null;
    protected $holder = null;

    public function __construct (SQLQuery $query, QuerySelectHolder $holder, \PHPCR\Query\QOM\ConstraintInterface $iface)
    {
        $this->query = $query;
        $this->constraintIface = $iface;
        $this->holder = $holder;
    }

    public function addConstraint()
    {
        return;
    }

    public function removeQuotes($value)
    {
        return str_replace('"', '', $value);        
    }
}
