<?php
namespace Midgard2CR\NodeType;
\use ArrayObject;

public class NodeTypeTemplate implements \PHPCR\NodeTypeTemplateInterface
{
    //protected $childNodeDefinitions = array();
    //protected $propertyDefinitions = array();

    public function getNodeDefinitionTemplates()
    {
       
    }

    public function getPropertyDefinitionTemplates()
    {

    }

    public function setAbstract(bool $toggle)
    {
        $this->isAbstract = $toggle;
    }

    public function setDeclaredSuperTypeNames(array $names)
    {
        $this->supertypeNames = $names;
    }

    public function setMixin(bool $toggle)
    {
        $this->isMixin = $toggle;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setOrderableChildNodes(bool $toggle)
    {
        $this->hasOrderableChildNodes = $toggle;
    }

    public function setPrimaryItemName($name)
    {
        $this->primaryItemName = $name;
    }

    public function setQueryable(bool $toggle)
    {
        $this->isQueryable = $toggle;
    }
}

