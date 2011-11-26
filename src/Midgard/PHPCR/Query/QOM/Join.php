<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Join implements  \PHPCR\Query\QOM\JoinInterface
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
