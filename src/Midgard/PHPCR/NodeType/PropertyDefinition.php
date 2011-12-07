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
    protected $nodeTypeManager = null;

    public function __construct(NodeTypeDefinition $ntd, $name, NodeTypeManager $mgr)
    {
        $this->nodeDefinition = $ntd;
        $this->property = $name;
        $this->availableQueryOperators = array();
        $this->typename = $ntd->getName();
        $this->nodeTypeManager = $mgr;


        /* FIXME, once reflector property is in PHP bindings */
        $this->defaultValues = array();

        $this->valueConstraints = array();

        if ($this->typename == 'nt:unstructured') {
            $this->isUnstructured = true;
        }
    }

    private function prepareReflector()
    {
        if ($this->reflector) {
            return;
        }
        $midgardName = NodeMapper::getMidgardName($this->typename);
        if (is_subclass_of($midgardName, 'MidgardDBObject')) {
            $this->reflector = new midgard_reflection_property($midgardName);
        } else {
            // Currently mixin types are not reflectable. Get reflector
            // from a type using mixin
            $nodeTypes = $this->nodeTypeManager->getAllNodeTypes();
            foreach ($nodeTypes as $nodeType) {
                if ($nodeType->isMixin()) {
                    continue;
                }

                if ($nodeType->getName() == $this->nodeDefinition->getName()) {
                    continue;
                }
                if (!$nodeType->isNodeType($this->nodeDefinition->getName())) {
                    continue;
                }

                $midgardName = NodeMapper::getMidgardName($nodeType->getName());
                $this->reflector = new midgard_reflection_property($midgardName);
            }
        }

        $GNsProperty = NodeMapper::getMidgardPropertyName($this->property);
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
        $this->prepareReflector();
        if (!$this->reflector) {
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
        $this->prepareReflector();
        if (!$this->reflector) {
            return null;
        }

        $b = $this->reflector->get_user_value($this->midgardPropertyName, $value);
        if ($b == '') {
            return null;
        }
        return $b;
    }

    public function getRequiredType()
    {
        $this->prepareReflector();
        $midgardName = NodeMapper::getMidgardName($this->typename);
        if (!$midgardName) {
            return null;
        }
        return NodeMapper::getPHPCRPropertyType($midgardName, $this->midgardPropertyName, $this->reflector);
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
