<?php
namespace Midgard\PHPCR\Query\Utils;
use Midgard\PHPCR\Query\SQLQuery;

class ConstraintManagerBuilder
{
    public static function factory(SQLQuery $query, QuerySelectHolder $holder, \PHPCR\Query\QOM\ConstraintInterface $iface = null)
    {
        if ($iface == null) {
            return null;
        }

        $ifaceName = trim(strrchr((get_class($iface)), '\\'), '\\');
        $className = 'Midgard\PHPCR\Query\Utils\ConstraintManager' . $ifaceName; 
        return new $className($query, $holder, $iface);
    } 
}
