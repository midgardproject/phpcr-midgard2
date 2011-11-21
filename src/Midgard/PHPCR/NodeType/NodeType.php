<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use midgard_reflector_object;

class NodeType extends NodeTypeDefinition implements NodeTypeInterface
{
    protected $subTypeDefinitions = null;

    public function __construct(NodeTypeDefinitionInterface $ntt, NodeTypeManager $manager)
    {
        if (!$ntt->getName()) {
            throw new \PHPCR\RepositoryException("Can not initialize NodeType for empty name");
        }
        parent::__construct($ntt->getName(), $manager);

        $this->childNodeDefinitions = $ntt->getDeclaredChildNodeDefinitions();
        $this->propertyDefinitions = $ntt->getDeclaredPropertyDefinitions();
        $this->supertypeNames = $ntt->getDeclaredSuperTypeNames();
        $this->primaryItemName = $ntt->getPrimaryItemName();
        $this->hasOrderableChildNodes = $ntt->hasOrderableChildNodes();
        $this->isAbstract = $ntt->isAbstract();
        $this->isMixin = $ntt->isMixin();
        $this->isQueryable = $ntt->isQueryable();
    }

    public function getSupertypes()
    {
        $superTypes = array();
        foreach ($this->getDeclaredSupertypes() as $superType) {
            $superTypes[] = $superType;
            $superTypes = array_merge($superTypes, $superType->getSupertypes());
        }
        return $superTypes;
    }

    public function getDeclaredSubtypes()
    {
        if (!is_null($this->subTypeDefinitions)) {
            return $this->subTypeDefinitions;
        }

        $midgardName = NodeMapper::getMidgardName($this->name);
        $children = midgard_reflector_object::list_children($midgardName);
        $this->subTypeDefinitions = array();

        if (!$children) {
            return $this->childNodeDefinitions;
        }

        foreach ($children as $name => $v) {
            $childName = NodeMapper::getPHPCRName($name);
            $childDefinition = new NodeTypeDefinition($childName, $this->nodeTypeManager);
            $this->subTypeDefinitions[$childName] = $childDefinition;
        }
        return $this->subTypeDefinitions;
    }

    public function getSubtypes()
    {
        return $this->getDeclaredSubTypes();
    }

    public function getDeclaredSupertypes()
    {
        $superTypes = array();
        foreach ($this->getDeclaredSupertypeNames() as $superType) {
            $superTypes[] = $this->nodeTypeManager->getNodeType($superType);
        }
        return $superTypes;
    }

    public function isNodeType($nodeTypeName)
    {
        if ($this->name === $nodeTypeName)
        {
            return true;
        }

        if (in_array($nodeTypeName, $this->getDeclaredSupertypeNames()))
        {
            return true;
        }

        return false;
    }

    private function getPropertyReflector($name)
    {
        /* If this is MidgardObject derived property, return null.
         * We have no session available at this point, so check prefix
         * directly */
        if (strpos($name, ':') !== false)
        {
            $parts = explode(':', $name); 
            if ($parts[0] == 'mgd')
            {
                return null;
            }
        }

        $midgardName = NodeMapper::getMidgardName($this->name);
        if (!$midgardName)
        {
            return null;
        }

        $reflector = new \midgard_reflection_property($midgardName);
        if (!$reflector)
        {
            return null;
        }

        $midgardPropertyName = NodeMapper::getMidgardPropertyName($name);
        if (!$midgardPropertyName)
        {
            return null;
        }

        $midgardType = $reflector->get_midgard_type($name);
        /* Property is not registered for this type */
        if ($midgardType == MGD_TYPE_NONE)
        {
            return null;
        }

        return $reflector;
    }

    public function hasRegisteredProperty($name)
    {
        $reflector = $this->getPropertyReflector($name);
        if ($reflector == null)
        {
            return false;
        }
        return true;
    }

    public function getPropertyDefinition($name)
    {
    }

    public function getPropertyDefinitions()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getChildNodeDefinitions()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function canSetProperty($propertyName, $value)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function canAddChildNode($childNodeName, $nodeTypeName = NULL)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function canRemoveNode($nodeName)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function canRemoveProperty($propertyName)
    {
        // Determine if property is registered for MgdSchema class
        $mrp = $this->getPropertyReflector($propertyName);
        if ($mrp) {
            // Property is registered for GObject, so we can not remove it
            $mtype = $mrp->get_midgard_type($propertyName);
            if ($mtype > 0) 
            {
                return false;
            }
        }

        // Otherwise we should be able to do this
        // FIXME: Check whether the property is mandatory 
        return true;
    }
}
