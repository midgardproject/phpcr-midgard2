<?php
namespace Midgard\PHPCR\NodeType;

use Midgard2CR\Utils\NodeMapper;

class NodeType extends NodeTypeDefinition implements \PHPCR\NodeType\NodeTypeInterface
{
    protected $manager = null;

    public function __construct($ntt, NodeTypeManager $manager) {
        $this->name = $ntt->getName();
        if ($this->name === null
            || $this->name === "")
        {
            throw new \PHPCR\RepositoryException("Can not initialize NodeType for empty name");
        }

        $this->childNodeDefinitions = $ntt->getDeclaredChildNodeDefinitions();
        $this->propertyDefinitions = $ntt->getDeclaredPropertyDefinitions();
        $this->supertypeNames = $ntt->getDeclaredSuperTypeNames();
        $this->primaryItemName = $ntt->getPrimaryItemName();
        $this->hasOrderableChildNodes = $ntt->hasOrderableChildNodes();
        $this->isAbstract = $ntt->isAbstract();
        $this->isMixin = $ntt->isMixin();
        $this->isQueryable = $ntt->isQueryable();

        $this->manager = $manager;
    }

    public function getSupertypes()
    {
        $rv = array();
        $o = \midgard_schema_object::factory ($this->classname);
        $parentname = $o->parent();

        if ($parentname != null)
        {
            $rv[] = new NodeType ($parentname);
        }

        return $rv;
    }

    public function getDeclaredSupertypes()
    {
        return $this->getSupertypes();
    }

    public function getSubtypes()
    {
        $rv = array();

        $children = \midgard_reflector_object::list_children($this->classname);
        if (!is_empty($children))
        {
            foreach ($children as $name => $v)
            {
                $children[$name] = new NodeType($name);
            }
        }

        return $rv;

    }

    public function getDeclaredSubtypes()
    {
        return $this->getSubtypes();
    }

    public function isNodeType($nodeTypeName)
    {
        if ($this->classname === $nodeTypeName)
        {
            return true;
        }

        if (in_array ($nodeTypeName, $this->getSupertypes()))
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
        $mrp = new \midgard_reflector_property ($this->classname);

        // Property is registered for GObject, so we can not remove it
        $mtype = $mrp->get_midgard_type ($propertyName);
        if ($mtype > 0) 
        {
            return false;
        }

        // Otherwise we should be able to do this
        
        return true;
    }
}
