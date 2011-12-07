<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\NodeDefinitionInterface;
use Midgard\PHPCR\Utils\NodeMapper;

class NodeDefinition extends ItemDefinition implements NodeDefinitionInterface
{
    protected $defaultPrimaryTypeName = null;
    protected $requiredPrimaryTypeNames = array();
    protected $allowSameNameSiblings = false;
    protected $nodeTypeManager = null;

    public function __construct(NodeTypeDefinition $declaringType, NodeDefinitionTemplate $template, NodeTypeManager $mgr)
    {
        $this->nodeTypeManager = $mgr;
        parent::__construct($declaringType, $template);
    }

    public function allowsSameNameSiblings() 
    {
        return $this->allowSameNameSiblings;
    }

    public function getDefaultPrimaryType() 
    {
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
        foreach ($this->getDefaultPrimaryTypeNames() as $typeName) {
            $ret[] = $this->nodeTypeManager->getNodeType($typeName);
        }
        return $ret;
    }
}
