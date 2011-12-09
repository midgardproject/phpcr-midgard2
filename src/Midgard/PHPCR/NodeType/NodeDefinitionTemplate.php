<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\NodeDefinitionTemplateInterface;

class NodeDefinitionTemplate extends NodeDefinition implements NodeDefinitionTemplateInterface
{
    public function __construct()
    {
    }

    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    public function setDefaultPrimaryTypeName($defaultPrimaryTypeName)
    {
        if (!$defaultPrimaryTypeName) {
            $defaultPrimaryTypeName = null;
        }
        $this->defaultPrimaryTypeName = $defaultPrimaryTypeName;
    }

    public function setMandatory($mandatory)
    {
        $this->isMandatory = $mandatory;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setOnParentVersion($opv)
    {
        $this->onParentVersion = $opv;
    }

    public function setProtected($protectedStatus)
    {
        $this->isProtected = $protectedStatus;
    }

    public function setRequiredPrimaryTypeNames(array $requiredPrimaryTypeNames)
    {
        $this->requiredPrimaryTypeNames = $requiredPrimaryTypeNames;
    }

    public function setSameNameSiblings($allowSameNameSiblings)
    {
        $this->allowSameNameSiblings = $allowSameNameSiblings;
    }
}
