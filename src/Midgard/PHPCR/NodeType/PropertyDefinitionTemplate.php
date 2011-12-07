<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\PropertyDefinitionTemplateInterface;

class PropertyDefinitionTemplate extends PropertyDefinition implements PropertyDefinitionTemplateInterface
{
    public function __construct()
    {
    }

    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    public function setAvailableQueryOperators(array $operators)
    {
        $this->availableQueryOperators = $operators;
    }

    public function setQueryOrderable($queryOrderable)
    {
        $this->queryOrderable = $queryOrderable;
    }

    public function setValueConstraints(array $constraints)
    {
        $this->valueConstraints = $constraints;
    }

    public function setRequiredType($type)
    {
        $this->requiredType = $type;
    }

    public function setMultiple($multiple)
    {
        $this->isMultiple = $multiple;
    }

    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;
    }

    public function setFullTextSearchable($fullTextSearchable)
    {
        $this->fullTextSearchable = $fullTextSearchable;
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
}
