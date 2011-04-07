<?php
namespace Midgard2CR;

class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    protected $propertyName = null;
    protected $node = null;

    public function __construct(Node $node, $propertyName)
    {
        $this->propertyName = $propertyName;
        $this->node = $node;
        parent::__construct($node->getMidgard2Object(), $node, $node->getSession());
    }
    
    private function getMidgard2PropertyName()
    {
        if (substr($this->propertyName, 0, 4) == 'mgd:')
        {
            return substr($this->propertyName, 4);
        }
    }

    public function setValue($value, $type = NULL, $weak = FALSE)
    {
        throw new \PHPCR\RepositoryException("Not allowed");
    }
    
    public function addValue($value)
    {
    }
    
    public function getNativeValue()
    {
        $propertyName = $this->getMidgard2PropertyName();
        return $this->object->$propertyName;
    }
    
    public function getString()
    {
        $propertyName = $this->getMidgard2PropertyName();
        return $this->object->$propertyName;
    }
    
    public function getBinary()
    {
    }
    
    public function getLong()
    {
    }
    
    public function getDouble()
    {
    }
    
    public function getDecimal()
    {
    }
    
    public function getDate()
    {
    }
    
    public function getBoolean()
    {
    }

    public function getName()
    {
        return $this->propertyName;
    }

    public function getNode()
    {
        return $this->node;
    }
    
    public function getProperty()
    {
    }
    
    public function getLength()
    {
    }
    
    public function getLengths()
    {
    }
    
    public function getDefinition()
    {
    }
    
    public function getType()
    {
    }
    
    public function isMultiple()
    {
    }
    
    public function getIterator()
    {
         return new \ArrayIterator(array());
    }
}
