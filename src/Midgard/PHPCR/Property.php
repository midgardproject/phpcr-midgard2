<?php
namespace Midgard\PHPCR;

use PHPCR\PropertyInterface;
use PHPCR\ItemInterface;
use PHPCR\PropertyType;
use PHPCR\NodeType\PropertyDefinitionInterface; 
use PHPCR\ValueFormatException;
use PHPCR\RepositoryException;
use IteratorAggregate;
use DateTime;
use Midgard\PHPCR\Utils\NodeMapper;
use midgard_attachment;
use midgard_blob;
use midgard_node_property;

class Property extends Item implements IteratorAggregate, PropertyInterface
{
    protected $propertyName = null;
    protected $type = PropertyType::UNDEFINED;
    protected $definition = null;
    protected $multiple = null;
    private $streams = array();

    public function __construct(Node $node, $propertyName, PropertyDefinitionInterface $definition = null, $type = null)
    {
        $this->propertyName = $propertyName; 
        $this->parent = $node;
        $this->session = $node->getSession();
        $this->definition = $definition;

        if ($definition) {
            $this->type = $definition->getRequiredType();
            $this->multiple = $definition->isMultiple();
        } elseif ($type) {
            $this->type = $type;
        }
    }

    protected function populateParent()
    {
    }

    public function getParentNode()
    {
        return $this->parent;
    }

