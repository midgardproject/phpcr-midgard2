<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\ItemDefinitionInterface;
use PHPCR\NodeType\NodeTypeInterface;

class ItemDefinition implements ItemDefinitionInterface
{
    protected $declaringNodeType = null;
    protected $name = null;
    protected $onParentVersion = 0;
    protected $isAutoCreated = false;
    protected $isMandatory = false;
    protected $isProtected = false;

    public function __construct($declaringType, ItemDefinitionInterface $template, NodeTypeManager $mgr)
    {
        $this->nodeTypeManager = $mgr;

        $this->declaringNodeType = $declaringType;
        $this->name = $template->getName();
        $this->onParentVersion = $template->getOnParentVersion();
        $this->isAutoCreated = $template->isAutoCreated();
        $this->isMandatory = $template->isMandatory();
        $this->isProtected = $template->isProtected();
    }

    public function getDeclaringNodeType()
    {
        return $this->nodeTypeManager->getNodeType($this->declaringNodeType);
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
