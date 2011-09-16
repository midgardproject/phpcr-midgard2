<?php
namespace Midgard2CR\NodeType;

use Midgard2CR\Utils\NodeMapper;

class NodeTypeManager implements \IteratorAggregate, \PHPCR\NodeType\NodeTypeManagerInterface
{
    protected $primaryNodeTypes = array();
    protected $mixinNodeTypes = array();

    public function __construct()
    {
        $this->registerMidgard2Types();
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
            $ignore = true;
            if ($refclass->isSubclassOf('MidgardObject')
                || $refclass->isSubclassOf('MidgardBaseMixin')
                || $refclass->isSubclassOf('MidgardBaseInterface'))
            {
                $ignore = false;
            }

            if ($refclass->isAbstract())
            {
                $ignore = true;
            }

            if ($ignore == true)
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
        $name = $ntd->getName();

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
