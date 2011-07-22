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
    protected $midgardPropertyName = null;

    public function __construct(\Midgard2CR\Node $node, $name)
    {
        $midgardObject = $node->getMidgard2ContentObject();
        $this->node = $node;
        $this->property = $name;
        $this->availableQueryOperators = array();
        $this->reflector = new \midgard_reflection_property(get_class($midgardObject));

        /* FIXME, once reflector property is in PHP bindings */
        $this->defaultValues = array();

        $this->typename = $node->getMidgard2Node()->typename;

        $this->valueConstraints = array();

        if (is_a($midgardObject, 'nt_unstructured'))
        {
            $this->isUnstructured = true;
        }

        $tokens = $this->getPropertyTokens();
        if ($tokens[0] == 'mgd'
            && $tokens[1] != null)
        {
            $this->midgardPropertyName = $tokens[1];
        }

        $GNsProperty = str_replace(':', '-', $name);
        if (property_exists($midgardObject, $GNsProperty))
        {
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
        $name = $this->midgardPropertyName;
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

        if ($type != null
            || $type != '')
        {
            return \PHPCR\PropertyType::valueFromName($type);
        }

        /* Fallback to native type */
        $type = $this->reflector->get_midgard_type($this->midgardPropertyName);
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

        /* Try midgard_node_property */
        if ($type_id == 0)
        {
            try 
            {
                $property = $this->node->getProperty($this->property);
                $propertyNode = $property->getMidgard2ContentObject();
                $type_id = $propertyNode->type;
            }
            catch (\PHPCR\PathNotFoundException $e)
            {
                /* Do nothing */
            }
        }

        return $type_id;
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
        return $ntm->getNodeType($this->node->getProperty('jcr:primaryType'));
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
