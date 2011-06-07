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
    
        /* JCR 2.0 3.7.11 Standard Application Node Types */
        /* nt:hierarchy */
        $hierarchy = $this->createNamedNodeTypeTemplate('nt:hierarchyNode', false);
        $hierarchy->setAbstract(true);
        $hierarchy->setDeclaredSuperTypeNames(array('mix:created'));
        $this->registerNodeType($hierarchy, false);

        /* nt:folder */
        $folder = $this->createNamedNodeTypeTemplate('nt:folder', false);
        $folder->setDeclaredSuperTypeNames(array('mix:created', 'nt:hierarchy'));
        $this->registerNodeType($folder, false);

        /* nt:file */
        $file = $this->createNamedNodeTypeTemplate('nt:file', false);
        $file->setDeclaredSuperTypeNames(array('mix:created', 'nt:hierarchy'));
        $file->setPrimaryItemName('jcr:content');
        $this->registerNodeType($file, false);

        /* nt: linkedFile */
        $linkedfile = $this->createNamedNodeTypeTemplate('nt:linkedFile', false);
        $linkedfile->setDeclaredSuperTypeNames(array('mix:created', 'nt:hierarchy'));
        $linkedfile->setPrimaryItemName('jcr:content');
        $this->registerNodeType($linkedfile, false);

        /* nt:resource */
        $res = $this->createNamedNodeTypeTemplate('nt:resource', false);
        $res->setDeclaredSuperTypeNames(array('mix:mimeType', 'mix:LastModified'));
        $res->setPrimaryItemName('jcr:data');
        $this->registerNodeType($res, false);

        $address = $this->createNamedNodeTypeTemplate('nt:address', false);
        $this->registerNodeType($address, false);

        /* mixins */

        /* mix:title */
        $title = $this->createNamedNodeTypeTemplate('mix:title', true);
        $this->registerNodeType($title, false);

        /* mix:created */
        $created = $this->createNamedNodeTypeTemplate('mix:created', true);
        $this->registerNodeType($created, false);
       
        /* mix:lastModified */
        $lastModified = $this->createNamedNodeTypeTemplate('mix:lastModified', true);
        $this->registerNodeType($lastModified, false);

        /* mix:language */
        $language = $this->createNamedNodeTypeTemplate('mix:language', true);
        $this->registerNodeType($language, false);

        /* mix:mimeType */
        $mimeType = $this->createNamedNodeTypeTemplate('mix:mimeType', true);
        $this->registerNodeType($mimeType, false);

        /* mix:etag */
        $etag = $this->createNamedNodeTypeTemplate('mix:etag', true);
        $this->registerNodeType($etag, false);
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
            $mgdschemaName = 'mgd:' . $refclass->getName();
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
        $this->mixinNodeTypes[$nodeTypeName];
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
