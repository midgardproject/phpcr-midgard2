<?php
namespace Midgard\PHPCR;

use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface; 
use PHPCR\ItemNotFoundException; 
use midgard_object_class;
use midgard_query_select;
use midgard_query_constraint;
use midgard_query_constraint_group;
use midgard_query_storage;
use midgard_query_property;
use midgard_query_value;
use midgard_node;
use midgard_node_property;
use Midgard\PHPCR\Utils\NodeMapper;

abstract class Item implements ItemInterface
{
    protected $session = null;
    protected $parent = null;
    protected $is_new = false;
    protected $is_modified = false;
    protected $contentObject = null;
    protected $midgardNode = null;
    protected $propertyManager = null;
    protected $propertyObjects = array();

    protected function populateContentObject()
    {
        if ($this->contentObject) {
            return;
        }

        if ($this instanceof Property) {
            $this->contentObject = $this->getParent()->getMidgard2ContentObject();
            return;
        }

        $midgardType = '\\' . NodeMapper::getMidgardName($this->getTypeName(false));
        if ($this->midgardNode->objectguid) {
            $this->contentObject = new $midgardType($this->midgardNode->objectguid);
        } else {
            $this->contentObject = new $midgardType();
        }
        /*
        if ($this->hasProperty('jcr:created'))
        {
            $this->setProperty('jcr:created',  new \DateTime('now'), \PHPCR\PropertyType::DATE);
        }*/
    }

    public function getMidgard2ContentObject()
    {
        $this->populateContentObject();
        return $this->contentObject;
    }

    protected function setMidgard2ContentObject($object)
    {
        $this->contentObject = $object;
    }

    public function getMidgard2Node()
    {
        if ($this instanceof Property && !$this->midgardNode) {
            $this->setMidgard2Node($this->getParent()->getMidgard2Node());
        }
        return $this->midgardNode;
    }

    protected function setMidgard2Node(midgard_node $node)
    {
        if (!$node->guid || !$node->objectguid) {
            $this->is_new = true;
        }
        $this->midgardNode = $node;
    }

    protected function refreshMidgard2Node()
    {
        if (!$this->midgardNode || !$this->midgardNode->guid) {
            return;
        }
        $this->midgardNode = new midgard_node($this->midgardNode->guid);
    }

    private function prepareMidgard2PropertyObject($name, $multiple)
    {
        $midgardName = NodeMapper::getMidgardPropertyName($name);
        $prop = new midgard_node_property();
        $prop->name = $midgardName;
        $prop->title = $name;
        $prop->parent = $this->getMidgard2Node()->id;
        $prop->parentguid = $this->getMidgard2Node()->guid;
        $prop->multiple = $multiple;

        if ($this instanceof Property) {
            $prop->type = $this->getType();
        }

        return $prop;
    }

    private function getMidgard2PropertyStorageEmpty($name, $multiple, $prepareNew)
    {
        if (!$prepareNew) {
            return null;
        }
        $prop = $this->prepareMidgard2PropertyObject($name, $multiple);
        if ($multiple) {
            $this->propertyObjects[$name][$multiple] = array($prop);
            return $this->propertyObjects[$name][$multiple];
        }
        $this->propertyObjects[$name][$multiple] = $prop;
        return $this->propertyObjects[$name][$multiple];
    }

    protected function getMidgard2PropertyStorage($name, $multiple, $checkContentObject = true, $prepareNew = true)
    {
        if (!isset($this->propertyObjects[$name])) {
            $this->propertyObjects[$name] = array();
        }

        if (isset($this->propertyObjects[$name][$multiple])) {
            return $this->propertyObjects[$name][$multiple];
        }

        $midgardName = NodeMapper::getMidgardPropertyName($name);

        if (!$multiple && $checkContentObject) {
            $contentObject = $this->getMidgard2ContentObject();
            if (property_exists($contentObject, $midgardName)) {
                return $contentObject;
            }
        }

        if (!$this->getMidgard2Node() || !$this->getMidgard2Node()->guid) {
            return $this->getMidgard2PropertyStorageEmpty($name, $multiple, $prepareNew);
        }

        $q = new midgard_query_select(new midgard_query_storage('midgard_node_property'));
        $cg = new midgard_query_constraint_group('AND');
        $cg->add_constraint(
            new midgard_query_constraint(
                new midgard_query_property('parent'),
                '=',
                new midgard_query_value($this->getMidgard2Node()->id)
            )
        );
        $cg->add_constraint(
            new midgard_query_constraint(
                new midgard_query_property('name'),
                '=',
                new midgard_query_value($midgardName)
            )
        );
        $q->set_constraint($cg);
        $q->execute();
        if ($q->get_results_count() < 1) {
            return $this->getMidgard2PropertyStorageEmpty($name, $multiple, $prepareNew);
        }
        $objects = $q->list_objects();
        if ($multiple) {
            $this->propertyObjects[$name][$multiple] = $objects;
            return $this->propertyObjects[$name][$multiple];
        }
        $this->propertyObjects[$name][$multiple] = $objects[0];
        return $this->propertyObjects[$name][$multiple];
    }

