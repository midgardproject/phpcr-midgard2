<?php
namespace Midgard2CR;

abstract class Item implements \PHPCR\ItemInterface
{
    protected $session = null;
    protected $object = null;
    protected $parent = null;
    protected $is_new = false;
    protected $is_modified = false;

    public function __construct(\midgard_object $object = null, Node $parent = null, Session $session)
    {
        $this->parent = $parent;
        $this->object = $object;
        $this->session = $session;
    }
    
    public function getMidgard2Object()
    {
        return $this->object;
    }

    public function getPath()
    {
        if (!$this->parent)
        {
            // Root node
            return '/';
        }
        $parent_path = $this->parent->getPath();
        if ($parent_path == '/')
        {
            return "/{$this->getName()}";
        }
        return "{$this->parent->getPath()}/{$this->getName()}";
    }
    
    public function getName()
    {
        if (!$this->parent)
        {
            // Root node
            return '';
        }
        return $this->object->name;
    }

    public function getAncestor($depth)
    {
    }

    public function getParent()
    {
        if (!$this->parent)
        {
            throw new \PHPCR\ItemNotFoundException();
        }
        return $this->parent;
    }

    public function getDepth()
    {
    }

    public function getSession()
    {
        return $this->session;
    }

    public function isNode()
    {
        return true;
    }

    public function isNew()
    {
        return $this->is_new;
    }

    public function isModified()
    {
        return $this->is_modified;
    }

    public function isSame(\PHPCR\ItemInterface $otherItem)
    {
        return false;
    }

    public function accept(\PHPCR\ItemVisitorInterface $visitor)
    {
    }

    public function refresh($keepChanges)
    {
    }

    public function remove()
    {
    }
}
