<?php

namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use \midgard_query_storage;
use \midgard_query_select;
use \midgard_query_property;
use \midgard_query_value;
use \midgard_query_constraint;
use \midgard_query_constraint_group;

class SQLQuerySelector 
{
    private $holder = null;
    private $SQLQuery = null;

    public function __construct(SQLQuery $query, $holder)
    {
        $this->SQLQuery = $query;
        $this->holder = $holder;
    }

    private function computeResult()
    {
        $selects = array();
        foreach ($this->SQLQuery->getSelectors() as $selector) 
        {
            $propertyStorage = $this->holder->getPropertyStorage();
            $qs = new midgard_query_select($propertyStorage);
            $nodeStorage = new midgard_query_storage("midgard_node");
            $qs->add_join(
                "INNER",
                new midgard_query_property("parent"),
                new midgard_query_property("id", $nodeStorage)
            );

            $selectorName = $selector->getSelectorName();

            $properties = array();
            foreach ($this->SQLQuery->getColumns() as $column) 
            {
                if ($selectorName != $column->getSelectorName()) {
                    continue;
                }

                $midgardName = NodeMapper::getMidgardPropertyName(str_replace(array('[', ']'), '', $column->getPropertyName())); 
                $properties[$midgardName]['columnName'] = $column->getColumnName();
                $properties[$midgardName]['propertyName'] = $column->getPropertyName();
            }
            $selects[$selectorName]['properties'] = $properties;

            $defaultCG = new midgard_query_constraint_group("AND");
            $defaultCG->add_constraint(
                new midgard_query_constraint(
                    new midgard_query_property("typename", $nodeStorage),
                    "=",
                    new midgard_query_value(NodeMapper::getMidgardName($selector->getNodeTypeName()))
                )
            );

            $addOR = false;
            $cg = $defaultCG;
            if (count($properties) > 1) {
                $cg = new midgard_query_constraint_group("OR");
                $addOR = true;
            }

            foreach ($properties as $name => $v) 
            {
                $cg->add_constraint(
                    new midgard_query_constraint(
                        new midgard_query_property("name"), 
                        "=",
                        new midgard_query_value($name)
                    )
                ); 
     
                if ($addOR == true) {
                    $defaultCG->add_constraint($cg);
                }
            }

            $constraints = $this->SQLQuery->getConstraint()->getMidgardConstraints($selectorName, $qs, $nodeStorage);
            if (!empty($constraints)) {
                foreach ($constraints as $c) 
                {
                    $defaultCG->add_constraint($c);
                }
            }

            $qs->set_constraint($defaultCG);
            $selects[$selectorName]['QuerySelect'] = $qs;
        }

        foreach ($selects as $name => $v) 
        {
            \midgard_connection::get_instance()->set_loglevel("debug");
            $v['QuerySelect']->execute();
            \midgard_connection::get_instance()->set_loglevel("warn");
        }

        return $this->SQLQuery->getSource()->getJoinCondition()->computeResults($selects);
    }

    private function computeEquiJoinCondition(QOM\JoinCondition $joinCondition, array $selects)
    {
        
    }

    public function getQueryResult()
    {
        $ret = $this->computeResult();
        print_r($ret);
    }
}

?>
