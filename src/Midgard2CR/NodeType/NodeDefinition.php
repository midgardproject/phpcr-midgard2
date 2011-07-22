<?php
namespace Midgard2CR\NodeType;

class NodeDefinition implements \PHPCR\NodeType\NodeDefinitionInterface
{
    protected $node = null;
    protected $midgardNode = null;
    protected $typename = null;

    public function __construct(\Midgard2CR\Node $node)
    {
        $this->node = $node;
        $this->midgardNode = $node->getMidgard2Node();
        $this->typename = $this->midgardNode->typename;
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
        $primaryType = \midgard_object_class::get_schema_value($this->typename, 'DefaultPrimaryType'); 
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
        /* TODO */
        return null;
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
