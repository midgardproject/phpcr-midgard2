<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Selector implements \PHPCR\Query\QOM\SelectorInterface 
{
    protected $nodeTypeName = null;
    protected $name = null;

    public function __construct($nodeTypeName, $name = null)
    {
        $this->nodeTypeName = $nodeTypeName;
        $this->name = $name;
    }
    /**
     * {@inheritDoc}
     */
    public function getNodeTypeName()
    {
        return $this->nodeTypeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectorName()
    {
        return $this->name;
    }
}
