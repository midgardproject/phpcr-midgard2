<?php
namespace Midgard2CR;

abstract class Item implements \PHPCR\ItemInterface
{
    protected $session = null;
    protected $object = null;
    protected $parent = null;

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
    }
    
    public function getName()
    {
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
    }

    public function isNew()
    {
    }

    public function isModified()
    {
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
