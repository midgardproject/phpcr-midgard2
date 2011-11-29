<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class SameNode implements \PHPCR\Query\QOM\SameNodeInterface
{
    protected $selectorName = null;
    protected $path = null;

    public function __construct($path, $selectorName = null)
    {
        $this->path = $path;
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
    public function getPath()
    {
        return $this->path;
    }
}
