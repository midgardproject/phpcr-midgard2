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

        $property = $this->manager->factory($this->propertyName, $this->propertyPrefix, $type, $value);
        if ($this->type != $type) 
        {
            $this->type = $type;
            $this->manager->setModelType($this->propertyName, $this->propertyPrefix, $type);
        }
    }
    
    public function addValue($value)
    {
        throw new \PHPCR\RepositoryException("Not allowed");
    }

    public function getValue()
    {
        $type = $this->getType();

        switch ($type) 
        {
        case \PHPCR\PropertyType::DATE:
            return $this->getDate();

        case \PHPCR\PropertyType::BINARY:
            return $this->getBinary();

        default:
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

        /* Empty value */
        if (empty($ret))
        {
            return null;
        }

        /* Multivalue */
        if (count($ret) > 1)
        {
            $this->isMultiple = true;
            return $ret;
        }

        /* Single value */
        return $ret[0];
    }

    private function transformValue($func)
    {
        $v = $this->getNativeValue();
        if ($this->isMultiple)
        {
            $va = array();
            foreach ($this->getIterator() as $value)
            {
                $va[] = $func($value);
            }
            return $va;
        }
        return $func($v);
    }

    public function getString()
    {
        // TODO: Convert
        return $this->getNativeValue();
    }
    
    public function getBinary()
    {
        $f = fopen('php://memory', 'rwb+');
        fwrite($f, $this->getNativeValue());
        rewind($f);

        return $f; 
    }
    
    public function getLong()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE
            || $type == \PHPCR\PropertyType::BINARY
            || $type == \PHPCR\PropertyType::DECIMAL
            || $type == \PHPCR\PropertyType::REFERENCE
            || $type == \PHPCR\PropertyType::DOUBLE)
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to LONG."); 
        } 

        return $this->transformValue('intval');
    }
    
    public function getDouble()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE
            || $type == \PHPCR\PropertyType::BINARY
            || $type == \PHPCR\PropertyType::REFERENCE) 
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to DOUBLE."); 
        } 
        
        return $this->transformValue('floatval'); 
    }
    
    public function getDecimal()
    {
        $v = $this->transformValue('floatval');
        $current = setlocale(LC_ALL, '0'); 
        setlocale(LC_ALL, 'C');
        if (is_array($v))
        {
            foreach ($v as $value)
            {
                $ret[] = (string)$value;
            }
        }
        else 
        {
            $ret = (string)$v;
        }
        setlocale(LC_ALL, $current);

        return $ret;
    }
    
    public function getDate()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE
            || $type == \PHPCR\PropertyType::STRING)
        {
            try 
            {
                $date = new \DateTime($this->getNativeValue());
                return $date;
            }
            catch (\Exception $e)
            {
                /* Silently ignore */
            }
        } 
        throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type)  . ") to DateTime object.");
    }
    
    public function getBoolean()
    {
        $v = $this->getNativeValue();
        if ($this->isMultiple)
        {
            $va = array();
            foreach ($this->getIterator() as $value)
            {
                $va[] = (bool) $value;
            }
            return $va;
        }
        return (bool) $v;
    }

    public function getName()
    {
        return $this->propertyFullName;
    }

    public function getNode()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::PATH)
        {
            /* TODO */
            throw new \PHPCR\RepositoryException("Not implemented");
        }

        if ($type == \PHPCR\PropertyType::REFERENCE)
        {
            return $this->parent->getSession()->getNodeByIdentifier($this->getValue());
        }

        if ($type == \PHPCR\Propertytype::WEAKREFERENCE)
        {
            /* TODO */
            throw new \PHPCR\RepositoryException("Not implemented");
        }
    
        throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to Node type."); 

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
        $v = $this->getNativeValue();
        if ($this->type == \PHPCR\PropertyType::BINARY)
        {
            return strlen(base64_decode($v));
        }
        if (is_array($v))
        {
            throw new \PHPCR\ValueFormatException("Can not get multivalue length");
        }
        return strlen($this->getString());
    }
    
    public function getLengths()
    {
        $v = $this->getNativeValue();
        if (is_array($v))
        {
            /* Native values are always strings */
            foreach ($v as $values)
            {
                $ret[] = strlen($values);
            }
            return $ret;
        }
        throw new \PHPCR\ValueFormatException("Can not get lengths of single value");
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
            return (int)$this->type;
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
        $v = $this->getValue();
        return new \ArrayIterator(is_array($v) ? $v : array($v));
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
