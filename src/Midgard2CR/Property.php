<?php
namespace Midgard2CR;
require_once 'Value.php';

class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    protected $propertyFullName = null;
    protected $propertyName = null;
    protected $propertyPrefix = null;
    protected $type = \PHPCR\PropertyType::UNDEFINED;
    protected $isMidgardProperty;
    protected $midgardPropertyName = null; 
    protected $isMultiple = false;
    protected $midgardPropertyNodes = null;

    public function __construct(Node $node, $propertyName, array $nodeProperties = null)
    {
        $this->propertyFullName = $propertyName; 
        $this->midgardNode = $node->getMidgard2Node(); 
        $this->midgardPropertyNodes = $nodeProperties;
        if (!empty($nodeProperties))
        {
            if ($nodeProperties[0]->guid)
            {
                $this->is_new = false;
            }
            $this->contentObject = $nodeProperties[0];
        }
        $this->parent = $node;
        $this->isMidgardProperty = false;
        $this->session = $node->session;

        /* Check if we get MidgardObject property */
        $nsregistry = $this->parent->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();
        $tokens = $nsmanager->getPrefixTokens($propertyName);
        if ($tokens[0] == $nsregistry::MGD_PREFIX_MGD
            && $tokens[1] != null)
        {
            $this->isMidgardProperty = true;
            $this->midgardPropertyName = $tokens[1];
        }

        /* Check namespace by convention.
         * ns:name is represented as ns-name in Midgard2 */
        $GNsProperty = str_replace(':', '-', $propertyName);
        if (property_exists($this->parent->getMidgard2ContentObject(), $GNsProperty))
        {
            $this->isMidgardProperty = true;
            $this->midgardPropertyName = $GNsProperty;
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
    }

    public function getParentNode()
    {
        return $this->parent;
    }

    public function getMidgard2PropertyName()
    {
        if ($this->isMidgardProperty == false)
        {
            return null;
        }
        return $this->midgardPropertyName;
    }

    private function determineType(&$value)
    {
        if (is_long($value))
        {
            return \PHPCR\PropertyType::LONG;
        }

        if (is_double($value))
        {
            return \PHPCR\PropertyType::DOUBLE;
        }

        if (is_string($value))
        {
            return \PHPCR\PropertyType::STRING;
        }

        if (is_array($value))
        {
            return self::determineType($value[0]);
        }
    }

    private function setMidgard2NodePropertyValue($propertyNode = null, $value, $type)
    {
        if ($propertyNode == null)
        {
            $propertyNode = new \midgard_node_property();
            $propertyNode->title = $this->getName();
            $propertyNode->parent = $this->parent->getMidgard2Node()->id;
            $propertyNode->parentguid = $this->parent->getMidgard2Node()->guid;
            $propertyNode->type = $type;
            $this->midgardPropertyNodes[] = $propertyNode;
        }

        $propertyNode->value = ValueFactory::transformValue($value, $type, \PHPCR\PropertyType::STRING);

        $this->is_new = true;
        $this->is_modified = false;

        if ($propertyNode->guid)
        { 
            $this->is_modified = true;
            $this->is_new = false;
        }
    }

    public function setValue($value, $type = null, $weak = FALSE)
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
            $this->contentObject->$propertyName = $value;
            return;
        }

        /*
         * The type detection follows PropertyType::determineType. 
         * Thus, passing a Node object without an explicit type (REFERENCE or WEAKREFERENCE) will create a REFERENCE property. 
         * If the specified node is not referenceable, a ValueFormatException is thrown.
         */ 
        if (is_a($value, '\Midgard2CR\Node'))
        {
            if (!$value->isReferenceable())
            {
                throw new \PHPCR\ValueFormatException("Node " . $value->getPath() . " is not referencable"); 
            }

            if ($type == null)
            {
                $type = 'Reference';
            }

            $new_value = $value->getProperty('jcr:uuid')->getString();
        }
        else if (is_a($value, '\DateTime'))
        {
            $new_value = $value->format("c");
        }
        else if (is_a($value, '\Midgard2CR\Property'))
        {
            $new_value = $value->getString(); 
        }
        else 
        {
            $new_value = $value;
        }

        if ($type == null)
        {
            if ($this->parent->hasProperty($this->getName()))
            {
                $type = $this->getType();
            }

            if ($type == null)
            {
                $type = self::determineType($new_value);
            }
        }

        if (is_array($new_value))
        {
            $i = 0;
            foreach($new_value as $v)
            {
                if (isset($this->midgardPropertyNodes[$i]))
                {
                    $this->setMidgard2NodePropertyValue($this->midgardPropertyNodes[$i], $v, $type);
                }
                else 
                {
                    $this->setMidgard2NodePropertyValue(null, $v, $type);
                }
                $i++;
            }
        }
        else 
        {
            $this->setMidgard2NodePropertyValue(empty($this->midgardPropertyNodes) ? null : $this->midgardPropertyNodes[0], $new_value, $type);
        }

        $this->type = $type;
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

        case \PHPCR\PropertyType::REFERENCE:
        case \PHPCR\PropertyType::WEAKREFERENCE:
            return $this->getNode();

        default:
            return $this->getNativeValue();
        } 
    }

    public function getNativeValue()
    {
        if ($this->type == \PHPCR\PropertyType::BINARY)
        {
            return $this->getBinary();
        } 

        $propertyName = $this->getMidgard2PropertyName();
        if ($propertyName)
        {
            return $this->contentObject->$propertyName;
        }

        if (!empty($this->midgardPropertyNodes))
        {
            if (count($this->midgardPropertyNodes) == 1)
            {
                return $this->midgardPropertyNodes[0]->value;
            } 
            else 
            {
                foreach ($this->midgardPropertyNodes as $np)
                {
                    $ret[] = $np->value;
                }
            }
        }

        /* Empty value */
        if (empty($ret))
        {
            return null;
        }

        /* Multivalue */
        if (is_array($ret) && count($ret) > 1)
        {
            $this->isMultiple = true;
            return $ret;
        }

        /* Single value */
        return $ret;
    }

    public function getString()
    {
        $type = $this->getType();
        return ValueFactory::transformValue($this->getNativeValue(), $type, \PHPCR\PropertyType::STRING);
    }
    
    public function getBinary()
    {
        if ($this->getType() != \PHPCR\PropertyType::BINARY)
        {
            $sv = new StringValue();
            return ValueFactory::transformValue($this->getNativeValue(), $this->type, \PHPCR\PropertyType::BINARY);
        }

        $ret = array();
        $attachments = $this->midgardPropertyNodes[0]->list_attachments();
        if (empty($attachments))
        {
            return null;
        }

        $name = $this->getName();
        foreach ($attachments as $att)
        {
            if ($name == $att->name)
            {
                $blob = new \midgard_blob($att);
                $ret[] = $blob->get_handler('r');
            }
        }

        /* Remove this, once we provide multiple flag in model */
        if (count($ret) > 1)
        {
            $this->isMultiple = true;
        }

        return count($ret) == 1 ? $ret[0] : $ret;
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
        
        return ValueFactory::transformValue($this->getNativeValue(), $type, \PHPCR\PropertyType::LONG);
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

        return ValueFactory::transformValue($this->getNativeValue(), $type, \PHPCR\PropertyType::DOUBLE);
    }
    
    public function getDecimal()
    {
        $type = $this->getType();
        return ValueFactory::transformValue($this->getNativeValue(), $type, \PHPCR\PropertyType::DECIMAL);
    }
    
    public function getDate()
    {
        $type = $this->getType();
        if ($type == \PHPCR\PropertyType::DATE
            || $type == \PHPCR\PropertyType::STRING)
        {
            try 
            {
                $v = $this->getNativeValue();
                if (is_array($v))
                {
                    foreach ($v as $value)
                    {
                        $ret[] = new \DateTime($value);
                    }
                    return $ret;
                }
                if ($v instanceof \DateTime)
                {
                    $date = $v;
                }
                else 
                {
                    $date = new \DateTime($this->getNativeValue());
                }
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
        $type = $this->getType();
        return ValueFactory::transformValue($this->getNativeValue(), $type, \PHPCR\PropertyType::BOOLEAN);
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
            $path = $this->getNativeValue();
            if (is_array($path))
            {
                throw new \PHPCR\RepositoryException("Path array not implemented");
            }
            /* TODO, handle /./ path */
            if (strpos($path, ".") == false)
            {
                try 
                {
                    $node = $this->parent->getNode($path);
                    return $node;
                }
                catch (\PHPCR\PathNotFoundException $e)
                {
                    throw new \PHPCR\ItemNotFoundException($e->getMessage());
                }
            }
            /* TODO */
            throw new \PHPCR\RepositoryException("Not implemented");
        }

        if ($type == \PHPCR\PropertyType::REFERENCE
            || $type == \PHPCR\PropertyType::WEAKREFERENCE)
        {
            try {
                $v = $this->getNativeValue();
                if (is_array($v))
                {
                    foreach ($v as $id)
                    {
                        $ret[] = $this->parent->getSession()->getNodeByIdentifier($id);
                    } 

                    return $ret;
                } 
                return $this->parent->getSession()->getNodeByIdentifier($v);
            }
            catch (\PHPCR\PathNotFoundException $e)
            {
                    throw new \PHPCR\ItemNotFoundException($e->getMessage());
            }
        }
   
        throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to Node type."); 

        return $this->parent;
    }
    
    public function getProperty()
    {
        $type = $this->getType();
        if ($type != \PHPCR\PropertyType::PATH)
        {
            throw new \PHPCR\ValueFormatException("Can not convert {$this->propertyName} (of type " . \PHPCR\PropertyType::nameFromValue($type) . ") to PATH type.");
        } 

        $path = $this->getValue();
        if (is_array($path))
        {
            foreach ($path as $v)
            {
                $ret[] = $this->parent->getProperty($v);
            }
            return $ret;
        }

        return $this->parent->getProperty($path);
    }
    
    public function getLength()
    {
        $v = $this->getNativeValue();
        if (is_array($v))
        {
            return $this->getLengths();
        }

        if ($this->type === \PHPCR\PropertyType::BINARY)
        {
            $stat = fstat($v);
            return $stat['size'];
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
                if ($this->type == \PHPCR\PropertyType::BINARY)
                {
                    $stat = fstat($values);
                    $ret[] = $stat['size'];
                    continue;
                }
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
        return $this->getMidgard2ValueType();
    }

    public function getMidgard2ValueType()
    {
        if (!class_exists('\midgard_reflector_property'))
        {
            return null;
        }
        $mrp = new \midgard_reflector_property (get_class($this->contentObject));
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

        if ($this->isMidgardProperty == true)
        {
            return $this->getMGDType();
        }

        if (!empty($this->midgardPropertyNodes))
        {
            $this->type = $this->midgardPropertyNodes[0]->type;
        }

        return $this->type;
    }
    
    public function isMultiple()
    {
        if ($this->isMidgardProperty)
        {
            return false;
        }

        if ($this->nodeProperty)
        {
            return $this->nodeProperty->multiple;
        }
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

    public function save()
    {
        $pnodes = $this->midgardPropertyNodes;
        if (empty($pnodes))
        {
            return;
        }
        foreach ($pnodes as $mpn)
        {
            if ($this->isNew())
            {
                $mpn->parent = $this->parent->getMidgard2Node()->id;
                $mpn->parentguid = $this->parent->getMidgard2Node()->guid;
                $mpn->create();
            }
            else if ($this->isModified())
            {
                $mpn->update();
            }
        }
        $this->is_new = false;
        $this->is_modified = false;
    }

    public function remove()
    {
        $this->parent->setProperty($this->getName(), null);
    }
}
