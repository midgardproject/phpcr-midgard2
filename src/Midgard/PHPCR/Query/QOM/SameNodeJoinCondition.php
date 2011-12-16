<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class SameNodeJoinCondition extends ConditionHelper implements \PHPCR\Query\QOM\SameNodeJoinConditionInterface
{
    protected $selectorFirst = null;
    protected $selectorSecond = null;
    protected $selectorSecondPath = null;

    public function __construct($selector1Name, $selector2Name, $selector2Path = null)
    {
        $this->selectorFirst = $selector1Name;
        $this->selectorSecond = $selector2Name;
        $this->selectorSecondPath = $selector2Path;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector1Name()
    {
        return $this->selectorFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector2Name()
    {
        return $this->selectorSecond;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector2Path()
    {
        return $this->selectorSecondPath;
    }
}
