<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Literal implements \PHPCR\Query\QOM\StaticOperandInterface
{
    protected $value = null;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getLiteralValue()
    {
        return $this->value;
    }
}
