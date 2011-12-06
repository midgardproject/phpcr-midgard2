<?php
namespace Midgard\PHPCR\Query\Utils;

class QuerySelectholder
{
    protected $querySelect = null;
    protected $propertyStorage = null;
    protected $defaultNodeStorage = null;
    protected $defaultConstraintGroup = null;

    public function __construct ()
    {
    }

    public function getDefaultNodeStorage()
    {
        if ($this->defaultNodeStorage == null)
            $this->defaultNodeStorage = new \midgard_query_storage('midgard_node');
        return $this->defaultNodeStorage;
    }

    public function getQuerySelect()
    {
        if ($this->querySelect == null)
            $this->querySelect = new \midgard_query_select($this->getDefaultNodeStorage());
        return $this->querySelect;
    }

    public function getPropertyStorage()
    {
        if ($this->propertyStorage == null)
            $this->propertyStorage = new \midgard_query_storage('midgard_node_property');
        return $this->propertyStorage;
    }

    public function getDefaultConstraintGroup()
    {
        if ($this->defaultConstraintGroup == null)
            $this->defaultConstraintGroup = new \midgard_query_constraint_group("AND");
        return $this->defaultConstraintGroup;
    }
}
