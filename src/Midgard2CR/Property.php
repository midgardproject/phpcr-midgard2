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
        return null;
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
        if (!$propertyName)
        {
            $parts = explode(':', $this->propertyName);
            if (count($parts) == 1)
            {
                return $this->object->get_parameter('phpcr:undefined', $parts[0]);
            }
            return $this->object->get_parameter($parts[0], $parts[1]);
        }
        return $this->object->$propertyName;
    }
    
    public function getString()
    {
        // TODO: Convert
        return $this->getNativeValue();
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

    public function isNode()
    {
        return false;
    }
    
    public function getIterator()
    {
         return new \ArrayIterator(array());
    }
}