    private function validateValue($value, $type)
    {
        /*
        if (is_array($value) && !$this->isNew() && !$this->isMultiple()) {
            throw new ValueFormatException("Attempted to set array as value to a non-multivalued property");
        }*/

        if ($this->isMultiple() && is_array($value)) {
            foreach ($value as $val) {
                $this->validateValue($val, $type);
            }
        }

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
                    throw new ValueFormatException("Invalid '\' URI character");
                }
            }
        }
    }

    private function normalizePropertyValue($value, $type)
    {
        if ($this->isMultiple() && is_array($value)) {
            $normalized = array();
            foreach ($value as $val) {
                $normalized[] = $this->normalizePropertyValue($val, $type);
            }
            return $normalized;
        }
        /*
         * The type detection follows PropertyType::determineType. 
         * Thus, passing a Node object without an explicit type (REFERENCE or WEAKREFERENCE) will create a REFERENCE property. 
         * If the specified node is not referenceable, a ValueFormatException is thrown.
         */
        if (is_a($value, '\Midgard\PHPCR\Node')) {
            if (!$value->isReferenceable()) {
                throw new ValueFormatException("Node " . $value->getPath() . " is not referencable"); 
            }

            if ($type == null) {
                $type = PropertyType::REFERENCE;
            }

            return $value->getIdentifier();
        }
        elseif (is_a($value, '\DateTime')) {
            return $value->format('c');
        }
        elseif (is_a($value, '\Midgard\PHPCR\Property')) {
            return $value->getString(); 
        }
        return $value;
    }

    public function setValue($value, $type = null, $weak = FALSE)
    {
        if (is_null($value)) {
            return $this->remove();
        }

        if ($type) {
            $this->type = $type;
        } elseif (!$this->type) {
            $this->type = PropertyType::determineType(is_array($value) ? reset($value) : $value);
        }

        /* \PHPCR\ValueFormatException */
        $this->validateValue($value, $type);

        /* Check if property is registered.
         * If it is, we need to validate if conversion follows the spec: "3.6.4 Property Type Conversion" */
        $value = PropertyType::convertType($value, $this->getType());

        /* TODO, handle:
         * \PHPCR\Version\VersionException 
         * \PHPCR\Lock\LockException
         * \PHPCR\ConstraintViolationException
         * \PHPCR\RepositoryException
         * \InvalidArgumentException
         */ 

        if (is_null($this->multiple) && is_array($value)) {
            $this->multiple = true;
        }

        $normalizedValue = $this->normalizePropertyValue($value, $type);
        if ($this->isMultiple() && !is_array($normalizedValue)) {
            $normalizedValue = array($normalizedValue);
        }

        if ($this->getType() == PropertyType::BINARY) {
            $this->setBinaryValue($value);
        } else {
            $this->setMidgard2PropertyValue($this->getName(), $this->isMultiple(), $normalizedValue);
        }
        $this->is_modified = true;
        $this->parent->is_modified = true;
    }

    private function writeToStream($source, $target)
    {
        rewind($source);
        stream_copy_to_stream($source, $target);
        rewind($target);
    }

    private function setBinaryValue($value)
    {
        $streams = $this->getMidgard2PropertyBinary($this->getName(), $this->isMultiple());
        if ($this->isMultiple()) {
            foreach ($streams as $stream) {
                $val = array_shift($value);
                $this->writeToStream($val, $stream);
            }
            return;
        }
        $this->writeToStream($value, $streams);
        $this->is_modified = true;
    }
    
    public function addValue($value)
    {
        if (!$this->isMultiple()) {
            throw new ValueFormatException("Can't add values to a non-multiple property");
        }
        $values = $this->getNativeValue();
        $values[] = $value;
        $this->setValue($values);
    }

    public function getValue()
    {
        $type = $this->getType();
        switch ($type) 
        {
        case PropertyType::DATE:
            return $this->getDate();

        case PropertyType::BINARY:
            return $this->getBinary();

        case PropertyType::REFERENCE:
        case PropertyType::WEAKREFERENCE:
            return $this->getNode();

        default:
            return PropertyType::convertType($this->getNativeValue(), $type, PropertyType::STRING);
        } 
    }

    public function getNativeValue()
    {
        if ($this->getType() == PropertyType::BINARY) {
            return $this->getBinary();
        } 

        $value = $this->getMidgard2PropertyValue($this->getName(), $this->isMultiple(), true, false);

        if ($this->getType() == PropertyType::DATE) {
            if (!is_array($value)) {
                $value = array($value);
            }
            $ret = array();
            foreach ($value as $val) {
                if (!is_a($val, '\DateTime')) {
                    if (is_numeric($val)) {
                        $timestamp = (int) $val;
                        $val = new \DateTime();
                        $val->setTimeStamp($timestamp);
                    } else {
                        $val = new \DateTime($val);
                    }
                }
                $ret[] = $val;
            }
            $value = $ret;
            if (!$this->isMultiple()) {
                $value = $ret[0];
            }
        }
        return $value;
    }

    public function getString()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::STRING, $this->getType());
    }
    
    public function getBinary()
    {
        if ($this->getType() != PropertyType::BINARY) {
            return PropertyType::convertType($this->getNativeValue(), PropertyType::BINARY, $this->getType());
        }
        return $this->getMidgard2PropertyBinary($this->getName(), $this->isMultiple());
    }
    
    public function getLong()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::LONG, $this->getType());
    }
    
    public function getDouble()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::DOUBLE, $this->getType());  
    }
    
    public function getDecimal()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::DECIMAL, $this->getType()); 
    }
    
    public function getDate()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::DATE, $this->getType()); 
    }
    
    public function getBoolean()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::BOOLEAN, $this->getType()); 
    }

    public function getName()
    {
        return $this->propertyName;
    }

    public function getNode()
    {
        $type = $this->getType();
        if ($type == PropertyType::PATH) {
            $path = $this->getNativeValue();
            if (is_array($path)) {
                return $this->getSession()->getNodes($path);
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
            return $this->getSession()->getNode($path);
        }

        if ($type == PropertyType::REFERENCE || $type == PropertyType::WEAKREFERENCE)
        {
            try {
                $v = $this->getNativeValue();
                if (is_array($v)) {
                    $ret = array();
                    foreach ($v as $id) {
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
   
        throw new ValueFormatException("Can not convert {$this->propertyName} (of type " . PropertyType::nameFromValue($type) . ") to Node type."); 

        return $this->parent;
    }
    
    public function getProperty()
    {
        $type = $this->getType();
        if ($type != PropertyType::PATH) {
            throw new ValueFormatException("Can not convert {$this->propertyName} (of type " . PropertyType::nameFromValue($type) . ") to PATH type.");
        } 

        $path = $this->getValue();
        if (is_array($path)) {
            foreach ($path as $v) {
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

        if ($this->type === PropertyType::BINARY)
        {
            $stat = fstat($v);
            return $stat['size'];
        }
        return strlen($this->getString());
    }
    
    public function getLengths()
    {
        $v = $this->getNativeValue();
        if (is_array($v)) {
            /* Native values are always strings */
            foreach ($v as $values) {
                if ($this->getType() == PropertyType::BINARY) {
                    $stat = fstat($values);
                    $ret[] = $stat['size'];
                    continue;
                }
                $ret[] = strlen($values);
            }
            return $ret;
        }
        throw new ValueFormatException("Can not get lengths of single value");
    }
    
    public function getDefinition()
    {
        return $this->definition;
    }

    public function getType()
    {
        if ($this->type) {
            // Type either given at instantiation or from definition
            return $this->type;
        }

        $object = $this->getMidgard2PropertyStorage($this->getName(), false, true, false);
        if (!$object) {
            return PropertyType::UNDEFINED;
        }

        if (is_array($object)) {
            $object = $object[0];
        }

        if (is_a($object, 'midgard_node_property')) {
            // Unknown additional property, read type from storage object
            return $object->type;
        }
        return NodeMapper::getPHPCRPropertyType(get_class($object), NodeMapper::getMidgardPropertyName($this->getName()));
    }
    
    public function isMultiple()
    {
        if (!is_null($this->multiple)) {
            return $this->multiple;
        }
        $object = $this->getMidgard2PropertyStorage($this->getName(), false);
        if ($object && $object instanceof midgard_node_property && $object->multiple) {
            return true;
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

    private function savePropertyObject($propertyObject)
    {
        $midgardName = NodeMapper::getMidgardPropertyName($this->getName());
        $propertyObject->name = $midgardName;
        $propertyObject->title = $this->getName();

        $type = $this->getType();
        if (!$type && !$propertyObject->type) {
            $type = PropertyType::determineType($propertyObject->value);
        }
        $propertyObject->type = $type;

        if (!$propertyObject->parent) {
            $propertyObject->parent = $this->getMidgard2Node()->id;
            $propertyObject->parentguid = $this->getMidgard2Node()->guid;
        }
        if ($propertyObject->guid) {
            $propertyObject->update();
        } else {
            $propertyObject->create();
        }

        if ($this->getType() == PropertyType::BINARY) {
            if (!isset($propertyObject->stream)) {
                return;
            }
            $attachments = $propertyObject->find_attachments(array('name' => $this->getName()));
            if (!$attachments) {
                $att = new midgard_attachment();
                $att->name = $this->getName();
                $att->parentguid = $propertyObject->guid;
                $attachments = array($att);
            }

            $blob = new midgard_blob($attachments[0]);
            rewind($propertyObject->stream);
            $blob->write_content(stream_get_contents($propertyObject->stream));
            rewind($propertyObject->stream);

            if ($attachments[0]->guid) {
                $attachments[0]->update();
                return;
            }
            $attachments[0]->create();
        }
    }

    public function save()
    {
        if (!$this->is_modified && !$this->is_new) {
            return;
        }

        $object = $this->getMidgard2PropertyStorage($this->getName(), $this->isMultiple());
        if (is_array($object)) {
            foreach ($object as $propertyObject) {
                $this->savePropertyObject($propertyObject);
            }
            $this->setUnmodified();
            return;
        }

        if (!is_a($object, 'midgard_node_property')) {
            return;
        }

        $this->savePropertyObject($object);
        $this->setUnmodified();
    }

    public function refresh($keepChanges)
    {
        if ($keepChanges && ($this->isModified() || $this->isNew())) {
            return;
        }
        parent::refresh($keepChanges);
    }

    public function removeMidgard2Property()
    {
        $object = $this->getMidgard2PropertyStorage($this->getName(), $this->isMultiple(), true, false);
        if (!$object || $object instanceof midgard_node) {
            return;
        }

        if (is_array($object)) {
            foreach ($object as $propertyObject) {
                if (!$propertyObject->guid) {
                    continue;
                }
                $propertyObject->purge_attachments(true);
                $propertyObject->purge();
            }
            return;
        }
        if (!$object->guid) {
            return;
        }
        $object->purge_attachments(true);
        $object->purge();
    }

    public function remove()
    {
        $this->parent->setProperty($this->getName(), null);
    }
}
