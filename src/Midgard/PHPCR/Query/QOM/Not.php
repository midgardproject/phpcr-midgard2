<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Not implements \PHPCR\Query\QOM\NotInterface
{
    protected $constraint = null;

    public function __construct($constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}
