<?php
namespace Midgard\PHPCR;

use PHPCR\PropertyInterface;
use PHPCR\ItemInterface;
use PHPCR\PropertyType;
use PHPCR\NodeType\PropertyDefinitionInterface; 
use PHPCR\ValueFormatException;
use IteratorAggregate;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Utils\ValueFactory;

class Property extends Item implements IteratorAggregate, PropertyInterface
{
    protected $propertyName = null;
    protected $type = PropertyType::UNDEFINED;
    protected $definition = null;

    public function __construct(Node $node, $propertyName, PropertyDefinitionInterface $definition = null)
    {
        $this->propertyName = $propertyName; 
        $this->parent = $node;
        $this->session = $node->getSession();
        $this->definition = $definition;
    }

    protected function populateParent()
    {
    }

    public function getParentNode()
    {
        return $this->parent;
    }

    private function determineType($value)
    {
        if (is_long($value)) {
            return PropertyType::LONG;
        }

        if (is_double($value)) {
            return PropertyType::DOUBLE;
        }

        if (is_string($value)) {
            return PropertyType::STRING;
        }

        if (is_bool($value)) {
            return PropertyType::BOOLEAN;
        }

        if (is_array($value)) {
            return $this->determineType($value[0]);
        }
    }

    private function validateValue($value, $type)
    {
        /*
        if (is_array($value) && !$this->isMultiple()) {
xdebug_print_function_stack();
            throw new \PHPCR\ValueFormatException("Attempted to set array as value to a non-multivalued property");
        }
        */

        if ($type == PropertyType::PATH) {
            if (strpos($value, ' ') !== false) {
                throw new ValueFormatException("Invalid empty element in path");
            }
        }

        if ($type == PropertyType::URI) {
            if (strpos($value, '\\') !== false) {
                throw new ValueFormatException("Invalid '\' URI character");
            }
        }

        if ($type == PropertyType::NAME)
        {
            if (strpos($value, ':') !== false) {
                $nsregistry = $this->getSession()->getWorkspace()->getNamespaceRegistry();
                $nsmanager = $nsregistry->getNamespaceManager();
                if (!$nsmanager->getPrefix($value)) {
                    throw new \PHPCR\ValueFormatException("Invalid '\' URI character");
                }
            }
        }
    }

    private function normalizePropertyValue($value, $type)
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
        $nt = $this->parent->getPrimaryNodeType();
        if ($nt->hasRegisteredProperty($this->getName()) && $type != null)
        {
            Value::checkTransformable($this->getType(), $type);
        }

        /* TODO, handle:
         * \PHPCR\Version\VersionException 
         * \PHPCR\Lock\LockException
         * \PHPCR\ConstraintViolationException
         * \PHPCR\RepositoryException
         * \InvalidArgumentException
         */ 

        $normalizedValue = $this->normalizePropertyValue($value, $type);
        $this->setMidgard2PropertyValue($this->getName(), $this->isMultiple(), $normalizedValue);
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
        if ($this->type == PropertyType::BINARY) {
            return $this->getBinary();
        } 

        return $this->getMidgard2PropertyValue($this->getName(), $this->isMultiple());
    }

    public function getString()
    {
        $type = $this->getType();
        return ValueFactory::transformValue($this->getNativeValue(), $type, \PHPCR\PropertyType::STRING);
    }
    
    public function getBinary()
    {
        if ($this->getType() != PropertyType::BINARY) {
            $sv = new StringValue();
            return ValueFactory::transformValue($this->getNativeValue(), $this->type, PropertyType::BINARY);
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
        return $this->definition;
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
            foreach ($pNodes as $pNode) {
                if (!is_object($pNode)) { 
                    continue;
                }
                $this->type = $pNode->type;
                break;
            }
        }

        return $this->type;
    }
    
    public function isMultiple()
    {
        if ($this->definition) {
            return $this->definition->isMultiple();
        }
        return false;
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

    public function isSame(ItemInterface $item)
    {
        if (!$item instanceof PropertyInterface) {
            return false;
        }

        if ($item->getName() == $this->getName()) {
            if ($item->getParent()->isSame($this->getParent())) {
                return true;
            } 
        }

        return false;
    }

    public function save()
    {
        $object = $this->getMidgard2PropertyStorage($this->getName(), $this->isMultiple());
        if (is_array($object)) {
            foreach ($object as $propertyObject) {
                if ($propertyObject->guid) {
                    $propertyObject->update();
                    continue;
                }
                $propertyObject->create();
            }
            $this->setUnmodified();
            return;
        }

        if ($object->guid) {
            $object->update();
            $this->setUnmodified();
            return;
        }

        $object->create();
        $this->setUnmodified();
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
