<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class EquiJoinCondition extends ConditionHelper implements \PHPCR\Query\QOM\EquiJoinConditionInterface
{
    protected $selectorFirst = null;
    protected $selectorSecond = null;
    protected $nameFirst = null;
    protected $nameSecond = null;

    public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        $this->selectorFirst = $selector1Name;
        $this->nameFirst = $property1Name;
        $this->selectorSecond = $selector2Name;
        $this->nameSecond = $property2Name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector1Name()
    {
        return $this->selectorFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty1Name()
    {
        return $this->nameFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector2Name()
    {
        return $this->selectorSecond;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty2Name()
    {
        return $this->nameSecond;
    }

    private function findEqualRow($value, array $objects)
    {
        foreach ($objects as $o) 
        {
            if ($o->value == $value) {
                return $o;
            }
        }
        return null;
    }

    public function computeResults(array $selects)
    {
        $rows[0] = $selects[$this->getSelector1Name()];
        $rows[1] = $selects[$this->getSelector2Name()];
      
        $retTwo = $rows[1]->list_objects();
        $i = 0;
        $j = 0;
        $result = array();
        foreach ($rows[0]->list_objects() as $object) 
        {
            $objTwo = $this->findEqualRow($object->value, $retTwo);
            if ($objTwo != null) {
                $result[$j][$this->getSelector1Name()] = $object;
                $result[$j][$this->getSelector2Name()] = $objTwo;
            }
            $i++;
        }
        return $result;
    }
}
