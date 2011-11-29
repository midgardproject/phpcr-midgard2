<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class LowerCase implements \PHPCR\Query\QOM\LowerCaseInterface 
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
