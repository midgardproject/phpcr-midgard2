<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class UpperCase implements \PHPCR\Query\QOM\UpperCaseInterface 
{
    protected $operand = null;

    public function __construct($operand)
    {
        $this->operand = $operand;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperand()
    {
        return $this->operand;
    }
}
