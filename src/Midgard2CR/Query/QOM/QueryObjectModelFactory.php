<?php

namespace Midgard\PHPCR\Query\QOM;

class QueryObjectModelFactory implements \PHPCR\Query\QOM\QueryObjectModelFactoryInterface
{
    public function createQuery(\PHPCR\Query\QOM\SourceInterface $source, $constraint, array $orderings, array $columns)
    {

    }
    
    public function selector($nodeTypeName, $selectorName = null)
    {

    }
    
    public function join(\PHPCR\Query\QOM\SourceInterface $left, \PHPCR\Query\QOM\SourceInterface $right,
        $joinType, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {

    }
    
    public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name)
    {

    }

    public function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = null)
    {

    }

    public function childNodeJoinCondition($childSelectorName, $parentSelectorName)
    {

    }

    public function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName)
    {

    }

    public function _and(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {

    }


    public function _or(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {

    }

    public function not(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {

    }

    public function comparison(\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator,
        \PHPCR\Query\QOM\StaticOperandInterface $operand2)
    {

    }
    
    public function propertyExistence($propertyName, $selectorName = null)
    {

    }

    public  function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName = null)
    {

    }

    public function sameNode($path, $selectorName = null)
    {

    }

    public function childNode($path, $selectorName = null)
    {

    }


    public function descendantNode($path, $selectorName = null)
    {

    }

    public function propertyValue($propertyName, $selectorName = null)
    {

    }

    public function length(\PHPCR\Query\QOM\PropertyValueInterface $propertyValue)
    {

    }

    public function nodeName($selectorName = null)
    {

    }

    public function nodeLocalName($selectorName = null)
    {

    }

    public function fullTextSearchScore($selectorName = null)
    {

    }

    public function lowerCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {

    }

    public function upperCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {

    }

    public function bindVariable($bindVariableName)
    {

    }

    public function literal($literalValue)
    {

    }

    public function ascending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {

    }

    public function descending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {

    }

    public function column($propertyName, $columnName = null, $selectorName = null)
    {

    }
}
