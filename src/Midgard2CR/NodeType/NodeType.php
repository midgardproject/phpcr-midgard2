<?php
namespace Midgard2CR;

class NodeType implements \PHPCR\NodeTypeInterface
{
    protected $classname = null;

    public function __construct($classname) {
        if ($classname === null
            || $classname === "")
        {
            throw new \PHPCR\RepositoryException("Can not initialize NodeType for empty classname");
        }
        
        $mrp = new midgard_reflector_property ($classname);
        if (!$mrp) 
        {
            throw new \PHPCR\NoSuchNodeTypeException("Invalid Node '{$classname}' name"); 
        }

        $this->classname = $classname;
    }

    public function getSupertypes()
    {
        $rv = array();
        $o = midgard_schema_object::factory ($this->classname);
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

        $children = midgard_reflector_object::list_children($this->classname);
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
        $mrp = new midgard_reflector_property ($this->classname);

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
