<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class DescendantNode implements \PHPCR\Query\QOM\DescendantNodeInterface
{
    protected $selectorName = null;
    protected $ancestorPath = null;

    public function __construct($path, $selectorName = null)
    {
        $this->ancestorPath = $path;
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
    public function getAncestorPath()
    {
        return $this->ancestorPath;
    }
}
