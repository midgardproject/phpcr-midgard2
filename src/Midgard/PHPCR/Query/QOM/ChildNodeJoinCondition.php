<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class ChildNodeJoinCondition extends ConditionHelper implements \PHPCR\Query\QOM\ChildNodeJoinConditionInterface
{
    protected $childSelectorName = null;
    protected $parentSelectorName = null;

    public function __construct($childSelectorName, $parentSelectorName)
    {
        $this->childSelectorName = $childSelectorName;
        $this->parentSelectorName = $parentSelectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildSelectorName()
    {
        return $this->childSelectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentSelectorName()
    {
        return $this->parentSelectorName;
    }
}
