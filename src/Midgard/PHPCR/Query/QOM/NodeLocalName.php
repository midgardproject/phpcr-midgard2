<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class NodeLocalName implements \PHPCR\Query\QOM\NodeLocalNameInterface
{
    protected $selectorName = null;

    public function __construct($selectorName)
    {
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }
}
