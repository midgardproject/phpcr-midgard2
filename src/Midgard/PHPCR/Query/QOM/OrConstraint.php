<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class OrConstraint extends ConstraintHelper implements \PHPCR\Query\QOM\OrInterface
{
    protected $constraintFirst = null;
    protected $constraintSecond = null;

    public function __construct($constraintFirst, $constraintSecond)
    {
        $this->constraintFirst = $constraintFirst;
        $this->constraintSecond = $constraintSecond;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraint1()
    {
        return $this->constraintFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraint2()
    {
        return $this->constraintSecond;
    }
}
