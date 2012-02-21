<?php
namespace Midgard\PHPCR;

use \midgard_node;

class SessionTracker 
{
    protected $session = null;
    protected $newNodes = null;
    protected $modifiedNodes = null;
    protected $removedNodes = null;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    private function getSessions() 
    {
        $repository = $this->session->getRepository();
        $sessions = $repository->getSessions();

        if (empty($sessions)) {
            return array();
        }        

        return $sessions;
    } 

    public function setRemovedNode($midgardNode, $args = null)
    { 
        $sessions = $this->getSessions();
        foreach($sessions as $name => $s) {
            if ($s->getName() == $this->session->getName()) {
                continue;
            }
            $s->getSessionTracker()->removedNodes[] = $midgardNode->guid; 
        }
    }

    public function setModifiedNode($midgardNode, $args = null)
    {  
        $sessions = $this->getSessions();
        foreach($sessions as $name => $s) {
            if ($s->getName() == $this->session->getName()) {
                continue;
            }
            $s->getSessionTracker()->modifiedNodes[] = $midgardNode->guid; 
        }
    }

    public function removeNodes()
    {
        if (empty($this->removedNodes)) {
            return;
        }

        foreach ($this->removedNodes as $guid) {
            try {
                $node = $this->session->getNodeRegistry()->getByMidgardGuid($guid);
                $node->remove();
            } catch (\PHPCR\ItemNotFoundException $e) {
                /* Do nothing */
            }
        }
    }

    public function modifyNodes()
    {
        if (empty($this->modifiedNodes)) {
            return;
        }

        foreach ($this->modifiedNodes as $guid) {
            try {
                $node = $this->session->getNodeRegistry()->getByMidgardGuid($guid);
                /* TODO */
                /* $node->save(); */
            } catch (\PHPCR\ItemNotFoundException $e) {
                /* Do nothing */
            }
        }
    }

    public function trackNode(midgard_node $node)
    {
        $node->connect('action-purged', array ($this, 'setRemovedNode'), array());
        //$node->connect('action-updated', array ($this, 'setModifiedNode'), array());
    }
}
