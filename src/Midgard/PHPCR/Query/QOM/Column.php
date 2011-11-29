<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Column implements \PHPCR\Query\QOM\ColumnInterface
{
    protected $selectorName = null;
    protected $propertyName = null;
    protected $columnName = null;

    public function __construct($propertyName, $columnName = null, $selectorName = null)
    {
        $this->propertyName = $propertyName;
        $this->columnName = $columnName;
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

    /**
     * {@inheritDoc}
     */
    public function getColumnName()
    {
        return $this->columnName;
    }
}
