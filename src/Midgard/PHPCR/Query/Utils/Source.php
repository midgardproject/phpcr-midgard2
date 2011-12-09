<?php

namespace Midgard\PHPCR\Query\Utils;
use Midgard\PHPCR\Utils\NodeMapper;

class Source
{
    protected $holder = null;
    protected $source = null;
    protected $nodeTypeName = null;
    protected $isJoin = false;

    public function __construct($holder, $source)
    {
        $this->holder = $holder;
        $this->source = $source;
        if ($source instanceOf \PHPCR\Query\QOM\EquiJoinInterface) {
            $this->nodeTypeName = $source->getLeft()->getNodeTypeName();
            $this->isJoin = true;
        } else if ($source instanceOf \PHPCR\Query\QOM\SelectorInterface) {
            $this->nodeTypeName = $source->getNodeTypeName();
        } else {
            /* FIXME */
            return;
        }
        $this->holder->setMidgardStorageName(NodeMapper::getMidgardName($this->nodeTypeName));
        $this->addImplicitJoin();
    }

    public function getNodeTypeName()
    {
        return $this->nodeTypeName;
    }

    private function getJoinType($jcrJoin)
    {
        if ($jcrJoin == 'jcr.join.type.inner')
            return 'INNER';
    }

    private function normalizeName($name)
    {
        $name = trim($name);
        if (strpos($name, '[') !== false) {
            return strtr($name, array('[' => '', ']' => ''));
        }
        return $name;
    }

    private function addImplicitJoin()
    {
        if ($this->isJoin == false) {
            return;
        }
      
        $qs = $this->holder->getQuerySelect();
        $leftPropertyStorage = new \midgard_query_storage("midgard_node_property");
        $rightPropertyStorage = new \midgard_query_storage("midgard_node_property");
        //$rightStorage = new \midgard_query_storage(NodeMapper::getMidgardName($this->source->getRight()->getNodeTypeName()));

        /* Join midgard_node and midgard_node_property */
        $qs->add_join(
            'INNER',
            new \midgard_query_property('id'),
            new \midgard_query_property('parent', $leftPropertyStorage)
        );

        /* Join midgard_node_property and midgard_node_property */
        $qs->add_join(
            self::getJoinType($this->source->getJoinType()),
            new \midgard_query_property('value', $leftPropertyStorage),
            new \midgard_query_property('value', $rightPropertyStorage)
        ); 

        /* Add implicit constraints: midgard_node_property.name = $val  */        
        $cg = $this->holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('title', $leftPropertyStorage),
                '=',
                new \midgard_query_value(
                    self::normalizeName($this->source->getJoinCondition()->getProperty1Name())
                )
            )
        );
        $cg->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('title', $rightPropertyStorage),
                '=',
                new \midgard_query_value(
                    self::normalizeName($this->source->getJoinCondition()->getProperty2Name())
                )
            )
        );
    }
}
