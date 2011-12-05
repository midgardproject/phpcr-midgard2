<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\NodeDefinitionInterface;
use Midgard\PHPCR\Utils\NodeMapper;

class NodeDefinition implements NodeDefinitionInterface
{
    protected $name = null;
    protected $node = null;
    protected $midgardNode = null;
    protected $typename = null;

    public function __construct(\Midgard\PHPCR\Node $node = null, $name= null, $typename = null, NodeTypeManager $mgr)
    {
        $this->node = $node;
        if ($node) {
            $this->midgardNode = $node->getMidgard2Node();
            $this->typename = $this->midgardNode->typename;
            $this->name = $node->getName();
        } else {
            $this->name = $name;
            $this->typename = $typename;
        }

        $this->nodeTypeManager = $mgr;
    }

    private function getBooleanSchemaValue($name)
    {
        $value = \midgard_object_class::get_schema_value($this->typename, $name);
        if ($value == 'true')
        {
            return true;
        }
        return false;
    }

    public function allowsSameNameSiblings() 
    {
        return $this->getBooleanSchemaValue('SameNameSiblings');
    }

    public function getDefaultPrimaryType() 
    {   
        /* TODO */
        return null;
    }

    public function getDefaultPrimaryTypeName() 
    {
        $typename = 'nt_folder';
        if ($this->typename)
        {
            $typename = $this->typename;
        } 
        $primaryType = \midgard_object_class::get_schema_value($typename, 'DefaultPrimaryType'); 
        if ($primaryType == '')
        {
            return null;
        }
        return $primaryType;
    }

    public function getRequiredPrimaryTypeNames() 
    {
        /* TODO */
        return array();
    }

    public function getRequiredPrimaryTypes() 
    {
        /* TODO */
        return array();
    }

    public function getDeclaringNodeType()
    {
        return $this->nodeTypeManager->getNodeType(NodeMapper::getPHPCRName($this->typename));
    }

    public function getName()
    {
        return $this->name;
    }

    public function getOnParentVersion()
    {
        $opv = \midgard_object_class::get_schema_value($this->typename, 'OnParentVersion'); 
        if ($opv == '')
        {
            return -1; /* FIXME */
        }
        return (int)$opv;

        return $this->onParentVersion;
    }

    public function isAutoCreated()
    {
        return $this->getBooleanSchemaValue('isAutoCreated');
    }

    public function isMandatory()
    {  
        return $this->getBooleanSchemaValue('isMandatory');
    }

    public function isProtected() 
    {     
        return $this->getBooleanSchemaValue('isProtected');
    }

}
