<?php
namespace Midgard2CR\NodeType;

class ItemDefinition implements \PHPCR\NodeType\ItemDefinitionInterface
{
    protected $declaringNodeType = null;
    protected $name = null;
    protected $onParentVersion = 0;
    protected $isAutoCreated = false;
    protected $isMandatory = false;
    protected $isProtected = false;

    public function getDeclaringNodeType()
    {
        return $this->declaringNodeType;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getOnParentVersion()
    {
        return $this->onParentVersion;
    }

    public function isAutoCreated()
    {
        return $this->isAutoCreated;
    }

    public function isMandatory()
    {
        return $this->isMandatory;
    }

    public function isProtected()
    {
        return $this->isProtected;
    }

}
