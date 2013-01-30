<?php
namespace Midgard\PHPCR\NodeType;

use PHPCR\NodeType\NodeTypeManagerInterface;
use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\NodeTypeExistsException;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\NodeType\NoSuchNodeTypeException;
use ArrayIterator;
use ReflectionExtension;
use midgard_reflector_object;
use IteratorAggregate;

class NodeTypeManager implements IteratorAggregate, NodeTypeManagerInterface
{
    protected $primaryNodeTypes = array();
    protected $mixinNodeTypes = array();

    public function __construct()
    {
        $this->registerMidgard2Types();
    }

    /**
     * Register PHPCR Node Types based on Midgard2 schemas
     */
    private function registerMidgard2Types()
    {
        $re = new ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        foreach ($classes as $refclass)
        {
            $mixin = false;
            $abstract = false;
            $ignore = true;
            if (   $refclass->isSubclassOf('MidgardObject')
                || $refclass->isSubclassOf('MidgardBaseMixin')
                || $refclass->isSubclassOf('MidgardBaseInterface')) {
                $ignore = false;
            }

            if ($refclass->isAbstract()) {
                $ignore = true;
            }

            $tmpName = $refclass->getName();
            if ($tmpName == 'MidgardBaseMixin') {
                $ignore = true;
            }

            if ($ignore == true) {
                continue;
            }

            $mgdschemaName = NodeMapper::getPHPCRName($tmpName);

            if (midgard_reflector_object::get_schema_value($tmpName, 'isMixin') == 'true') {
                $mixin = true;
            }

            if (midgard_reflector_object::get_schema_value($tmpName, 'isAbstract') == 'true') {
                $abstract = true;
            }

            $mgdschemaType = $this->createNamedNodeTypeTemplate($mgdschemaName, $mixin, $abstract);
 
            $this->registerNodeType($mgdschemaType, false);
        }
    }

    public function createNodeDefinitionTemplate()
    {
       return new NodeDefinitionTemplate();
    }

    private function createNamedNodeTypeTemplate($name, $mixin, $abstract)
    {
        $ntt = $this->createNodeTypeTemplate();
        $ntt->setName($name);
        $ntt->setMixin($mixin);
        $ntt->setAbstract($abstract);

        return $ntt;
    }

    public function createNodeTypeTemplate($ntd = null)
    {
        return new NodeTypeTemplate($ntd, $this);
    }

    public function createPropertyDefinitionTemplate()
    {
        return new PropertyDefinitionTemplate();
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
        if (!$this->hasNodeType($nodeTypeName)) {
            throw new NoSuchNodeTypeException("Node Type '{$nodeTypeName}' is not registered");
        }

        if (isset($this->primaryNodeTypes[$nodeTypeName])) {
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
        if (isset($this->primaryNodeTypes[$name]) || isset($this->mixinNodeTypes[$name])) {
            return true;
        }
        return false;
    }

    public function registerNodeType(NodeTypeDefinitionInterface $ntd, $allowUpdate)
    {
        $name = $ntd->getName();

        // TODO: InvalidNodeTypeDefinitionException

        if (isset($this->primaryNodeTypes[$name]) || isset($this->mixinNodeTypes[$name])) {
            if (!$allowUpdate) {
                throw new NodeTypeExistsException("Node '{$name}' is already registered");
            }
            return;
        }

        if ($ntd->isMixin()) {
            $this->mixinNodeTypes[$name] = new NodeType($ntd, $this);
            return;
        }
        
        $this->primaryNodeTypes[$name] = new NodeType($ntd, $this);
    }

    public function registerNodeTypes(array $definitions, $allowUpdate)
    {
        foreach ($definitions as $ntd) {
            $this->registerNodeType($ntd, $allowUpdate);
        }
    }

    public function unregisterNodeType($name)
    {
        throw new UnsupportedRepositoryOperationException("Can not unregister '{$name}'");
    }

    public function unregisterNodeTypes(array $names)
    {
        foreach ($names as $name) {
            $this->unregisterNodeType($name);
        }
    }

    public function getIterator() 
    {
        return $this->getAllNodeTypes();
    }

    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        /* Wait till CNS parser is available in phpcr-utils package */
        throw new UnsupportedRepositoryOperationException("Can not register from CND");
    }
}
