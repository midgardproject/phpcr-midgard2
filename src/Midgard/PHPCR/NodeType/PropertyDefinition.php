<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\PropertyType;
use midgard_reflection_property;

class PropertyDefinition extends ItemDefinition implements PropertyDefinitionInterface
{
    protected $availableQueryOperators = array();
    protected $queryOrderable = false;
    protected $defaultValues = array();
    protected $valueConstraints = array();
    protected $requiredType = 0;
    protected $isMultiple = false;
    protected $fullTextSearchable = false;

    public function __construct(NodeTypeDefinition $declaringType, PropertyDefinitionTemplate $template, NodeTypeManager $mgr)
    {
        $this->availableQueryOperators = $template->getAvailableQueryOperators();
        $this->queryOrderable = $template->isQueryOrderable();
        $this->defaultValues = $template->getDefaultValues();
        $this->valueConstraints = $template->getValueConstraints();
        $this->requiredType = $template->getRequiredType();
        $this->isMultiple = $template->isMultiple();
        $this->fullTextSearchable = $template->isFullTextSearchable();

        parent::__construct($declaringType, $template, $mgr);
    }

    public function getAvailableQueryOperators() 
    {
        return $this->availableQueryOperators;
    }

    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    public function getRequiredType()
    {
        return $this->requiredType;
    }

    public function getValueConstraints()
    {
        return $this->valueConstraints;
    }

    public function isFullTextSearchable()
    {
        return $this->fullTextSearchable;
    }

    public function isMultiple()
    {
        return $this->isMultiple;
    }

    public function isQueryOrderable()
    {
        return $this->queryOrderable;
    }
}
