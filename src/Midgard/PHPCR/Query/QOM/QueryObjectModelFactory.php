<?php

namespace Midgard\PHPCR\Query\QOM;
use Midgard\PHPCR\Session;

class QueryObjectModelFactory implements \PHPCR\Query\QOM\QueryObjectModelFactoryInterface
{
    private $session = null;

    public function __construct (Session $session) 
    {
        $this->session = $session;
    }

    public function createQuery(\PHPCR\Query\QOM\SourceInterface $source, 
        \PHPCR\Query\QOM\ConstraintInterface $constraint = null, array $orderings = array(), array $columns = array())
    {
        return new QueryObjectModel ($this->session, null, $source, $constraint, $orderings, $columns); 
    }
    
    public function selector($nodeTypeName, $selectorName = null)
    {
        return new Selector($nodeTypeName, $selectorName); 
    }
    
    public function join(\PHPCR\Query\QOM\SourceInterface $left, \PHPCR\Query\QOM\SourceInterface $right,
        $joinType, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return new Join($left, $right, $joinType, $joinCondition); 
    }
    
    public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        return new EquiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name);
    }

    public function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = null)
    {
        return new SameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path); 
    }

    public function childNodeJoinCondition($childSelectorName, $parentSelectorName)
    {
        return new ChildNodeJoinCondition($childSelectorName, $parentSelectorName); 
    }

    public function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName)
    {
        return new DescendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName); 
    }

    public function _and(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
        return $this->andConstraint($constraint1, $constraint2);
    }

    public function andConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    { 
        return new AndConstraint($constraint1, $constraint2);
    }

    public function _or(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
        return $this->orConstraint($constraint1, $constraint2);
    }

    public function orConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
         return new OrConstraint($constraint1, $constraint2); 
    }

    public function not(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        return $this->notConstraint($constraint); 
    }

    public function notConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        return new Not($constraint);
    }

    public function comparison(\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator,
        \PHPCR\Query\QOM\StaticOperandInterface $operand2)
    {
        return new Comparison($operand1, $operator, $operand2); 
    }
    
    public function propertyExistence($propertyName, $selectorName = null)
    {
        return new PropertyExistence($propertyName, $selectorName);
    }

    public  function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName = null)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function sameNode($path, $selectorName = null)
    {
        return new SameNode($path, $selectorName); 
    }

    public function childNode($path, $selectorName = null)
    {
        return new ChildNode($path, $selectorName); 
    }

    public function descendantNode($path, $selectorName = null)
    {
        return new DescendantNode($path, $selectorName); 
    }

    public function propertyValue($propertyName, $selectorName = null)
    {
        return new PropertyValue($propertyName, $selectorName); 
    }

    public function length(\PHPCR\Query\QOM\PropertyValueInterface $propertyValue)
    {
        return new Length($propertyValue); 
    }

    public function nodeName($selectorName = null)
    {
        return new NodeName($selectorName); 
    }

    public function nodeLocalName($selectorName = null)
    {
        return new NodeLocalName($selectorName); 
    }

    public function fullTextSearchScore($selectorName = null)
    {
        return new FullTextSearchScore($selectorName); 
    }

    public function lowerCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new LowerCase($operand); 
    }

    public function upperCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new UpperCase($operand); 
    }

    public function bindVariable($bindVariableName)
    {
        return new BindVariableValue($bindVariableName); 
    }

    public function literal($literalValue)
    {
        return new Literal($literalValue); 
    }

    public function ascending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new Ordering($operand, self::JCR_ORDER_ASCENDING); 
    }

    public function descending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new Ordering($operand, self::JCR_ORDER_DESCENDING);
    }

    public function column($propertyName, $columnName = null, $selectorName = null)
    {
        return new Column($propertyName, $columnName, $selectorName); 
    }
}
