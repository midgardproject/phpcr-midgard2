<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\PropertyType;
use midgard_reflection_property;

class PropertyDefinition implements PropertyDefinitionInterface
{
    protected $node = null;
    protected $property = null;
    protected $reflector = null;
    protected $availableQueryOperators = null;
    protected $defaultValues = null;
    protected $valueConstraints = null;
    private $isUnstructured = false;
    protected $typename = null;
    protected $midgardPropertyName = null;

    public function __construct(NodeTypeDefinition $ntd, $name)
    {
        $this->nodeDefinition = $ntd;
        $this->property = $name;
        $this->availableQueryOperators = array();
        $this->typename = $ntd->getName();

        $midgardName = NodeMapper::getMidgardName($this->typename);
        if (is_subclass_of($midgardName, 'MidgardDBObject')) {
            $this->reflector = new midgard_reflection_property($midgardName);
        }

        /* FIXME, once reflector property is in PHP bindings */
        $this->defaultValues = array();

        $this->valueConstraints = array();

        if (is_a($midgardName, 'nt_unstructured')) {
            $this->isUnstructured = true;
        }

        if (substr($name, 0, 4) == 'mgd:') {
            $this->midgardPropertyName = substr($name, 4);
        }

        $GNsProperty = str_replace(':', '-', $name);
        if ($this->reflector && $this->reflector->get_midgard_type($GNsProperty)) {
            $this->midgardPropertyName = $GNsProperty;
        }
    }

    private function getPropertyTokens()
    {
        $nsregistry = $this->node->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();
        return $nsmanager->getPrefixTokens($this->property);
    }

    private function isUnstructured()
    {
        return $this->isUnstructured;
    }

    private function getBooleanSchemaValue($value)
    {
        if (!$this->midgardPropertyName) {
            return false;
        }

        $b = $this->reflector->get_user_value($this->midgardPropertyName, $value);
        if ($b == 'true') {
            return true;
        }
        return false;
    }

    public function getAvailableQueryOperators() 
    {
        return $this->availableQueryOperators;
    }

    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    private function getStringSchemaValue($value)
    {
        $name = $this->midgardPropertyName;
        if ($name == null)
        {
            return null;
        }

        $b = $this->reflector->get_user_value($name, $value);
        if ($b == '')
        {
            return null;
        }
        return $b;
    }

    public function getRequiredType()
    {
        $midgardName = NodeMapper::getMidgardName($this->typename);
        if (!$midgardName) {
            return null;
        }
        return NodeMapper::getPHPCRPropertyType($midgardName, $this->midgardPropertyName);
    }

    public function getValueConstraints()
    {
        return $this->valueConstraints;
    }

    public function isFullTextSearchable()
    {
        if ($this->getRequiredType() == PropertyType::STRING) {
            return true;
        }
        return false;
    }

    public function isMultiple()
    {
        return $this->getBooleanSchemaValue('isMultiple');
    }

    public function isQueryOrderable()
    {
        return true;
    }

    public function getDeclaringNodeType()
    {
        return $this->nodeDefinition->getName();
    }

    public function getName()
    {
        return $this->property;
    }

    public function getOnParentVersion()
    {
        return $this->onParentVersion;
    }

    public function isAutoCreated()
    {
        return $this->getBooleanSchemaValue('isAutoCreated');
    }

    public function isMandatory()
    {
        return $this->getBooleanSchemaValue('isMandatory');
    }

    public function isProtected()
    {
        return $this->getBooleanSchemaValue('isProtected');
    }
}
