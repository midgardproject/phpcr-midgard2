<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class ChildNode implements \PHPCR\Query\QOM\ChildNodeInterface
{
    protected $selectorName = null;
    protected $parentPath = null;

    public function __construct($parentPath, $selectorName = null)
    {
        $this->parentPath = $parentPath;
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
    public function getParentPath()
    {
        return $this->parentPath;
    }
}
