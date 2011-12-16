<?php
namespace Midgard\PHPCR\Query\QOM;

class ConstraintHelper
{
    public function getMidgardConstraints($selectorName, \midgard_query_select $qs, \midgard_query_storage $nodeStorage)
    {
        return;
    }

    public function removeQuotes($value)
    {
        return str_replace('"', '', $value);        
    }
}
