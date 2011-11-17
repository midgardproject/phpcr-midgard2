<?php
namespace Midgard\PHPCR;

use Midgard\PHPCR\Utils\NodeMapper;
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

    public function __construct(Node $node, $propertyName)
    {
        $this->propertyFullName = $propertyName; 
        $this->midgardNode = $node->getMidgard2Node();
        $pNodes = $node->getMidgardPropertyNodes($propertyName); 
        if (!empty($pNodes))
        {
            $this->contentObject = $pNodes[0];
            if ($this->contentObject->guid)
            {
                $this->is_new = false;
            }
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
        $GNsProperty = NodeMapper::getMidgardPropertyName($propertyName);
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

    public function getMidgardPropertyNodes()
    {
        return $this->parent->getMidgardPropertyNodes($this->getName());
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

        if (is_bool($value))
        {
            return \PHPCR\PropertyType::BOOLEAN;
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
            $this->parent->setMidgardPropertyNode($this->getName(), $propertyNode);
        }

        if (!$propertyNode->guid) {
            $this->is_new = true;
            $this->is_modified = false;
        } else {
            $this->is_modified = true;
            $this->is_new = false;
        }

        $propertyNode->value = ValueFactory::transformValue($value, $type, \PHPCR\PropertyType::STRING);
        $propertyNode->type = $type;
    }

    public function validateValue($value, $type)
    {
        /*
        if (is_array($value) && !$this->isMultiple()) {
xdebug_print_function_stack();
            throw new \PHPCR\ValueFormatException("Attempted to set array as value to a non-multivalued property");
        }
        */

        if ($type == \PHPCR\PropertyType::PATH)
        {
            if (strpos($value, ' ') !== false)
            {
                throw new \PHPCR\ValueFormatException("Invalid empty element in path");
            }
        }

        if ($type == \PHPCR\PropertyType::URI)
        {
            if (strpos($value, '\\') !== false)
            {
                throw new \PHPCR\ValueFormatException("Invalid '\' URI character");
            }
        }

        if ($type == \PHPCR\PropertyType::NAME)
        {
            if (strpos($value, ':') !== false)
            {
                $nsregistry = $this->parent->getSession()->getWorkspace()->getNamespaceRegistry();
                $nsmanager = $nsregistry->getNamespaceManager();
                if (!$nsmanager->getPrefix($value))
                {
                    throw new \PHPCR\ValueFormatException("Invalid '\' URI character");
                }
            }
        }
    }

    private function normalizePropertyValue($value)
    {
        /*
         * The type detection follows PropertyType::determineType. 
         * Thus, passing a Node object without an explicit type (REFERENCE or WEAKREFERENCE) will create a REFERENCE property. 
         * If the specified node is not referenceable, a ValueFormatException is thrown.
         */ 
        if (is_a($value, '\Midgard\PHPCR\Node'))
        {
            if (!$value->isReferenceable())
            {
                throw new \PHPCR\ValueFormatException("Node " . $value->getPath() . " is not referencable"); 
            }

            if ($type == null)
            {
                $type = \PHPCR\PropertyType::REFERENCE;
            }

            return $value->getProperty('jcr:uuid')->getString();
        }
        else if (is_a($value, '\DateTime'))
        {
            return $value->format("c");
        }
        else if (is_a($value, '\Midgard\PHPCR\Property'))
        {
            return $value->getString(); 
        }
        return $value;
    }

    public function setValue($value, $type = null, $weak = FALSE)
    { 
        /* \PHPCR\ValueFormatException */
        $this->validateValue($value, $type);

        /* Check if property is registered.
         * If it is, we need to validate if conversion follows the spec: "3.6.4 Property Type Conversion" */
        $typename = $this->parent->getTypeName();
        $ntm = $this->getSession()->getWorkspace()->getNodeTypeManager();
        $nt = $ntm->getNodeType($typename);
        if ($nt->hasRegisteredProperty($this->getName()) && $type != null)
        {
            Value::checkTransformable($this->getType(), $type);
        }

        $value = $this->normalizePropertyValue($value);

        /* TODO, handle:
         * \PHPCR\Version\VersionException 
         * \PHPCR\Lock\LockException
         * \PHPCR\ConstraintViolationException
         * \PHPCR\RepositoryException
         * \InvalidArgumentException
         */ 
        $propertyName = $this->getMidgard2PropertyName();
        if ($propertyName 
            && (!$this->isMultiple() || $this->getName() != 'jcr:mixinTypes')) 
        { 
            $this->parent->contentObject->$propertyName = $value;
            return;
        }

        if ($type == null)
        {
            if ($this->parent->hasProperty($this->getName()))
            {
                $type = $this->getType();
            }

            if ($type == null)
            {
                $type = self::determineType($value);
            }
        }

        $pNodes = $this->getMidgardPropertyNodes();
        if (is_array($value))
        {
            // FIXME: We should ensure the property is multivalued
            foreach($value as $v)
            {
                if ($pNodes) {
                    $pNode = array_shift($pNodes);
                    $this->setMidgard2NodePropertyValue($pNode, $v, $type);
                    continue;
                }
                $this->setMidgard2NodePropertyValue(null, $v, $type);
            }
            $this->contentObject->multiple = true;
        }
        else 
        {
            $this->setMidgard2NodePropertyValue(empty($pNodes) ? null : $pNodes[0], $value, $type);
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
            return ValueFactory::transformValue($this->getNativeValue(), \PHPCR\PropertyType::STRING, $type);
        } 
    }

    public function getNativeValue()
    {
        if ($this->type == \PHPCR\PropertyType::BINARY)
        {
            return $this->getBinary();
        } 

        $propertyName = $this->getMidgard2PropertyName();
        if ($propertyName && !$this->isMultiple())
        {
            $contentObject = $this->parent->getMidgard2ContentObject();
            if ($contentObject && isset($contentObject->$propertyName)) {
                return $this->parent->getMidgard2ContentObject()->$propertyName;
            }
        }

        $pNodes = $this->parent->getMidgardPropertyNodes($this->getName());
        $ret = array();
        if (!empty($pNodes))
        {
            if (!$this->isMultiple())
            {
                $property = array_pop($pNodes);
                return $property->value;
            } 
            else 
            {
                foreach ($pNodes as $np)
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
        $pNodes = $this->getMidgardPropertyNodes();
        if (empty($pNodes))
        {
            return null;
        }

        $attachments = array();
        foreach ($pNodes as $prop)
        {
            if (!$prop->guid)
            {
                continue;
            }
            $attachments = array_merge($attachments, $prop->list_attachments());
        }

        if (empty($attachments))
        {
            if(empty($pNodes))
            {
                return null;
            }
            foreach ($pNodes as $mnp)
            {
                $ret[] = ValueFactory::transformValue($mnp->value, \PHPCR\PropertyType::STRING, \PHPCR\PropertyType::BINARY);
            }
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
            || $type == \PHPCR\PropertyType::WEAKREFERENCE)
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

        if ($type == \PHPCR\PropertyType::REFERENCE || $type == \PHPCR\PropertyType::WEAKREFERENCE)
        {
            try {
                $v = $this->getNativeValue();
                if (is_array($v))
                {
                    $ret = array();
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
        $type_id = NodeMapper::getPHPCRPropertyType(get_class($this->parent->getMidgard2ContentObject()), $this->midgardPropertyName);
        $this->type = $type_id;
        return $this->type;
    }

    public function getType()
    {
        if ($this->isMidgardProperty == true)
        { 
            return $this->getMGDType();
        }
 
        $pNodes = $this->getMidgardPropertyNodes(); 
        if (!empty($pNodes))
        {
            $this->type = $pNodes[0]->type;
        }

        return $this->type;
    }
    
    public function isMultiple()
    {
        /* Hack, needs to be fixed in core so reflector_property can handle this */
        if ($this->getName() == 'jcr:mixinTypes')
        {
            return true;
        }

        if ($this->isMidgardProperty)
        {
            return false;
        }

        if ($this->contentObject)
        {
            return $this->contentObject->multiple;
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
        $pnodes = $this->getMidgardPropertyNodes();

        if (empty($pnodes))
        {
            return;
        }

        foreach ($pnodes as $mpn)
        { 
            if ($this->isNew() && !$mpn->guid)
            {
                $mpn->parent = $this->parent->getMidgard2Node()->id;
                $mpn->parentguid = $this->parent->getMidgard2Node()->guid;
                $mpn->create();
                Repository::checkMidgard2Exception();
            }
            else if ($this->isModified())
            {
                $mpn->update();
                Repository::checkMidgard2Exception();
            }
        }

        $this->is_new = false;
        $this->is_modified = false;
    }

    public function refresh($keepChanges)
    {
        if ($keepChanges && ($this->isModified() || $this->isNew())) {
            return;
        }
    }

    public function remove()
    {
        $this->parent->setProperty($this->getName(), null);
    }
}
