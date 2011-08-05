<?php
namespace Midgard2CR\NodeType;

class NodeTypeManager implements \IteratorAggregate, \PHPCR\NodeType\NodeTypeManagerInterface
{
    protected $primaryNodeTypes = array();
    protected $mixinNodeTypes = array();

    public function __construct()
    {
        $this->registerStandardTypes();
        $this->registerMidgard2Types();
    }

    private function registerStandardTypes()
    {

        /* TODO, remove this method and add linkedFile to schema */
        /* JCR 2.0 3.7.11 Standard Application Node Types */
     
        /* nt: linkedFile */
        $linkedfile = $this->createNamedNodeTypeTemplate('nt:linkedFile', false);
        $linkedfile->setDeclaredSuperTypeNames(array('mix:created', 'nt:hierarchy'));
        $linkedfile->setPrimaryItemName('jcr:content');
        $this->registerNodeType($linkedfile, false);
    }

    private function registerMidgard2Types()
    {
        /* Register abstract MidgardObject */
        $mgdObject = $this->createNamedNodeTypeTemplate('mgd:object', false);
        $mgdObject->setAbstract(true);
        $this->registerNodeType($mgdObject, false);


        /* Register all types */
        $re = new \ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        foreach ($classes as $refclass)
        {
            $parent_class = $refclass->getParentClass();
            if (!$parent_class)
            {
                continue;
            }

            if ($parent_class->getName() != 'midgard_object')
            {
                continue;
            }
            $tmpName = $refclass->getName();
            if (strpos($tmpName, 'nt_') !== false
                || strpos($tmpName, 'mix_') !== false)
            {
                $mgdschemaName = \MidgardNodeMapper::getPHPCRName($tmpName);
            }   
            else 
            {
                $mgdschemaName = 'mgd:' . $tmpName;
            }
            $mgdschemaType = $this->createNamedNodeTypeTemplate($mgdschemaName, false);
            $mgdschemaType->setDeclaredSuperTypeNames(array('mgd:object'));
            $this->registerNodeType($mgdschemaType, false);
        }
    }

    public function createNodeDefinitionTemplate()
    {
       return new NodeDefinitionTemplate();
    }

    private function createNamedNodeTypeTemplate($name, $mixin)
    {
        $ntt = $this->createNodeTypeTemplate();
        $ntt->setName($name);
        $ntt->setMixin($mixin);

        return $ntt;
    }

    public function createNodeTypeTemplate($ntd = null)
    {
        /* TODO, handle NodeTypeDefinition */
        return new NodeTypeTemplate();
    }

    public function createPropertyDefinitionTemplate()
    {

    }

    public function getAllNodeTypes()
    {
        return new ArrayIterator(array_merge($this->primaryNodeTypes, $this->mixinNodeTypes));
    }

    public function getMixinNodeTypes()
    {
        return new ArrayIterator($this->mixinNodeTypes);
    }

    public function getNodeType($nodeTypeName)
    {
        $nodeTypeName = strtolower($nodeTypeName);
        if (!$this->hasNodeType($nodeTypeName))
        {
            throw new \PHPCR\NodeType\NoSuchNodeTypeException("Node '{$nodeTypeName}' is not registered");
        }

        if (isset($this->primaryNodeTypes[$nodeTypeName]))
        {
            return $this->primaryNodeTypes[$nodeTypeName];
        }
        return $this->mixinNodeTypes[$nodeTypeName];
    }

    public function getPrimaryNodeTypes()
    {
        return new ArrayIterator($this->primaryNodeTypes);
    }

    public function hasNodeType($name)
    {
        if (isset($this->primaryNodeTypes[$name]) || isset($this->mixinNodeTypes[$name]))
        {
            return true;
        }
        return false;
    }

    public function registerNodeType(\PHPCR\NodeType\NodeTypeDefinitionInterface $ntd, $allowUpdate)
    {
        $name = strtolower($ntd->getName());

        /* TODO
         * InvalidNodeTypeDefinitionException */

        if (isset($this->primaryNodeTypes[$name]) || isset($this->mixinNodeTypes[$name]))
        {
            if ($allowUpdate == true)
            {
                throw new \PHPCR\NodeTypeExistsException("Node '{$name}' is already registered");
            }
            return;
        }

        if ($ntd->isMixin() == true)
        {
            $this->mixinNodeTypes[$name] = new NodeType($ntd, $this);
            return;
        }
        
        $this->primaryNodeTypes[$name] = new NodeType($ntd, $this);
    }

    public function registerNodeTypes(array $definitions, $allowUpdate)
    {
        foreach ($definitions as $ntd)
        {
            $this->registerNodeType($ntd, $allowUpdate);
        }
    }

    public function unregisterNodeType($name)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException("Can not unregister '{$name}'");
    }

    public function unregisterNodeTypes(array $names)
    {
        foreach ($names as $name)
        {
            $this->unregisterNodeType($name);
        }
    }

    public function getIterator() 
    {
        return $this->getAllNodeTypes();
    }
}
