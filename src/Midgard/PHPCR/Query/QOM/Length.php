<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Length implements \PHPCR\Query\QOM\LengthInterface
{
    protected $propertyValue = null;

    public function __construct($propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyValue()
    {
        return $this->propertyValue;
    }
}