    protected function removeMidgard2PropertyStorage($name, $multiple)
    {
        $storage = $this->getMidgard2PropertyStorage($name, $multiple, true);
        if ($multiple) {
            foreach ($storage as $propStorage) {
                $propStorage->purge_attachments(true);
                $propStorage->purge();
            }
            return;
        }

        if ($storage instanceof midgard_node_property) {
            $storage->purge_attachments(true);
            $storage->purge();
        }
    }

    protected function getMidgard2PropertyValue($name, $multiple, $checkContentObject = true, $prepareNew = true)
    {
        $object = $this->getMidgard2PropertyStorage($name, $multiple, $checkContentObject, $prepareNew);
        if (!$object) {
            if ($multiple) {
                return array();
            }
            return null;
        }

        if ($multiple) {
            $values = array();
            foreach ($object as $property) {
                $values[] = $property->value;
            }
            return $values;
        }

        if ($object instanceof midgard_node_property) {
            return $object->value;
        }

        $midgardName = NodeMapper::getMidgardPropertyName($name);
        return $object->$midgardName;
    }

    protected function setMidgard2PropertyValue($name, $multiple, $value)
    {
        $object = $this->getMidgard2PropertyStorage($name, $multiple);
        if ($multiple) {
            $storedValues = array();
            $storedProperties = array();
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($object as $propertyObject) {
                if (!in_array($propertyObject->value, $value)) {
                    if ($propertyObject->guid) {
                        $propertyObject->delete();
                    }
                    continue;
                }
                $storedValues[] = $propertyObject->value;
                $storedProperties[] = $propertyObject;
            }
            $toStore = array_diff($value, $storedValues);
            foreach ($toStore as $val) {
                $prop = $this->prepareMidgard2PropertyObject($name, $multiple);
                $prop->value = $val;
                $storedProperties[] = $prop;
            }
            $this->propertyObjects[$name][$multiple] = $storedProperties;
            return;
        }

        if ($object instanceof midgard_node_property) {
            return $object->value = $value;
        }

        $midgardName = NodeMapper::getMidgardPropertyName($name);
        return $object->$midgardName = $value;
    }

    abstract protected function populateParent();

    public function getPath()
    {
        if (!$this->parent) {
            $this->populateParent();
            if (!$this->parent) {
                /* Root node probably */
                return '/';
            }
        }
        $parentPath = $this->parent->getPath();
        if ($parentPath == '/') {
            return "/{$this->getName()}";
        }
        return "{$parentPath}/{$this->getName()}";
    }
    
    public function getName()
    {
        if (!$this->parent) {
            $this->populateParent();
            if (!$this->parent) {
                // Root node
                return '';
            }
        }
        if (!$this->midgardNode->name) {
            $this->refreshMidgard2Node();
        }

        return $this->midgardNode->name;
    }

    public function getAncestor($depth)
    {
        if ($depth < 0 || $depth > $this->getDepth()) {
            throw new ItemNotFoundException("Invalid depth ({$depth}) value.");
        }

        /* n is the depth of this Item, which returns this Item itself. */
        if ($depth == $this->getDepth()) {
            return $this;
        }

        $ancestor = $this;

        while (true) {
            try {
                $ancestor = $ancestor->getParent();
                if ($ancestor->getDepth() == $depth) {
                    break;
                }
            } 
            catch (ItemNotFoundException $e) {
                $ancestor = $this->getSession()->getRootNode();
                break;
            }
        }

        if ($ancestor != null) { 
            return $ancestor;
        }

        throw new ItemNotFoundException("No item found at depth {$depth}");
    }

    public function getParent()
    {
        $this->populateParent();
        if (!$this->parent) {
            throw new ItemNotFoundException();
        }
        return $this->parent;
    }

    public function getDepth()
    {
        try {
            $parent = $this->getParent();
            return $parent->getDepth() + 1;
        } 
        catch (ItemNotFoundException $e) {
            return 0;
        }
    }

    public function getSession()
    {
        return $this->session;
    }

    public function isNode()
    {
        return true;
    }

    public function isNew()
    {
        return $this->is_new;
    }

    public function isModified()
    {
        return $this->is_modified;
    }

    protected function setUnmodified()
    {
        $this->is_new = false;
        $this->is_modified = false;
    }

    public function isSame(ItemInterface $otherItem)
    {
        return false;
    }

    public function accept(ItemVisitorInterface $visitor)
    {
        $visitor->visit($this);
    }

    public function refresh($keepChanges)
    {
        if ($keepChanges) {
            return;
        }
        $this->propertyObjects = array();
    }

    public function remove()
    {
    }
}
