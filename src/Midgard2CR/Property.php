<?php
namespace Midgard2CR;

class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    protected $propertyFullName = null;
    protected $propertyName = null;
    protected $propertyPrefix = null;
    protected $node = null;
    protected $type = 0;
    protected $isMidgardProperty = false;
    protected $midgardPropertyName = null; 
    protected $manager = null;
    protected $isMultiple = false;

    public function __construct(Node $node, $propertyName, \Midgard2CR\PropertyManager $manager = null)
    {
        $this->propertyFullName = $propertyName;
        $this->node = $node;
        $this->parent = $node;
        $midgard_object = $node->getMidgard2Object();
        $this->manager = $manager;

        /* Check if we get MidgardObject property */
        $nsregistry = $this->node->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();
        $tokens = $nsmanager->getPrefixTokens($propertyName);
        if ($tokens[0] == $nsregistry::MGD_PREFIX_MGD
            && $tokens[1] != null)
        {
            $this->isMidgardProperty = true;
            $this->midgardPropertyName = $tokens[1];
        }

        if ($tokens[1] != null)
        {
            $this->propertyPrefix = $tokens[0];
            $this->propertyName = $tokens[1];
        } 
        else 
        {
            $this->propertyPrefix = 'phpcr:undefined';
            $this->propertyName = $propertyName;
        }

        parent::__construct($midgard_object, $node, $node->getSession());
    }
    
    private function getMidgard2PropertyName()
    {
        if ($this->isMidgardProperty = false)
        {
            return null;
        }
        return $this->midgardPropertyName;
    }

    public function setValue($value, $type = NULL, $weak = FALSE)
    { 
        /* TODO, handle:
         * \PHPCR\ValueFormatException
         * \PHPCR\Version\VersionException 
         * \PHPCR\Lock\LockException
         * \PHPCR\ConstraintViolationException
         * \PHPCR\RepositoryException
         * \InvalidArgumentException
         */ 
        $propertyName = $this->getMidgard2PropertyName();
        if ($propertyName) 
        {
            $this->object->$propertyName = $value;
            return;
        }

        $this->type = $type;
        $property = $this->manager->factory ($this->propertyName, $this->propertyPrefix, $type, $value);
    }
    
    public function addValue($value)
    {
        throw new \PHPCR\RepositoryException("Not allowed");
    }

    public function getValue()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE)
        {
            return $this->getDate();
        }
        else 
        {
            return $this->getNativeValue();
        } 
    }

    public function getNativeValue()
    {
        $propertyName = $this->getMidgard2PropertyName();
        if ($propertyName)
        {
            return $this->object->$propertyName;
        }
 
        $property = $this->manager->getProperty($this->propertyName, $this->propertyPrefix);
        $ret = $property->getLiterals();
        if (empty($ret))
        {
            return null;
        }
        return $ret[0];
    }
    
    public function getString()
    {
        // TODO: Convert
        return $this->getNativeValue();
    }
    
    public function getBinary()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function getLong()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE
            || $type == \PHPCR\PropertyType::BINARY
            || $type == \PHPCR\PropertyType::DECIMAL
            || $type == \PHPCR\PropertyType::NAME
            || $type == \PHPCR\PropertyType::REFERENCE
            || $type == \PHPCR\PropertyType::DOUBLE)
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to LONG."); 
        } 

        return intval($this->getNativeValue());
    }
    
    public function getDouble()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE
            || $type == \PHPCR\PropertyType::BINARY
            || $type == \PHPCR\PropertyType::NAME
            || $type == \PHPCR\PropertyType::REFERENCE) 
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to LONG."); 
        } 

        return floatval($this->getNativeValue());       
    }
    
    public function getDecimal()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function getDate()
    {
        if ($this->getType() != \PHPCR\PropertyType::DATE)
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type FIXME) to DateTime object."); 
        } 
        return new \DateTime($this->getNativeValue());
    }
    
    public function getBoolean()
    {
        return (bool) $this->getNativeValue();
    }

    public function getName()
    {
        return $this->propertyFullName;
    }

    public function getNode()
    {
        $type = $this->getType();
        if ($type != \PHPCR\PropertyType::PATH
            || $type != \PHPCR\PropertyType::REFERENCE
            || $type != \PHPCR\Propertytype::WEAKREFERENCE)
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to Node type.");
        } 

        return $this->node;
    }
    
    public function getProperty()
    {
        $type = $this->getType();
        if ($type != \PHPCR\PropertyType::PATH)
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to PATH type.");
        } 
        
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function getLength()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function getLengths()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    public function getDefinition()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
    
    private function getMGDType ()
    {
        $mrp = new \midgard_reflector_property (get_class($this->object));
        $type = $mrp->get_midgard_type ($this->midgardPropertyName);

        $type_id = 0;

        switch ($type) 
        {
            case \MGD_TYPE_STRING:
            case \MGD_TYPE_LONGTEXT:
                $type_id = \PHPCR\PropertyType::STRING;
                break;

            case \MGD_TYPE_UINT:
            case \MGD_TYPE_INT:
                $type_id = \PHPCR\PropertyType::LONG;
                break;

            case \MGD_TYPE_FLOAT:
                $type_id = \PHPCR\PropertyType::DOUBLE;
                break;

            case \MGD_TYPE_BOOLEAN:
                $type_id = \PHPCR\PropertyType::BOOLEAN;
                break;

            case \MGD_TYPE_TIMESTAMP:
                $type_id = \PHPCR\PropertyType::DATE;
                break;
        }

        $this->type = $type_id;
        return $this->type;
    }

    public function getType()
    {
        if ($this->type > 0)
        {
            return $this->type;
        }

        if ($this->isMidgardProperty)
        {
            return $this->getMGDType();
        }

        $property = $this->manager->getProperty($this->propertyName, $this->propertyPrefix);
        $this->type = \PHPCR\PropertyType::valueFromName($property->model->type);

        return $this->type;
    }
    
    public function isMultiple()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    public function isNode()
    {
        return false;
    }
    
    public function getIterator()
    {
         return new \ArrayIterator(array());
    }

    public function isSame (\PHPCR\ItemInterface $item)
    {
        if (!$item instanceof \PHPCR\PropertyInterface)
        {
            return false;
        }

        if ($item->getName() == $this->getName())
        {
            if ($item->getParent()->isSame($this->getParent()))
            {
                return true;
            } 
        }

        return false;
    }
}
