<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Join extends QuerySelectHelper implements \PHPCR\Query\QOM\JoinInterface
{
    protected $leftSource = null;
    protected $rightSource = null;
    protected $joinType = null;
    protected $condition = null;

    public function __construct(\PHPCR\Query\QOM\SourceInterface $left, \PHPCR\Query\QOM\SourceInterface $right,
            $joinType, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        $this->leftSource = $left;
        $this->rightSource = $right;
        $this->joinType = $joinType;
        $this->condition = $joinCondition;
    }

    public function getNodeTypeName()
    {
        return $this->getLeft()->getNodeTypeName();
    }

    public function computeQuerySelectConstraints($holder)
    {
        parent::computeQuerySelectConstraints($holder);
        /* FIXME */
        //echo get_class($this->getJoinCondition());
        if ($this->getJoinCondition() instanceOf SameNodeJoinCondition)
            return;

        return;

        $qs = $this->holder->getQuerySelect();
        $leftPropertyStorage = new \midgard_query_storage("midgard_node_property");
        $rightPropertyStorage = new \midgard_query_storage("midgard_node_property");
        //$rightStorage = new \midgard_query_storage(NodeMapper::getMidgardName($this->source->getRight()->getNodeTypeName()));

        /* Join midgard_node and midgard_node_property */
        $qs->add_join(
            'INNER',
            new \midgard_query_property('id'),
            new \midgard_query_property('parent', $leftPropertyStorage)
        );

        /* Join midgard_node_property and midgard_node_property */
        $qs->add_join(
            self::mapJoinType($this->getJoinType()),
            new \midgard_query_property('value', $leftPropertyStorage),
            new \midgard_query_property('value', $rightPropertyStorage)
        ); 

        /* Add implicit constraints: midgard_node_property.name = $val  */        
        $cg = $this->holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('title', $leftPropertyStorage),
                '=',
                new \midgard_query_value(
                    self::normalizeName($this->getJoinCondition()->getProperty1Name())
                )
            )
        );
        $cg->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('title', $rightPropertyStorage),
                '=',
                new \midgard_query_value(
                    self::normalizeName($this->getJoinCondition()->getProperty2Name())
                )
            )
        );
    } 

    /**
     * {@inheritDoc}
     */
    public function getLeft()
    {
        return $this->leftSource;
    }

    /**
     * {@inheritDoc}
     */
    public function getRight()
    {
        return $this->rightSource;
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinCondition()
    {
        return $this->condition;
    }
}
