<?php
namespace Midgard\PHPCR\NodeType;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\PropertyType;
use PHPCR\ValueFormatException;
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
        $subTypes = array();
        foreach ($this->getDeclaredSubtypes() as $subType) {
            $subTypes[$subType->getName()] = $subType;
            $subTypes = array_merge($subTypes, iterator_to_array($subType->getSubtypes()));
        }
        return new ArrayIterator($subTypes);
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
        if ($this->nodeTypeManager->hasNodeType($propertyName)) {
            return false;
        }

        $definitions = $this->getPropertyDefinitions();
        if (!isset($definitions[$propertyName])) {
            // FIXME: Now MgdSchemas can't define * properties
            // so we special-case nt:unstructured
            return $this->isNodeType('nt:unstructured');
        }

        $requiredType = $definitions[$propertyName]->getRequiredType();
        if ($requiredType) {
            if (is_object($value) && !is_a($value, '\DateTime') && !is_a($value, '\PHPCR\NodeInterface')) {
                return false;
            }
            try {
                PropertyType::convertType($value, $requiredType);
            } catch (ValueFormatException $e) {
                return false;
            }
        }

        return true;
    }

    public function canAddChildNode($nodeName, $nodeTypeName = NULL)
    {
        if ($nodeTypeName && !$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            return false;
        }

        $propDefs = $this->getPropertyDefinitions();
        if (isset($propDefs[$nodeName])) {
            return false;
        }

        $childDefs = $this->getChildNodeDefinitions();
        if (isset($childDefs[$nodeName])) {
            $childDef = $childDefs[$nodeName];
        } elseif (isset($childDefs['*'])) {
            $childDef = $childDefs['*'];
        } else {
            return false;
        }

        if (!$nodeTypeName) {
            $nodeTypeName = $childDef->getDefaultPrimaryTypeName();
            if (!$nodeTypeName) {
                return false;
            }
        }
        
        $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        if ($nodeType->isMixin() || $nodeType->isAbstract()) {
            return false;
        }

        $requiredPrimaryTypes = $childDef->getRequiredPrimaryTypeNames();
        $match = false;
        foreach ($requiredPrimaryTypes as $primary) {
            if ($nodeType->isNodeType($primary)) {
                $match = true;
                break;
            }
        }
        return $match;
    }

    public function canRemoveNode($nodeName)
    {
        $childDefs = $this->getDeclaredChildNodeDefinitions();
        if (isset($childDefs[$nodeName])) {
            $childDef = $childDefs[$nodeName];
        } elseif (isset($childDefs['*'])) {
            $childDef = $childDefs['*'];
        } else {
            return true;
        }

        if ($childDef->isMandatory()) {
            return false;
        }

        if ($childDef->isProtected()) {
            return false;
        }

        return true;
    }

    public function canRemoveProperty($propertyName)
    {
        $definitions = $this->getPropertyDefinitions();
        if (!isset($definitions[$propertyName])) {
            return true;
        }

        if ($definitions[$propertyName]->isMandatory()) {
            return false;
        }

        if ($definitions[$propertyName]->isProtected()) {
            return false;
        }

        return true;
    }
}
