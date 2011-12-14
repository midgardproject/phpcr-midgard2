<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectHolder;
use Midgard\PHPCR\Utils\NodeMapper;

class QuerySelectHelper
{
    protected $holder = null;

    public function setQuerySelectHolder(QuerySelectHolder $holder)
    {
        $this->holder = $holder;
        $this->holder->setMidgardStorageName(NodeMapper::getMidgardName($this->getNodeTypeName()));
    }

    public function normalizeName($name)
    {
        $name = trim($name);
        if (strpos($name, '[') !== false) {
            return strtr($name, array('[' => '', ']' => ''));
        }
        return $name;
    }

    public function mapJoinType($jcrJoin)
    {
        if ($jcrJoin == 'jcr.join.type.inner')
            return 'INNER';
        if ($jcrJoin == 'jcr.join.type.left.outer')
            return 'LEFT';
        if ($jcrJoin == 'jcr.join.type.right.outer')
            return 'RIGHT';
    }

    public function getNodeTypeName()
    {
        return null;
    }

    public function computeQuerySelectConstraints($holder)
    {
        $this->setQuerySelectHolder($holder);
        return;
    }
}
