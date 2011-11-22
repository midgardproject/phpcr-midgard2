<?php
namespace Midgard\PHPCR;

use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface; 
use PHPCR\ItemNotFoundException; 
use midgard_object_class;

abstract class Item implements ItemInterface
{
    protected $session = null;
    protected $parent = null;
    protected $is_new = false;
    protected $is_modified = false;
    protected $contentObject = null;
    protected $midgardNode = null;
    protected $propertyManager = null;

    public function getMidgard2ContentObject()
    {
        if (is_null($this->contentObject)) {
            $guid = $this->midgardNode->objectguid;
            if ($guid == '') {
                $guid = null;
            }
            $typename = $this->midgardNode->typename ? $this->midgardNode->typename : 'nt_unstructured';
            $this->contentObject = midgard_object_class::factory($typename, $guid);
        }
        return $this->contentObject;
    }

    public function getMidgard2Node()
    {
        return $this->midgardNode;
    }

    abstract protected function populateParent();

    public function getPath()
    {
        if (!$this->parent) {
            $this->populateParent();
            if (!$this->parent) {
                /* Root node probably */
                return '/';
            }
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
        if (!$this->parent) {
            // Root node
            return '';
        }
        return $this->midgardNode->name;
    }

    public function getAncestor($depth)
    {
        if ($depth < 0 || $depth > $this->getDepth()) {
            throw new ItemNotFoundException("Invalid depth ({$depth}) value.");
        }

        /* n is the depth of this Item, which returns this Item itself. */
        if ($depth == $this->getDepth()) {
            return $this;
        }

        $ancestor = $this;

        while (true) {
            try {
                $ancestor = $ancestor->getParent();
                if ($ancestor->getDepth() == $depth) {
                    break;
                }
            } 
            catch (ItemNotFoundException $e) {
                $ancestor = $this->getSession()->getRootNode();
                break;
            }
        }

        if ($ancestor != null) { 
            return $ancestor;
        }

        throw new ItemNotFoundException("No item found at depth {$depth}");
    }

    public function getParent()
    {
        $this->populateParent();
        if (!$this->parent) {
            throw new ItemNotFoundException();
        }
        return $this->parent;
    }

    public function getDepth()
    {
        try {
            $parent = $this->getParent();
            return $parent->getDepth() + 1;
        } 
        catch (ItemNotFoundException $e) {
            return 0;
        }
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

    public function isSame(ItemInterface $otherItem)
    {
        return false;
    }

    public function accept(ItemVisitorInterface $visitor)
    {
        $visitor->visit($this);
    }

    public function refresh($keepChanges)
    {
    }

    public function remove()
    {
    }
}
