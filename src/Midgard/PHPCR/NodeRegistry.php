<?php
namespace Midgard\PHPCR;

use PHPCR\RepositoryException;
use PHPCR\ItemNotFoundException;
use midgard_node;
use midgard_query_storage;
use midgard_query_select;
use midgard_query_constraint_group;
use midgard_query_constraint;
use midgard_query_property;
use midgard_query_value;

class NodeRegistry
{
    private $byGuid = array();
    private $byUuid = array();
    private $byPath = array();
    private $session = null;

    public function __construct(midgard_node $root, Session $session)
    {
        $this->session = $session;
        $this->getByMidgardNode($root);
    }

    public function getByMidgardNode(midgard_node $node)
    {
        if ($node->guid && isset($this->byGuid[$node->guid])) {
            return $this->getByMidgardGuid($node->guid);
        }

        $parent = null;
        $path = null;
        if ($node->parentguid) {
            $parent = $this->getByMidgardGuid($node->parentguid);
        }

        $crNode = new Node($node, $parent, $this->session);
        $this->registerNode($crNode);
        return $crNode;
    }

    public function registerNode(Node $node)
    {
        $path = $node->getPath();
        if ($path) {
            $this->byPath[$path] = $node;
        }

        if ($node->getMidgard2Node()->guid) {
            $this->byGuid[$node->getMidgard2Node()->guid] = $node;
        }

        if ($node->hasProperty('jcr:uuid')) {
            $this->byUuid[$node->getPropertyValue('jcr:uuid')] = $node;
        }
    }

    public function getByPath($path)
    {
        if (!isset($this->byPath[$path])) {
            $this->fetchByPath($path);
        }
        return $this->byPath[$path];
    }

    public function fetchByPath($path)
    {
        if (substr($absPath, 0, 1) != '/') {
            throw new RepositoryException("Full path required. Given one is '{$absPath}'");
        }

        if (strpos($absPath, '//') !== false) {
            throw new RepositoryException("Invalid path '{$absPath}'");
        }

        $node = $this->getByPath('/')->getNode(substr($absPath, 1));
        $this->registerNode($node);
        return $node;
    }

    public function getByMidgardGuid($guid)
    {
        if (!isset($this->byGuid[$guid])) {
            $this->fetchByMidgardGuid($guid);
        }
        return $this->byGuid[$guid];
    }

    public function fetchByMidgardGuid($guid)
    {
        /* Replace this with 'new midgard_node'
         * Once, workspace bug is fixed:
         * https://github.com/midgardproject/midgard-core/issues/129
         */ 
        $qst = new midgard_query_storage('midgard_node');
        $select = new midgard_query_select($qst);
        $select->toggle_readonly(false);
        $select->set_constraint(
            new midgard_query_constraint(
                new midgard_query_property('guid'),
                '=',
                new midgard_query_value($guid)
            )
        );
        $select->execute();
        if ($select->get_results_count() < 1) {
            throw new ItemNotFoundException("Node idenfitied by GUID {$guid} not found.");
        }

        $nodes = $select->list_objects();
        return $this->getByMidgardNode($nodes[0]);
    }

    public function getByUuid($uuid)
    {
        if (!isset($this->byUuid[$uuid])) {
            $this->fetchByUuid($uuid);
        }
        return $this->byUuid[$uuid];
    }

    public function fetchByUuid($uuid)
    {
        $propertyStorage = new midgard_query_storage('midgard_node_property');
        $q = new midgard_query_select(new midgard_query_storage('midgard_node'));
        $q->add_join(
            'INNER',
            new midgard_query_property('id'),
            new midgard_query_property('parent', $propertyStorage)
        );
        $group = new midgard_query_constraint_group('OR');
        $group->add_constraint(
            new midgard_query_constraint(
                new midgard_query_property('guid'),
                '=',
                new midgard_query_value($uuid)
            )
        );
        $uuidGroup = new midgard_query_constraint_group('AND');
        $uuidGroup->add_constraint(
            new midgard_query_constraint(
                new midgard_query_property('value', $propertyStorage), 
                '=', 
                new midgard_query_value($uuid)
            )
        );
        $uuidGroup->add_constraint(
            new midgard_query_constraint(
                new midgard_query_property('name', $propertyStorage), 
                '=', 
                new midgard_query_value('jcr-uuid')
            )
        ); 
        $group->add_constraint($uuidGroup);
        $q->set_constraint($group);
        $q->execute();
        if ($q->get_results_count() < 1) {
            throw new ItemNotFoundException("Node idenfitied by UUID {$uuid} not found.");
        }

        $nodes = $q->list_objects();
        return $this->getByMidgardNode($nodes[0]);
    }
}
