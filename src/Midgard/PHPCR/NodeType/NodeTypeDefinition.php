<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\Version\OnParentVersionAction;
use ReflectionClass;
use midgard_reflector_object;
use midgard_reflection_property;
use midgard_reflection_class;

class NodeTypeDefinition implements NodeTypeDefinitionInterface
{
    protected $nodeTypeManager = null;
    protected $childNodeDefinitions = null;
    protected $propertyDefinitions = null;
    protected $supertypeNames = null;
    protected $name = null;
    protected $primaryItemName = null;
    protected $hasOrderableChildNodes = false;
    protected $isAbstract = false;
    protected $isMixin = false;
    protected $isQueryable = false;
    protected $midgardInternalProps = array(
        'connection',
        'guid',
        'metadata',
        'action',
        'id',
        'name',
        'parent',
        'parentname',
    );

    public function __construct($name = null, NodeTypeManager $mgr)
    {
        $this->name = $name;
        $this->nodeTypeManager = $mgr;
    }

    private function getStringValue($reflector, $name, $property = null)
    {
        if ($property) {
            // Property reflector
            return $reflector->get_user_value($property, $name);
        }
        // Class reflector
        return $reflector->get_user_value($name);
    }

    private function getBooleanValue($reflector, $name, $property = null)
    {
        $value = $this->getStringValue($reflector, $name, $property);
        if ($value == 'true') {
            return true;
        }
        return false;
    }

    private function createChildNodeDefinition($name, midgard_reflection_class $reflector)
    {
        $template = $this->nodeTypeManager->createNodeDefinitionTemplate();
        $template->setAutoCreated($this->getBooleanValue($reflector, 'isAutoCreated'));

        $primaryTypes = $this->getStringValue($reflector, 'RequiredPrimaryTypes');
        if ($primaryTypes) {
            $primaryTypes = explode(' ', $primaryTypes);
        } else {
            $primaryTypes = array('nt:base');
        }
        $template->setRequiredPrimaryTypeNames($primaryTypes);

        $template->setDefaultPrimaryTypeName($this->getStringValue($reflector, 'DefaultPrimaryType'));
        $template->setMandatory($this->getBooleanValue($reflector, 'isMandatory'));
        $template->setName($name);
        $template->setAutoCreated($this->getBooleanValue($reflector, 'isAutoCreated'));
        $template->setProtected($this->getBooleanValue($reflector, 'isProtected'));
        $template->setSameNameSiblings($this->getBooleanValue($reflector, 'SameNameSiblings'));

        $opv = $this->getStringValue($reflector, 'OnParentVersion');
        if ($opv) {
            $template->setOnParentVersion(OnParentVersionAction::valueFromName($opv));
        }

        return new NodeDefinition($this->getName(), $template, $this->nodeTypeManager);
    }

    public function getDeclaredChildNodeDefinitions() 
    {
        if (!is_null($this->childNodeDefinitions)) {
            return $this->childNodeDefinitions;
        }
        $this->childNodeDefinitions = array();

        $childNames = array();
        $midgardName = NodeMapper::getMidgardName($this->name);
        $reflector = new midgard_reflection_class($midgardName);

        $childDefs = $this->getStringValue($reflector, 'ChildNodeDefinition');
        if (!$childDefs) {
            return $this->childNodeDefinitions;
        }
        
        $childDefs = explode(' ', $childDefs);
        foreach ($childDefs as $childName) {
            if (!$childName) {
                continue;
            }
            $this->childNodeDefinitions[$childName] = $this->createChildNodeDefinition($childName, $reflector);
        }

        return $this->childNodeDefinitions;
    }

    private function createPropertyDefinition($midgardName, $name, midgard_reflection_property $reflector = null)
    {
        $template = $this->nodeTypeManager->createPropertyDefinitionTemplate();
        $template->setName($name);
        if (!$reflector) {
            // This is all we know of the property
            return new PropertyDefinition($this, $template, $this->nodeTypeManager);
        }
        $template->setAutoCreated($this->getBooleanValue($reflector, 'isAutoCreated', $midgardName));
        $template->setRequiredType(NodeMapper::getPHPCRPropertyType(null, $midgardName, $reflector));
        $template->setMandatory($this->getBooleanValue($reflector, 'isMandatory', $midgardName));
        $template->setProtected($this->getBooleanValue($reflector, 'isProtected', $midgardName));
        $template->setMultiple($this->getBooleanValue($reflector, 'isMultiple', $midgardName));

        return new PropertyDefinition($this->getName(), $template, $this->nodeTypeManager);
    }

