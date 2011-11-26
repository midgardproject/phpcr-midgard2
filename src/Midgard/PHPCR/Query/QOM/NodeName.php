<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class NodeName implements \PHPCR\Query\QOM\NodeNameInterface
{
    protected $selectorName = null;

    public function __construct($name)
    {
        $this->selectorName = $name;
    }

    /**
     * {@inheritDoc}
     */ 
    public function getSelectorName()
    {
        return $this->selectorName;
    }
}
