<?php
namespace Midgard2CR;

class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    protected $propertyName = null;
    protected $node = null;
    protected $type = 0;
    protected $isMidgardProperty = false;
    protected $midgardPropertyName = null;
    protected $parameter = null;

    public function __construct(Node $node, $propertyName)
    {
        $this->propertyName = $propertyName;
        $this->node = $node;
        $this->parent = $node;
        $midgard_object = $node->getMidgard2Object();
        $param = null;
        $property_name = null;

        /* Check if we get MidgardObject property */
        $nsregistry = $this->node->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();
        $tokens = $nsmanager->getPrefixTokens($this->propertyName);
        if ($tokens[0] == $nsregistry::MGD_PREFIX_MGD
            && $tokens[1] != null)
        {
            $property_name = $tokens[1];
            $this->isMidgardProperty = true;
            $this->midgardPropertyName = $property_name;
        }

        if (!$property_name) 
        {
            $params = array();
            if ($tokens[0] != null 
                && $tokens[1] != null)
            {
                $params = $midgard_object->find_parameters(array("domain" => $tokens[0], "name" => $tokens[1]));
            }
            else 
            {
                $params = $midgard_object->find_parameters(array("domain" => "phpcr:undefined", "name" => $this->propertyName));
            }
            if (!empty($params))
            { 
                $this->parameter = $params[0];
            }

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
        throw new \PHPCR\RepositoryException("Not allowed");
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
        if (!$propertyName)
        {
            $parts = explode(':', $this->propertyName);
            if (count($parts) == 1)
            {
                return $this->object->get_parameter('phpcr:undefined', $parts[0]);
            }
            return $this->object->get_parameter($parts[0], $parts[1]);
        }
        if ($this->parameter)
        {   
            return $this->node->object->$propertyName;
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
        return $this->propertyName;
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

    private function getJCRType($type)
    {
        switch ($type) 
        {
            case 'created':
            case 'lastModified':
                return \PHPCR\PropertyType::DATE;
        }

        return 0;
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

        $parts = explode(':', $this->propertyName);
        if (count($parts) == 1)
        {
            /* Try paramater which provides property type */
            $pValue = null;
            if ($this->parameter != null)
            {
                $pValue = $this->parameter->get_parameter('sv', 'type');
            }
            if (!$pValue)
            {
                throw new \PHPCR\RepositoryException("Unhandled type of property '{$this->propertyName}'"); 
            }
 
            $this->type = \PHPCR\PropertyType::valueFromName($pValue);
            /* HACK HACK HACK, in my case, PHPUnit (3.5.12) consumes the whole memory when I return reference type here */
            if ($this->type == \PHPCR\PropertyType::REFERENCE)
            {
                echo "REFERENCE type HACK\n";
                $this->type = 0;
            }
            return $this->type;
        }

        switch ($parts[0]) 
        {
            case 'jcr':
                $this->type = $this->getJCRType($parts[1]);
                break;

            default:
                throw new \PHPCR\RepositoryException("Unhandled type of namespaced property '{$this->propertyName}'");
        }

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