    public function getDeclaredPropertyDefinitions()
    {
        if (!is_null($this->propertyDefinitions)) {
            return $this->propertyDefinitions;
        }
        $this->propertyDefinitions = array();

        $midgardName = NodeMapper::getMidgardName($this->name);
        $properties = midgard_reflector_object::list_defined_properties($midgardName);
        $reflector = $this->getPropertyReflector();
        foreach ($properties as $property => $value) {
            if (in_array($property, $this->midgardInternalProps)) {
                continue;
            }
            $propertyPHPCR = NodeMapper::getPHPCRProperty($property);
            if (!$propertyPHPCR) {
                continue;
            }
            $this->propertyDefinitions[$propertyPHPCR] = $this->createPropertyDefinition($property, $propertyPHPCR, $reflector);
        }
        return $this->propertyDefinitions;
    }

    private function getPropertyReflector()
    {
       $midgardName = NodeMapper::getMidgardName($this->name);
        if (is_subclass_of($midgardName, 'MidgardDBObject')) {
            return new midgard_reflection_property($midgardName);
        }
        
        // Currently mixin types are not reflectable. Get reflector
        // from a type using mixin
        $nodeTypes = $this->nodeTypeManager->getAllNodeTypes();
        foreach ($nodeTypes as $nodeType) {
            if ($nodeType->isMixin()) {
                continue;
            }

            if ($nodeType->getName() == $this->getName()) {
                continue;
            }

            if (!$nodeType->isNodeType($this->getName())) {
                continue;
            }

            $midgardName = NodeMapper::getMidgardName($nodeType->getName());
            return new midgard_reflection_property($midgardName);
        }
        return null;
    }

    public function getDeclaredSupertypeNames()
    {
        if (!is_null($this->supertypeNames)) {
            return $this->supertypeNames;
        }

        $this->supertypeNames = array();

        // Get supertypes based on PHP hierarchy
        $midgardName = NodeMapper::getMidgardName($this->name);
        $reflector = new ReflectionClass($midgardName);
        $parentReflector = $reflector->getParentClass();
        if ($parentReflector) {
            $crName = NodeMapper::getPHPCRName($parentReflector->getName());
            if ($crName) {
                $this->supertypeNames[] = $crName;
            }
        }

        $reflector = new midgard_reflection_class($midgardName);
        $superTypes = explode(' ', $reflector->get_user_value('Supertypes'));
        foreach ($superTypes as $superType) {
            if (!$superType || $superType == $this->getName()) {
                continue;
            }
            
            if (!in_array($superType, $this->supertypeNames)) {
                $this->supertypeNames[] = $superType;
            }
        }

        if (!$this->isMixin() && $this->getName() != 'nt:base' && !in_array('nt:base', $this->supertypeNames)) {
            // Primary types extend nt:base automatically
            // but only if they don't already extend another
            // primary type
            $extendsPrimary = false;
            foreach ($this->supertypeNames as $superTypeName) {
                $superType = $this->nodeTypeManager->getNodeType($superTypeName);
                if (!$superType->isMixin()) {
                    $extendsPrimary = true;
                }
            }
            if (!$extendsPrimary) {
                $this->supertypeNames[] = 'nt:base';
            }
        }

        return $this->supertypeNames;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrimaryItemName()
    {
        if (!is_null($this->primaryItemName))
        {
            return $this->primaryItemName;
        }

        $mgdName = NodeMapper::getMidgardName($this->name);       
        $this->primaryItemName = midgard_reflector_object::get_schema_value($mgdName, 'PrimaryItemName');
        return $this->primaryItemName;
    }

    public function hasOrderableChildNodes()
    {
        return $this->hasOrderableChildNodes;
    }

    public function isAbstract()
    {
        return $this->isAbstract;
    }

    public function isMixin()
    {
        return $this->isMixin;
    }

    public function isQueryable()
    {
        return $this->isQueryable;
    }
}
