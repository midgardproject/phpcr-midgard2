<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class PropertyValue implements \PHPCR\Query\QOM\PropertyValueInterface 
{
    protected $propertyName = null;
    protected $selectorName = null;

    public function __construct($propertyName, $selectorName = null)
    {
        $this->propertyName = $propertyName;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
