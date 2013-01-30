<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Selector extends QuerySelectHelper implements \PHPCR\Query\QOM\SelectorInterface 
{
    protected $nodeTypeName = null;
    protected $name = null;

    public function __construct($nodeTypeName, $name = null)
    {
        $this->nodeTypeName = $nodeTypeName;
        $this->name = $name;
    }

    public function getMidgard2NodeTypeNames()
    {
        return array($this->getNodeTypeName());    
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
