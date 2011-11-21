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
        if ($this->name === $nodeTypeName) {
            return true;
        }

        if (in_array($nodeTypeName, $this->getDeclaredSupertypeNames())) {
            return true;
        }

        return false;
    }

    public function hasRegisteredProperty($name)
    {
        $properties = $this->getPropertyDefinitions();
        return isset($properties[$name]);
    }

    public function getPropertyDefinitions()
    {
        return $this->getDeclaredPropertyDefinitions();
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
        $definitions = $this->getPropertyDefinitions();
        if (!isset($definitions[$propertyName])) {
            return true;
        }

        if ($definitions[propertyName]->isMandatory()) {
            return false;
        }

        return true;
    }
}
