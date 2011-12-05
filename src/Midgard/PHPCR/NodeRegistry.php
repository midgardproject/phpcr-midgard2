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
    private static $byGuid = array();
    private static $byUuid = array();
    private static $byPath = array();

    public static function getByMidgardNode(midgard_node $node)
    {
        if ($node->guid && isset(self::$byGuid[$node->guid])) {
            return self::getByMidgardGuid($node->guid);
        }

        $parent = null;
        $path = null;
        if ($node->parentguid) {
            $parent = self::getByMidgardGuid($node->parentguid);
        }

        $crNode = new Node($node, $parent, self::getByPath('/')->getSession());
        self::registerNode($crNode);
        return $crNode;
    }

    public static function registerNode(Node $node)
    {
        $path = $node->getPath();
        if ($path) {
            self::$byPath[$path] = $node;
        }

        if ($node->getMidgard2Node()->guid) {
            self::$byGuid[$node->getMidgard2Node()->guid] = $node;
        }

        if ($node->hasProperty('jcr:uuid')) {
            self::$byUuid[$node->getPropertyValue('jcr:uuid')] = $node;
        }
    }

    public static function getByPath($path)
    {
        if (!isset(self::$byPath[$path])) {
            self::fetchByPath($path);
        }
        return self::$byPath[$path];
    }

    public static function fetchByPath($path)
    {
        if (substr($absPath, 0, 1) != '/') {
            throw new RepositoryException("Full path required. Given one is '{$absPath}'");
        }

        if (strpos($absPath, '//') !== false) {
            throw new RepositoryException("Invalid path '{$absPath}'");
        }

        return self::getByPath('/')->getNode(substr($absPath, 1));
    }

    public static function getByMidgardGuid($guid)
    {
        if (!isset(self::$byGuid[$guid])) {
            self::fetchByMidgardGuid($guid);
        }
        return self::$byGuid[$guid];
    }

    public static function fetchByMidgardGuid($guid)
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
        return self::getByMidgardNode($nodes[0]);
    }

    public static function getByUuid($uuid)
    {
        if (!isset(self::$byUuid[$uuid])) {
            self::fetchByUuid($uuid);
        }
        return self::$byUuid[$uuid];
    }

    public static function fetchByUuid($uuid)
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
        return self::getByMidgardNode($nodes[0]);
    }
}
