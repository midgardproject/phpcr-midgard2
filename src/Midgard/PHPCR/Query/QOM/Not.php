<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Not extends ConstraintHelper implements \PHPCR\Query\QOM\NotInterface
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
