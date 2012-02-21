<?php

namespace Midgard\PHPCR\Query\QOM;
use Midgard\PHPCR\Utils\NodeMapper;

/**
 * {@inheritDoc}
 */
class DescendantNodeJoinCondition extends ConditionHelper implements \PHPCR\Query\QOM\DescendantNodeJoinConditionInterface
{
    protected $descendantSelector = null;
    protected $ancestorSelector = null;

    public function __construct($descendantSelectorName, $ancestorSelectorName)
    {
        $this->descendantSelector = $descendantSelectorName;
        $this->ancestorSelector = $ancestorSelectorName;
    }

    public function computeQuerySelectConstraints($holder)
    {
        parent::computeQuerySelectConstraints($holder);
        foreach ($this->holder->getSQLQuery()->getSelectors() as $selector) {
            if ($selector->getSelectorName() == $this->getDescendantSelectorName()) {
                $nodeTypeName = $selector->getNodeTypeName();
                $this->holder->setMidgardStorageName(NodeMapper::getMidgardName($nodeTypeName));
            }
        }

        /* TODO
         * Compute correct node type name.
         *
         * Before QuerySelectData is available in release, we do set descendant node type asdefault one.
         * This one should be compared with defined constraints and columns */
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
        throw new \PHPCR\RepositoryException("Not supported");
    }
}
