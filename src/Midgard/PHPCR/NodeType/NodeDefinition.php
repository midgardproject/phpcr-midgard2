<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\NodeDefinitionInterface;
use Midgard\PHPCR\Utils\NodeMapper;

class NodeDefinition extends ItemDefinition implements NodeDefinitionInterface
{
    protected $defaultPrimaryTypeName = 'nt:base';
    protected $requiredPrimaryTypeNames = array();
    protected $allowSameNameSiblings = false;
    protected $nodeTypeManager = null;

    public function __construct($declaringType, NodeDefinitionTemplate $template, NodeTypeManager $mgr)
    {
        $this->defaultPrimaryTypeName = $template->getDefaultPrimaryTypeName();
        $this->requiredPrimaryTypeNames = $template->getRequiredPrimaryTypeNames();
        if (empty($this->requiredPrimaryTypeNames)) {
            $this->requiredPrimaryTypeNames[] = $this->defaultPrimaryTypeName;
        }

        $this->allowSameNameSiblings = $template->allowsSameNameSiblings();

        parent::__construct($declaringType, $template, $mgr);
    }

    public function allowsSameNameSiblings() 
    {
        return $this->allowSameNameSiblings;
    }

    public function getDefaultPrimaryType() 
    {
        if (!$this->getDefaultPrimaryTypeName()) {
            return null;
        }
        $this->nodeTypeManager->getNodeType($this->getDefaultPrimaryTypeName());
    }

    public function getDefaultPrimaryTypeName() 
    {
        return $this->defaultPrimaryTypeName;
    }

    public function getRequiredPrimaryTypeNames() 
    {
        return $this->requiredPrimaryTypeNames;
    }

    public function getRequiredPrimaryTypes() 
    {
        $ret = array();
        foreach ($this->getRequiredPrimaryTypeNames() as $typeName) {
            $ret[] = $this->nodeTypeManager->getNodeType($typeName);
        }
        return $ret;
    }
}
