<?php

namespace Midgard\PHPCR\Query\Utils;

class ConstraintManagerChildNode extends ConstraintManager
{
   /* public function __construct(Query\SQLQuery $query, QuerySelectHolder $holder, PHPCR\Query\QOM\ConstraintInterface $iface)
    {
        parent::__construct($query, $holder, $iface);
    }*/

    public function addConstraint()
    {
        /* There's a path specified so we need to find node under parent's path */
        $parentPath = $this->removeQuotes($this->constraintIface->getParentPath());
        $parts = explode("/", $parentPath);
        if (empty($parts))
            return;
        /* Reverse path's elements so we can generate correct joins */
        $parts = array_reverse($parts);
        $currentStorage = null;
        /* For each element in path, add join and constraint with parent's name */
        foreach ($parts as $name) {
            $nodeStorage = new \midgard_query_storage("midgard_node");
            $this->holder->getQuerySelect()->add_join(
                'INNER',
                $currentStorage == null ? new \midgard_query_property('parent') : new \midgard_query_property('parent', $currentStorage),
                new \midgard_query_property('id', $nodeStorage)
            );
            //$name = str_replace('"', '', $name);
            $constraint = new \midgard_query_constraint(
                new \midgard_query_property("name", $nodeStorage),
                "=",
                new \midgard_query_value ($name)
            );
            $this->holder->getDefaultConstraintGroup()->add_constraint($constraint);
            $currentStorage = $nodeStorage;
        }
    }
}
