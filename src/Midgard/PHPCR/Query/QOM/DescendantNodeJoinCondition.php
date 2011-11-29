<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class DescendantNodeJoinCondition implements \PHPCR\Query\QOM\DescendantNodeJoinConditionInterface
{
    protected $descendantSelector = null;
    protected $ancestorSelector = null;

    public function __construct($descendantSelectorName, $ancestorSelectorName)
    {
        $this->descendantSelector = $descendantSelectorName;
        $this->ancestorSelector = $ancestorSelectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescendantSelectorName()
    {
        return $this->descendantSelector;
    }

    /**
     * {@inheritDoc}
     */
    public function getAncestorSelectorName()
    {
        return $this->ancestorSelector;
    }

    /**
     * {@inheritDoc}
     */
    public function getLeft()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function getRight()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinType()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinCondition()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }
}
