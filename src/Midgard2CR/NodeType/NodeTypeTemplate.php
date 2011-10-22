<?php
namespace Midgard\PHPCR\NodeType;
use \ArrayObject;

class NodeTypeTemplate extends NodeTypeDefinition implements \PHPCR\NodeType\NodeTypeTemplateInterface
{
    //protected $childNodeDefinitions = array();
    //protected $propertyDefinitions = array();

    public function getNodeDefinitionTemplates()
    {
       
    }

    public function getPropertyDefinitionTemplates()
    {

    }

    public function setAbstract($toggle)
    {
        $this->isAbstract = $toggle;
    }

    public function setDeclaredSuperTypeNames(array $names)
    {
        $this->supertypeNames = $names;
    }

    public function setMixin($toggle)
    {
        $this->isMixin = $toggle;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setOrderableChildNodes($toggle)
    {
        $this->hasOrderableChildNodes = $toggle;
    }

    public function setPrimaryItemName($name)
    {
        $this->primaryItemName = $name;
    }

    public function setQueryable($toggle)
    {
        $this->isQueryable = $toggle;
    }
}

