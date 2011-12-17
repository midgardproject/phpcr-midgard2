<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Comparison extends ConstraintHelper implements \PHPCR\Query\QOM\ComparisonInterface
{
    protected $operandFirst = null;
    protected $operator = null;
    protected $operandSecond = null;

    public function __construct(\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator,
                \PHPCR\Query\QOM\StaticOperandInterface $operand2)
    {
        $this->operandFirst = $operand1;
        $this->operator = $operator;
        $this->operandSecond = $operand2;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperand1()
    {
        return $this->operandFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperand2()
    {
        return $this->operandSecond;
    }
}
