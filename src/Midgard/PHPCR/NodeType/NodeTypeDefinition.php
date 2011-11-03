<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;

class NodeTypeDefinition implements \PHPCR\NodeType\NodeTypeDefinitionInterface
{
    protected $childNodeDefinitions = array();
    protected $propertyDefinitions = array();
    protected $supertypeNames = array();
    protected $name = null;
    protected $primaryItemName = null;
    protected $hasOrderableChildNodes = false;
    protected $isAbstract = false;
    protected $isMixin = false;
    protected $isQueryable = false;

    public function getDeclaredChildNodeDefinitions() 
    {
        return $this->childNodeDefinitions;
    }

    public function getDeclaredPropertyDefinitions()
    {
        return $this->propertyDefinitions;
    }

    public function getDeclaredSupertypeNames()
    {
        return $this->supertypeNames;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrimaryItemName()
    {
        if ($this->primaryItemName)
        {
            return $this->primaryItemName;
        }

        $mgdName = NodeMapper::getMidgardName ($this->getName());       
        return \midgard_reflector_object::get_schema_value ($mgdName, "PrimaryItemName");
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
