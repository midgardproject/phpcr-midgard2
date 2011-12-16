<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class ChildNode extends ConstraintHelper implements \PHPCR\Query\QOM\ChildNodeInterface
{
    protected $selectorName = null;
    protected $parentPath = null;

    public function __construct($parentPath, $selectorName = null)
    {
        $this->parentPath = $parentPath;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentPath()
    {
        return $this->parentPath;
    }

    public function getMidgardConstraints($selectorName, \midgard_query_select $qs, \midgard_query_storage $nodeStorage)
    {
        /* Do not add constraint for unknown selector */
        if ($this->getSelectorName() != $selectorName)
        {
            return;
        }

        /* There's a path specified so we need to find node under parent's path */
        $parentPath = $this->removeQuotes($this->getParentPath());
        $parts = explode("/", $parentPath);
        if (empty($parts)) {
            return;
        }

        /* Reverse path's elements so we can generate correct joins */
        $parts = array_reverse($parts);

        $constraints = array();
        /* For each element in path, add join and constraint with parent's name */
        foreach ($parts as $name) {
            $currentStorage = new \midgard_query_storage("midgard_node");
            $qs->add_join(
                'INNER',
                new \midgard_query_property('parent', $nodeStorage),
                new \midgard_query_property('id', $currentStorage)
            );

            $constraints[] = new \midgard_query_constraint(
                new \midgard_query_property("name", $currentStorage),
                "=",
                new \midgard_query_value ($name)
            ); 
            $nodeStorage = $currentStorage;
        }

        return $constraints;
    }
}
