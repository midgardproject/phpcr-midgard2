<?php
namespace Midgard2CR;

class Item implements \PHPCR\ItemInterface
{
    protected $session = null;
    protected $object = null;

    public function __construct(\midgard_object $object = null, Session $session)
    {
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
