<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class BindVariableValue implements \PHPCR\Query\QOM\BindVariableValueInterface
{
    protected $name = null;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBindVariableName()
    {
        return $this->name;
    }
}
