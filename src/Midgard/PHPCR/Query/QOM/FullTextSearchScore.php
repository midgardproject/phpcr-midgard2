<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class FullTextSearchScore extends ConstraintHelper implements \PHPCR\Query\QOM\FullTextSearchScoreInterface
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
