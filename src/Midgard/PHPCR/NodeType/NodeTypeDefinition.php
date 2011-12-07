<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
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

    public function getDeclaredChildNodeDefinitions() 
    {
        if (!is_null($this->childNodeDefinitions)) {
            return $this->childNodeDefinitions;
        }

        $midgardName = NodeMapper::getMidgardName($this->name);
        $this->childNodeDefinitions = array();
        $reflector = new midgard_reflection_class($midgardName);
        $primaryTypes = $reflector->get_user_value('RequiredPrimaryTypes');
        if (empty($primaryTypes)) {
            return array();
        }

        $childName = $reflector->get_user_value('PrimaryItemName');
        if (!$childName) {
            return array();
        }

        return array(new NodeDefinition(null, $childName, $primaryTypes, $this->nodeTypeManager));
    }

    public function getDeclaredPropertyDefinitions()
    {
        if (!is_null($this->propertyDefinitions)) {
            return $this->propertyDefinitions;
        }

        $midgardName = NodeMapper::getMidgardName($this->name);
        $this->propertyDefinitions = array();
        $properties = midgard_reflector_object::list_defined_properties($midgardName);
        foreach ($properties as $property => $value) {
            if (in_array($property, $this->midgardInternalProps)) {
                continue;
            }
            $propertyPHPCR = NodeMapper::getPHPCRProperty($property);
            if (!$propertyPHPCR) {
                continue;
            }
            $this->propertyDefinitions[$propertyPHPCR] = new PropertyDefinition($this, $propertyPHPCR, $this->nodeTypeManager); 
        }
        return $this->propertyDefinitions;
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

    protected function getPropertyReflector($name)
    {
        /* If this is MidgardObject derived property, return null.
         * We have no session available at this point, so check prefix
         * directly */
        if (strpos($name, ':') !== false)
        {
            $parts = explode(':', $name); 
            if ($parts[0] == 'mgd')
            {
                return null;
            }
        }

        $midgardName = NodeMapper::getMidgardName($this->name);
        if (!$midgardName)
        {
            return null;
        }

        $reflector = new midgard_reflection_property($midgardName);
        if (!$reflector)
        {
            return null;
        }

        $midgardPropertyName = NodeMapper::getMidgardPropertyName($name);
        if (!$midgardPropertyName)
        {
            return null;
        }

        $midgardType = $reflector->get_midgard_type($name);
        /* Property is not registered for this type */
        if ($midgardType == MGD_TYPE_NONE)
        {
            return null;
        }

        return $reflector;
    }
}
