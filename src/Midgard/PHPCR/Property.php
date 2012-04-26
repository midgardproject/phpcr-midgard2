<?php
namespace Midgard\PHPCR;

use PHPCR\PropertyInterface;
use PHPCR\ItemInterface;
use PHPCR\PropertyType;
use PHPCR\NodeType\PropertyDefinitionInterface; 
use PHPCR\ValueFormatException;
use PHPCR\RepositoryException;
use PHPCR\InvalidItemStateException;
use PHPCR\Util\UUIDHelper;
use IteratorAggregate;
use DateTime;
use Midgard\PHPCR\Utils\NodeMapper;
use midgard_attachment;
use midgard_blob;
use midgard_node_property;

class Property extends Item implements IteratorAggregate, PropertyInterface
{
    protected $propertyName = null;
    protected $type = null;
    protected $definition = null;
    protected $multiple = null;
    private $value = null;
    private $resources = array();
    private $propertyResources = array();

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
        return PropertyType::convertType($value, PropertyType::STRING, $this->getType());
    }

    public function setValue($value, $type = null, $weak = FALSE)
    {
        if (is_null($value)) {
            return $this->remove();
        }

        if ($type) {
            if ($this->definition != null) {
                if ($type != $this->definition->getRequiredType()) {
                    throw new \PHPCR\ValueFormatException("Property " . $this->getName() . " registered with different type");
                }
            }
            $this->type = $type;
        } elseif (is_null($this->type)) {
            $this->type = PropertyType::determineType(is_array($value) ? reset($value) : $value);
        }

        /* \PHPCR\ValueFormatException */
        $this->validateValue($value, $type);

        if ($this->isMultiple() && !is_array($value)) {
            $v = $this->getValue();
            $nv = array();
            is_array($v) ? $nv = array_merge($nv, $v) : $nv[] = $v;
            $nv[] = $value;
            $value = $nv;
        }

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

        if (is_array($value)) {
            if (is_null($this->multiple) && $this->isNew()) {
                $this->multiple = true;
            } elseif (!$this->isMultiple()) {
                throw new ValueFormatException("Cannot set multiple values to a non-multivalued property " . $this->getPath());
            }
        }

        $normalizedValue = $this->normalizePropertyValue($value, $this->type);
        if ($this->isMultiple() && !is_array($normalizedValue)) {
            $normalizedValue = array($normalizedValue);
        }

        if ($this->getType() == PropertyType::BINARY) {
            $this->setBinaryValue($value);
        } else if ($this->getType() == PropertyType::DATE) {
            $this->setMidgard2PropertyValue($this->getName(), $this->isMultiple(), $normalizedValue);
            $this->value = $value;
        } else {
            $this->value = $normalizedValue;
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
        $this->resources[] = $source;
        $this->resources[] = $target;
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
            throw new ValueFormatException("Can't add values to a non-multiple property " . $this->getPath());
        }
        $values = $this->getValue();
        $values[] = $value;
        $this->setValue($values);
    }

    public function getValue()
    {
        if ($this->is_purged === true || $this->is_removed === true) {
            throw new \PHPCR\RepositoryException("Can not get value of purged property.");
        }

        $type = $this->getType();
        switch ($type) 
        {
        case PropertyType::DATE:
            return $this->getDate();

        case PropertyType::BINARY:
            return $this->getBinary();

        case PropertyType::REFERENCE:
            if ($this->value == null || $this->value == '') {
                return null;
            }
            return $this->getNode();

        case PropertyType::WEAKREFERENCE:
            $v = $this->getNativeValue();
            if (empty($v) || $v === '' || $v === null) {
                return null;
            }
            return $this->getNode();

        default:
            return PropertyType::convertType($this->getNativeValue(), $type, PropertyType::STRING);
        } 
    }

    private function getDefaultValue($value)
    {
        if ($this->getType() == PropertyType::DATE) {
            $this->setValue(new \DateTime());
            return $this->getNativeValue();
        }

        switch ($this->getName()) {
        case 'jcr:uuid':
            $this->setValue(UUIDHelper::generateUUID());
            return $this->getNativeValue();

        case 'jcr:createdBy':
        case 'jcr:lastModifiedBy':
            $this->setValue($this->parent->getSession()->getUserID());
            return $this->getNativeValue();
        case 'jcr:primaryType':
            $this->setValue($this->parent->getPrimaryNodeType()->getName());
            return $this->getNativeValue();
        }

        $defaults = $this->definition->getDefaultValues();
        if (!$defaults) {
            return $value;
        }

        if ($this->isMultiple()) {
            $this->setValue($defaults);
            return $this->getNativeValue();
        }

        $this->setValue($defaults[0]);
        return $this->getNativeValue();
    }

    public function getNativeValue()
    {
        if ($this->getType() == PropertyType::BINARY) {
            return $this->getBinary();
        }

        if (!is_null($this->value)) {
            return $this->value;
        }

        $value = $this->getMidgard2PropertyValue($this->getName(), $this->isMultiple(), true, false);
        if (!$value && $this->definition && $this->definition->isAutoCreated()) {
            $value = $this->getDefaultValue($value);
        }

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
                } else {
                    if ($val->getTimeStamp() < 0) {
                        $val = new \DateTime();
                    }
                }
                $ret[] = $val;
            }
            $value = $ret;
            if (!$this->isMultiple()) {
                $value = $ret[0];
            }
        }

        if ($this->getType() != PropertyType::DATE) {
            $this->value = $value;
        }
        return $value;
    }

    public function getString()
    {
        return PropertyType::convertType($this->getNativeValue(), PropertyType::STRING, $this->getType());
    }

    protected function getMidgard2PropertyBinary($name, $multiple)
    {
        $object = $this->getMidgard2PropertyStorage($name, $multiple);
        if (!is_array($object)) {
            $object = array($object);
        }

        $ret = array();
        foreach ($object as $index => $propertyObject) {
            if (isset($this->propertyResources[$index]) && is_resource($this->propertyResources[$index])) {
                rewind($this->propertyResources[$index]);
                $oldStream = $this->propertyResources[$index];
                $this->propertyResources[$index] = fopen('php://memory', 'rwb');
                stream_copy_to_stream($oldStream, $this->propertyResources[$index]);
                rewind($this->propertyResources[$index]);
                $ret[] = $this->propertyResources[$index];
                continue;
            }

            $this->propertyResources[$index] = fopen('php://memory', 'rwb');
            $ret[] = $this->propertyResources[$index];
            if (!$propertyObject->guid) {
                continue;
            }

            $attachments = $propertyObject->find_attachments(array('name' => $name));
            if ($attachments) {
                // Existing attachment, copy to a new in-memory stream
                $blob = new midgard_blob($attachments[0]);
                $source = $blob->get_handler('r');
                rewind($source);
                stream_copy_to_stream($source, $this->propertyResources[$index]);
                rewind($this->propertyResources[$index]);
                fclose($source);
            }
        }

        if ($multiple) {
            return $ret;
        }
        return $ret[0];
    }
    
    public function getBinary()
    {
        if ($this->getType() != PropertyType::BINARY) {
            $stream = PropertyType::convertType($this->getNativeValue(), PropertyType::BINARY, $this->getType());
            $this->resources[] = $stream;
            return $stream;
        }
        $stream = $this->getMidgard2PropertyBinary($this->getName(), $this->isMultiple());
        $this->resources[] = $stream;
        return $stream;
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
            if (strpos($path, ".") == false) {
                try {
                    $node = $this->parent->getNode($path);
                    return $node;
                }
                catch (\PHPCR\PathNotFoundException $e) {
                    throw new \PHPCR\ItemNotFoundException($e->getMessage());
                }
            }
            return $this->getSession()->getNode($path);
        }

        if ($type == PropertyType::REFERENCE || $type == PropertyType::WEAKREFERENCE) {
            try {
                $v = $this->getNativeValue();
                if (is_array($v)) {
                    $ret = array();
                    foreach ($v as $id) {
                        $ret[] = $this->parent->getSession()->getNodeByIdentifier($id);
                    }
                    foreach ($ret as $index => $node) {
                        $ret[$index] = $this->parent->getSession()->getNode($node->getPath()); 
                    }
                    return $ret;
                } 
                $node = $this->parent->getSession()->getNodeByIdentifier($v);
                return $this->parent->getSession()->getNode($node->getPath());
            }
            catch (\PHPCR\PathNotFoundException $e) {
                throw new \PHPCR\ItemNotFoundException($e->getMessage());
            }
        }
   
        throw new ValueFormatException("Can not convert {$this->propertyName} (of type " . PropertyType::nameFromValue($type) . ") to Node type."); 
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

        if ($this->getType() === PropertyType::BINARY)
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
        if (!is_null($this->type)) {
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
        if ($this->definition) {
            return $this->definition->isMultiple();
        }
        $object = $this->getMidgard2PropertyStorage($this->getName(), false, false, false);
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

    private function savePropertyObject($propertyObject, $index = 0)
    {
        $midgardName = NodeMapper::getMidgardPropertyName($this->getName());
        $propertyObject->name = $midgardName;
        $propertyObject->title = $this->getName();

        $type = $this->getType();
        if (!$type && !$propertyObject->type) {
            $type = PropertyType::determineType($propertyObject->value);
        }
        $propertyObject->type = $type;
        $propertyObject->multiple = $this->isMultiple();

        if (!$propertyObject->parent) {
            $propertyObject->parent = $this->getMidgard2Node()->id;
            $propertyObject->parentguid = $this->getMidgard2Node()->guid;
        }
        if ($propertyObject->guid) {
            $propertyObject->update();
        } else {
            if ($this->definition != null && $this->definition->isAutoCreated() === true) {
                if ($propertyObject->value == '') {
                    $propertyObject->value = $this->getDefaultValue('');
                }
            }
            $propertyObject->create();
        }
        $this->saveBinaryObject($propertyObject, $index);
    }

    private function saveBinaryObject($propertyObject, $index = 0)
    {
        if ($this->getType() != PropertyType::BINARY) {
            return;
        }

        if (!isset($this->propertyResources[$index])) {
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
        rewind($this->propertyResources[$index]);
        $blob->write_content(stream_get_contents($this->propertyResources[$index]));
        rewind($this->propertyResources[$index]);

        if ($attachments[0]->guid) {
            $attachments[0]->update();
            return;
        }
        $attachments[0]->create();
    }

    private function closeResources()
    {
        foreach ($this->resources as $resource) {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
        $this->resources = array();
    }

    public function save()
    {
        if (!$this->is_modified && !$this->isNew()) {
            $this->closeResources();
            return;
        }

        $object = $this->getMidgard2PropertyStorage($this->getName(), $this->isMultiple());
        if (is_array($object)) {
            foreach ($object as $index => $propertyObject) {
                $this->savePropertyObject($propertyObject, $index);
            }
            $this->setUnmodified();
        } elseif (is_a($object, 'midgard_node_property')) {
            $this->savePropertyObject($object);
            $this->setUnmodified();
        } else {
            $this->saveBinaryObject($object);
        }

        $this->closeResources();
    }

    public function refresh($keepChanges)
    {
        if ($this->is_removed) {
            throw new InvalidItemStateException("Cannot refresh removed property " . $this->getPathUnchecked());
        }

        if ($keepChanges === false) {
            $this->is_removed = false;
            $this->value = null;
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
            $this->is_purged = true;
            return;
        }
        if (!$object->guid) {
            return;
        }
        $object->purge_attachments(true);
        $object->purge();
        $this->is_purged = true;
    }

    public function remove()
    {
        $this->parent->setProperty($this->getName(), null);
    }
}
