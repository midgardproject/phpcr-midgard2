<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class PropertyExistence extends ConstraintHelper implements \PHPCR\Query\QOM\PropertyExistenceInterface
{
    protected $selectorName = null;
    protected $propertyName = null;

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
