<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class EquiJoinCondition implements \PHPCR\Query\QOM\EquiJoinConditionInterface
{
    protected $selectorFirst = null;
    protected $selectorSecond = null;
    protected $nameFirst = null;
    protected $nameSecond = null;

    public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        $this->selectorFirst = $selector1Name;
        $this->nameFirst = $property1Name;
        $this->selectorSecond = $selector2Name;
        $this->nameSecond = $property2Name;
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
    public function getProperty1Name()
    {
        return $this->nameFirst;
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
    public function getProperty2Name()
    {
        return $this->nameSecond;
    }
}
