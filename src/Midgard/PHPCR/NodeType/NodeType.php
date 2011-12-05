<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\PropertyType;
use midgard_reflector_object;
use ArrayIterator;

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
            return new ArrayIterator($this->subTypeDefinitions);
        }

        $this->subTypeDefinitions = array();
        $types = $this->nodeTypeManager->getAllNodeTypes();
        foreach ($types as $type) {
            $superTypes = $type->getDeclaredSuperTypeNames();
            if (in_array($this->getName(), $superTypes)) {
                $this->subTypeDefinitions[$type->getName()] = $type;
            }
        }

        return new ArrayIterator($this->subTypeDefinitions);
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

        if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            return false;
        }

        $superTypes = $this->getSupertypes();
        foreach ($superTypes as $superType) {
            if ($superType->getName() == $nodeTypeName) {
                return true;
            }
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
        return $this->getDeclaredChildNodeDefinitions();
    }

    public function canSetProperty($propertyName, $value)
    {
        $definitions = $this->getPropertyDefinitions();
        if (!isset($definitions[$propertyName])) {
            return true;
        }

        $requiredType = $definitions[$propertyName]->getRequiredType();
        if ($requiredType) {
            if (PropertyType::determineType($value) != $requiredType) {
                return false;
            }
        }
        // FIXME: We need a list of allowed property names
        return true;
    }

    public function canAddChildNode($childNodeName, $nodeTypeName = NULL)
    {
        if (!$nodeTypeName) {
            // FIXME: We need a list of allowed child node names
            return true;
        }
        $childNodeDefs = $this->getDeclaredChildNodeDefinitions();
        if (!$childNodeDefs) {
            return true;
        }

        foreach ($childNodeDefs as $def) {
            if ($def->isNodeType($nodeTypeName)) {
                return true;
            }
        }
        return false;
    }

    public function canRemoveNode($nodeName)
    {
        // FIXME: We need list of mandatory child nodes
        return true;
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
