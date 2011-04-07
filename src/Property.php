<?php
namespace Midgard2CR;

class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    protected $propertyName = null;

    public function __construct(\midgard_object $object = null, Session $session, $propertyName)
    {
        $this->propertyName = $propertyName;
        parent::__construct($object, $session);
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
    
    public function getNode()
    {
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
