<?php
namespace Midgard2CR\NodeType;

class PropertyDefinition implements \PHPCR\NodeType\PropertyDefinitionInterface
{
    protected $node = null;
    protected $property = null;
    protected $reflector = null;
    protected $availableQueryOperators = null;
    protected $defaultValues = null;
    protected $valueConstraints = null;
    private $isUnstructured = false;
    protected $typename = null;

    public function __construct(Node $node, $name)
    {
        $midgardObject = $node->getMidgard2ContentObject();
        $this->property = $name;
        $this->availableQueryOperators = array();
        $this->reflector = new \midgard_reflection_property($midgardObject);

        /* FIXME, once reflector property is in PHP bindings */
        $this->defaultValues = array();

        $this->typename = $node->getMidgard2Node()->typename;

        $this->valueConstraints = array();

        if (is_a($midgardObject, 'nt_unstructured'))
        {
            $this->isUnstructured = true;
        }
    }

    private function isUnstructured()
    {
        return $this->isUnstructured;
    }

    private function getBooleanSchemaValue($value)
    {
        $name = $this->property->getMidgard2PropertyName();
        if ($name == null)
        {
            return false;
        }

        $b = $this->reflector->get_user_value($name, $value);
        if ($b == 'true')
        {
            return true;
        }
        return false;
    }

    private function getStringSchemaValue($value)
    {
        $name = $this->property->getMidgard2PropertyName();
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

    public function getAvailableQueryOperators() 
    {
        return $this->availableQueryOperators;
    }

    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    public function getRequiredType()
    {
        /* Try user defined type */
        $type = $this->getStringSchemaValue('RequiredType');

        if ($type != null)
        {
            return \PHPCR\PropertyType::valueFromName($type);
        }

        /* Fallback to native type */
        $type = $this->property->getMidgard2ValueType();
        return $type;
    }

    public function getValueConstraints()
    {
        return $this->valueConstraints;
    }

    public function isFullTextSearchable()
    {
        $type = $this->property->getMidgard2ValueType();
        if ($type == \PHPCR\PropertyType::STRING)
        {
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
        $ntm = $this->node->getSession()->getWorkspace()->getNodeTypeManager();
        return $ntm->getNodeType($this->node->getProperty('jcr:primaryType');
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
