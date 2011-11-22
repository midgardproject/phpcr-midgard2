<?php
namespace Midgard\PHPCR;

use ArrayIterator;
use IteratorAggregate;
use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeInterface;
use PHPCR\NodeType\ConstraintViolationException; 
use PHPCR\PropertyType;
use PHPCR\PathNotFoundException;
use PHPCR\ItemExistsException;
use PHPCR\RepositoryException;
use PHPCR\NodeType\NoSuchNodeTypeException;
use midgard_node;

class Node extends Item implements IteratorAggregate, NodeInterface
{
    protected $children = null;
    protected $properties = null;
    protected $midgardPropertyNodes = null;
    protected $primaryNodeTypeName = null;
    protected $remove = false;
    protected $removeProperties = null;
    protected $isRoot = false;

    public function __construct(midgard_node $midgardNode = null, Node $parent = null, Session $session)
    {
        $this->parent = $parent;
        $this->midgardNode = $midgardNode;
        $this->session = $session;

        if ($parent == null) {
            if ($midgardNode->guid && $midgardNode->parent == 0) {
                $this->isRoot = true;
                $this->contentObject = $midgardNode;
            }
        }
    }

    private function getTypeName()
    {
        if ($this->isRoot) {
            return 'nt:folder';
        }

        if ($this->primaryNodeTypeName) {
            return $this->primaryNodeTypeName;
        }

        return $this->getPropertyValue('jcr:primaryType');
    }

    /* TODO, move this to ContentObjectFactory */
    private function contentObjectFactory(midgard_node $midgardNode,  $primaryNodeTypeName = null)
    {
        $guid = $midgardNode->objectguid;
        /* FIXME, set proper type name */
        if (!$midgardNode->typename)
        {
            $midgardNode->typename = 'nt_folder';
        }
        $this->contentObject = \midgard_object_class::factory($midgardNode->typename, $guid ? $guid : null);
        if ($primaryNodeTypeName != null)
        {
            $this->setProperty('jcr:primaryType', $primaryNodeTypeName, \PHPCR\PropertyType::NAME);
        }

        if ($this->hasProperty('jcr:created'))
        {
            $this->setProperty('jcr:created',  new \DateTime('now'), \PHPCR\PropertyType::DATE);
        }
    }

    protected function populateParent()
    {
        if ($this->isRoot) {
            return;
        }

        $parentMidgardNode = new midgard_node($this->getMidgard2Node()->parent);
        $this->parent = new Node($parentMidgardNode, null, $this->getSession());
    }

    private function appendNode($relPath, $primaryNodeTypeName = null)
    {
        try  {
            $node_exists = $this->getNode($relPath);
            throw new ItemExistsException("Node at given path {$relPath} exists");
        } 
        catch (PathNotFoundException $e) {
            // Do nothing 
        }

        /* ConstraintViolationException:
         * "if a node type or implementation-specific constraint is violated or 
         *  if an attempt is made to add a node as the child of a property and 
         *  this implementation performs this validation immediately." */
        $this->populateProperties();
        if (array_key_exists($relPath, $this->properties)) {
            throw new ConstraintViolationException("Can not add node to '{$relPath}' Item which is a Property");
        }

        // LockException
        // TODO

        // VersionException 
        // TODO

        $midgardNode = new midgard_node();
        $midgardNode->typename = str_replace(':', '_', $primaryNodeTypeName);
        $midgardNode->name = $relPath;

        $new_node = new Node($midgardNode, $this, $this->getSession());
        $new_node->is_new = true; 
        $new_node->primaryNodeTypeName = $primaryNodeTypeName;
        $ptnProperty = 'jcr-primaryType';
        $new_node->$ptnProperty = $primaryNodeTypeName;
        $new_node->setProperty('jcr:primaryType', $primaryNodeTypeName, \PHPCR\PropertyType::NAME);
        $this->children[$relPath] = $new_node;

        $this->is_modified = true;

        // FIXME, Catch exception before returning new node
        return $new_node;

        // RepositoryException
        // Unspecified yet.
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function addNode($relPath, $primaryNodeTypeName = NULL)
    {
        $pos = strpos('/', $relPath);
        if ($pos === 0)
        {
            throw new \InvalidArgumentException("Can not add Node at absolute path"); 
        }

        /* RepositoryException - If the last element of relPath has an index or if another error occurs. */
        if (strpos($relPath, '[') !== false)
        {
            throw new \PHPCR\RepositoryException("Index not allowed");
        }

        if ($primaryNodeTypeName == null)
        {
            $def = $this->getDefinition();
            $primaryNodeTypeName = $def->getDefaultPrimaryTypeName();
            if ($primaryNodeTypeName == null)
            {
                if ($this->getPath() == '/') {
                    $primaryNodeTypeName = 'nt:unstructured';
                } else {
                    /* ConstraintViolationException - if a node type or implementation-specific constraint 
                    * is violated or if an attempt is made to add a node as the child of a property and 
                    * this implementation performs this validation immediately.*/ 
                    throw new \PHPCR\NodeType\ConstraintViolationException("Can not determine default node type name for " . $this->getName() . "when trying to add '{$relPath}'");
                }
            }
        }
        else
        {
            $ntm = $this->session->getWorkspace()->getNodeTypeManager();
            $nt = $ntm->getNodeType($primaryNodeTypeName);
        }

        $parts = explode('/', $relPath);
        $pathElements = count($parts);
        if ($pathElements == 1)
        {
            return $this->appendNode($relPath, $primaryNodeTypeName);
        }

        /* ConstraintViolationException:
         * "if a node type or implementation-specific constraint is violated or 
         *  if an attempt is made to add a node as the child of a property and 
         *  this implementation performs this validation immediately." */
        if ($this->hasProperty($parts[0]))
        {
            throw new \PHPCR\NodeType\ConstraintViolationException("Can not add node to '{$relPath}' Item which is a Property");
        }

        $node = $this->getNode($parts[0]);
        for ($i = 1; $i < $pathElements; $i++)
        { 
            $node = $node->appendNode($parts[$i], $primaryNodeTypeName);
        }
        return $node;
    }

    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    private function getNodeDefinitionThatHasProperty($name)
    {
        $primary = $this->getPrimaryNodeType();
        if ($primary->hasRegisteredProperty($name)) {
            return $primary;
        }

        foreach ($this->getMixinNodeTypes() as $mixin) {
            if ($mixin->hasRegisteredProperty($name)) {
                return $mixin;
            }
        }
        return null;
    }

    public function setProperty($name, $value, $type = null)
    {
        if (strpos($name, '/') !== false) {
            throw new \InvalidArgumentException("Can not set property name with '/' delimeter");
        }

        $nodeDef = $this->getNodeDefinitionThatHasProperty($name);
        if (is_null($value)) {
            if ($nodeDef && !$nodeDef->canRemoveProperty($name)) {
                throw new ConstraintViolationException("Can not remove property {$name} which is mandatory for {$this->primaryNodeTypeName }");
            }

            return $this->removeProperty($name);
        }

        if ($type != null && $nodeDef) {
            $propertyDefs = $nodeDef->getPropertyDefinitions();
            if (isset($propertyDefs[$name])) {
               $requiredType = $propertyDefs[$name]->getRequiredType();

                if ($requiredType != 0 && $requiredType != $type) {
                    throw new ConstraintViolationException("Wrong type for {$name} property. " . PropertyType::nameFromValue($type) . " given. Expected " . PropertyType::nameFromValue($requiredType));
                }

                if ($requiredType != 0 && ($requiredType != null && $requiredType != $type) && property_exists($this->getMidgard2ContentObject(), $name)) {
                    throw new ConstraintViolationException("Wrong type for {$name} property. " . PropertyType::nameFromValue($type) . " given. Expected " . PropertyType::nameFromValue($requiredType));
                }
            }
        }

        try 
        {
            $property = $this->getProperty($name);
        } 
        catch (PathNotFoundException $e)
        { 
            $mnp = new \midgard_node_property();
            $mnp->title = $name;
            $mnp->type = $type;
            if (!$type && is_array($value)) {
                $mnp->multiple = true;
            }
            $this->setMidgardPropertyNode($name, $mnp);
            $property = new Property($this, $name);
            $this->properties[$name] = $property;
        }
        $property->setValue($value, $type);
        
        /* TODO, for performance reason, we could check if property's value
         * has been changed.
         * By default, it's modified */
        $this->is_modified = true;

        return $property;
    }

    public function setMidgardPropertyNode($name, \midgard_node_property $property)
    {
        $this->midgardPropertyNodes[$name][] = $property;
    }

    public function getMidgardPropertyNodes($name = null)
    {
        if (empty($this->midgardPropertyNodes))
        {
            return null;
        }
        if ($name != null)
        {
            if (!array_key_exists($name, $this->midgardPropertyNodes))
            {
                return null;
            }
            return $this->midgardPropertyNodes[$name];
        }

        return $this->midgardPropertyNodes;
    }

    private function populateChildren()
    {
        if (!is_null($this->children))
        {
            return;
        }

        /* Node is not saved, so DO NOT list children of the same type */
        if (!$this->midgardNode->guid)
        {
            return;
        }

        $children = $this->midgardNode->list();
        $this->children = array();
        foreach ($children as $child)
        {
            $this->children[$child->name] = new Node($child, $this, $this->getSession());
        }
    }

    public function getNode($relPath)
    {
        $remainingPath = '';
        $pos = strpos($relPath, '/');
        
        /* Convert to relative path when absolute one has been given */
        /* FIXME, Remove this part once absolute path is considered invalid
         * https://github.com/phpcr/phpcr-api-tests/issues/9 */
        if ($pos === 0)
        {
            $relPath = substr($relPath, 1);
            $pos = strpos($relPath, '/');
        }

        if ($pos !== false)
        {
            $parts = explode('/', $relPath);
            $relPath = array_shift($parts);
            $remainingPath = implode('/', $parts);
        }

        if (!isset($this->children[$relPath]))
        {
            $this->populateChildren();
            if (!isset($this->children[$relPath]))
            {
                /* Try special case: '..' */
                if ($relPath == '..')
                {
                    if ($remainingPath)
                    {
                        return $this->parent->getNode($remainingPath);
                    } 
                    return $this->parent;
                }

                $absPath = $this->getPath();
                //$guid = $this->getMidgard2Object()->guid;
                $guid = '';

                throw new PathNotFoundException("Node at path '{$relPath}' not found. ({$remainingPath}). Requested at node {$absPath} with possible guid identifier '{$guid}'." . print_r($this->children ? array_keys($this->children) : array(), true));
            }
        }

        if ($remainingPath != '') 
        {
            return $this->children[$relPath]->getNode($remainingPath);
        }

        return $this->children[$relPath];        
    }

    private function getItemsSimilar($items, $nsname, $isNode)
    {
        $ret = array();

        $nsregistry = $this->getSession()->getWorkspace()->getNamespaceRegistry();
        $nsmanager = $nsregistry->getNamespaceManager();

        foreach ($items as $n => $o)
        { 
            $prefixMatch = false;
            $nameMatch = false;
            $itemName = $n;
            $node_prefix = $nsmanager->getPrefix($itemName);
            $prefix = $nsname[0];
            $name = $nsname[1];

            if ($prefix != "")
            {
                /* Compare prefix */
                if ($node_prefix == $prefix)
                {
                    $prefixMatch = true;
                }
            }
            else if ($prefix == '*'
                || $prefix == '')
            {
                /* Empty prefix or wildcard, everything matches */
                $prefixMatch = true;
            }

            if ($name != '' && $name != '*') 
            {
                /* Clean given name and item name:
                 * From name remove wildcard and from item's one - prefix. */
                $name = str_replace('*', '', $name);
                $itemName = str_replace($prefix . ':' , '', $itemName);
                $pos = strpos($itemName, $name);
               
                if ($pos !== false)
                {
                    $nameMatch = true;
                }
            }
            else if ($name == '*')
            {
                /* Wildcard so everything matches */
                $nameMatch = true;
            }

            if ($prefixMatch == true && $nameMatch == true)
            {
                $ret[$n] = $isNode ? $this->getNode($n) : $this->getProperty($n);
            }
        }

        return $ret;
    }

    private function getItemsEqual($items, $nsnames)
    {
        $ret = array();

        $prefix = $nsnames[0];
        $name = $nsnames[1];

        if (array_key_exists($name, $this->children))
        {
            $ret[$name] = $items[$name];
        }
            
        return $ret;
    }

    /* Return array of prefixes and names.
     * Any prefix or name might be empty or null:
     * 
     * jcr:*
     * prefix = "jcr", name = "*"
     *
     * my doc
     * prefix = "", name = "my doc"
     *
     * jcr:created
     * prefix = "jcr", name = "created"
     */
    private function getFiltersFromString($filter)
    {
        $filters = array();
        $filtered = array();
        $parts = explode('|', $filter);
        if (!isset($parts[1]))
        {
            $filters[] = $filter;
        }
        else 
        {
            foreach($parts as $p)
            {
                $filters[] = trim($p);
            }
        }
        foreach ($filters as $f)
        {
            $parts = explode(':', $f);
            $prefix = "";
            $name = "";
            if (isset($parts[1]))
            {
                $prefix = $parts[0];
                $name = $parts[1]; 
            }
            else 
            {
                $name = $parts[0];
            }
            $filtered[] = array($prefix, $name);
        }

        return $filtered; 
    }

    private function getFiltersFromArray($filter)
    {
        $allFilters = array();
        foreach ($filter as $p)
        {
            $allFilters = array_merge($allFilters, $this->getFiltersFromString($p));
        }
        return $allFilters;
    }

    private function getItemsFiltered($items, $filter, $isNode)
    {
        if ($filter == null)
        {
            return $items;
        } 

        $filteredItems = array();

        if (is_string($filter))
        {
            $filters = $this->getFiltersFromString($filter);
        }

        if(is_array($filter))
        {
            $filters = $this->getFiltersFromArray($filter);
        } 

        foreach ($filters as $i => $f)
        {
            if (strpos($f[0], '*') !== false 
                || strpos($f[1], '*') !== false)
            { 
                $filteredItems = array_merge($filteredItems, $this->getItemsSimilar($items, $f, $isNode));
            }
            else 
            { 
                $filteredItems = array_merge($filteredItems, $this->getItemsEqual($items, $f, $isNode));
            }
        }

        return new \ArrayIterator($filteredItems);   
    }

    public function getNodes($filter = null)
    {
        $this->populateChildren();

        if ($filter == null) 
        {
            return new \ArrayIterator($this->children ? $this->children : array());
        }

        return $this->getItemsFiltered($this->children ? $this->children : array(), $filter, true); 
    }

    private function populateProperties()
    {
        if ($this->contentObject == null)
        {
            $this->contentObjectFactory($this->midgardNode, $this->primaryNodeTypeName);
        }

        if (!is_null($this->properties))
        {
            return;
        }

        foreach ($this->contentObject as $property => $value)
        {
            if (strpos($property, '-') === false)
            {
                $this->properties["mgd:{$property}"] = ' ';
            }
            else 
            {
                $parts = explode('-', $property);
                $this->properties["{$parts[0]}:{$parts[1]}"] = ' ';
            }
        }

        $nodeProperties = $this->midgardNode->list_children('midgard_node_property');
        foreach ($nodeProperties as $property)
        {
            $this->midgardPropertyNodes[$property->title][] = $property;
            $this->properties[$property->title] = ' ';
        }
    }

    public function getProperty($relPath)
    {
        $remainingPath = '';
        if (strpos($relPath, '/') !== false)
        {
            $parts = explode('/', $relPath);
            $property_name = array_pop($parts);
            $remainingPath = implode('/', $parts); 
            return $this->getNode($remainingPath)->getProperty($property_name);
        }

        if (!isset($this->properties[$relPath]))
        {
            $this->populateProperties();
            if (empty($this->properties) || !array_key_exists($relPath, $this->properties))
            {
                throw new PathNotFoundException("Property at path '{$relPath}' not found at node " . $this->getName() . " at path " . $this->getPath());
            }
        }

        if (!is_object($this->properties[$relPath]))
        {
            $midgardPropertyNodes = null;
            if (isset($this->midgardPropertyNodes[$relPath]))
            {
                $midgardPropertyNodes = $this->midgardPropertyNodes[$relPath];
            }
            $this->properties[$relPath] = new Property($this, $relPath);
        }

        return $this->properties[$relPath];
    }
    
    public function getPropertyValue($name, $type=null)
    {
        return $this->getProperty($name)->getValue();
    }
    
    public function getProperties($filter = null)
    {
        $this->populateProperties();
        $ret = $this->getItemsFiltered($this->properties, $filter, false);
        foreach ($ret as $name => $property)
        {
            $ret[$name] = is_object($this->properties[$name]) ? $this->properties[$name] : $this->getProperty($name); 
        }
        return new \ArrayIterator($ret);
    }

    public function getPropertiesValues($filter = null, $dereference = true)
    {
        $properties = $this->getProperties($filter);
        $ret = array();
        foreach ($properties as $name => $o)
        {
            $type = $this->getProperty($name)->getType();
            if ($type == \PHPCR\PropertyType::WEAKREFERENCE
                || $type == \PHPCR\PropertyType::REFERENCE
                || $type == \PHPCR\PropertyType::PATH)
            {
                if ($dereference == true)
                {
                    $ret[$name] = $this->getProperty($name)->getNode();
                }
                else
                {
                    $ret[$name] = $this->getProperty($name)->getString();
                }
            }
            else 
            {
                $ret[$name] = $this->getProperty($name)->getValue(); 
            }
        }
        return $ret;
    }   
    
    public function getPrimaryItem()
    {
        try
        {
            $primaryType = $this->getPropertyValue('jcr:primaryType');
            $ntm = $this->session->getWorkspace()->getNodeTypeManager();
            $nt = $ntm->getNodeType($primaryType);
            $primaryItem = $nt->getPrimaryItemName();
            if ($primaryItem == null)
            {
                throw new \PHPCR\ItemNotFoundException("PrimaryItem not found for {$this->getName()} node");
            }
        }
        catch (PathNotFoundException $e)
        {
                throw new \PHPCR\ItemNotFoundException("primaryType property not found for {$this->getName()} node");
        }
        if ($this->hasNode($primaryItem))
        {
            return $this->getNode($primaryItem);
        }
        
        return $this->getProperty($primaryItem);
    }
    
    public function getIdentifier()
    {
        $this->populateProperties();

        /* Try uuid first */
        try 
        {
            $uuidProperty = $this->getProperty('jcr:uuid');
            return $uuidProperty->getValue();
        }
        catch (PathNotFoundException $e)
        {
            /* Do notthing */
        }

        /* Return guid if uuid is not found */
        return $this->contentObject->guid;
    }
    
    public function getIndex()
    {
        /* We do not support same name siblings */
        return 1;
    }
    
    private function getReferencesByType($name = null, $type)
    {
        $ret = array();
        $this->populateProperties();
        /* If node has jcr:uuid property */
        if (!$this->hasProperty('jcr:uuid'))
        {
            return new \ArrayIterator($ret);
        }
         
        /* get its value */
        $uuid = $this->getPropertyValue('jcr:uuid');

        if ($uuid === null || $uuid === "")
        {
            throw new \PHPCR\RepositoryException("Invalid empty uuid value");
        }

        $q = new \midgard_query_select(new \midgard_query_storage('midgard_node_property'));
        $group = new \midgard_query_constraint_group('AND');
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('value'),
                '=',
                new \midgard_query_value($uuid)));

        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('type'),
                '=',
                new \midgard_query_value($type)
            )
        );

        if ($name != null)
        {
            $group->add_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property('title'),
                    '=',
                    new \midgard_query_value($name)
                )
            );
        }

        $q->set_constraint($group);
        $q->execute();
        if ($q->get_results_count() < 1)
        {
            return new \ArrayIterator($ret);
        }

        $nodeProperties = $q->list_objects();

        /* TODO, query properties only, once tree and nodes scope is provided by Property */

        /* query references */
        foreach ($nodeProperties as $midgardProperty)
        {
            $midgardNode = \midgard_object_class::factory('midgard_node', $midgardProperty->parent);
            $path = self::getMidgardPath($midgardNode);
            /* Convert to JCR path */
            $path = str_replace ('/jackalope', '', $path);
            $path = str_replace('/root', '', $path);
            $node = $this->session->getNode($path);
            $ret[] = $node->getProperty($midgardProperty->title);
        } 
        return new \ArrayIterator($ret);
    }

    public function getReferences($name = null)
    {
        return $this->getReferencesByType($name, \PHPCR\PropertyType::REFERENCE);
    }

    public function getWeakReferences($name = NULL)
    {
        return $this->getReferencesByType($name, \PHPCR\PropertyType::WEAKREFERENCE);
    }
    
    public function hasNode($relPath)
    {
        /* FIXME, optimize this.
         * Do not get node, check children array instead */
        $pos = strpos($relPath, '/');
        if ($pos === 0)
        {
            throw new \InvalidArgumentException("Expected relative path. Absolute given");
            /* Take few glasses if Absolute given ;) */
        }

        try 
        {
            $this->getNode($relPath);
            return true;
        }
        catch (PathNotFoundException $e) 
        {
            return false;
        }
    }
    
    public function hasProperty($relPath)
    {
        $pos = strpos($relPath, '/');
        if ($pos === 0)
        {
            throw new \InvalidArgumentException("Expected relative path. Absolute given");
        }

        /* Try native property first */
        $nativeProperty = $relPath;
        if (strpos($relPath, ':') !== false)
        {
            $parts = explode(':', $relPath);
            if ($parts[0] == 'mgd')
            {
                $nativeProperty = $parts[1];
            }
            else 
            {
                $nativeProperty = str_replace(':', '-', $relPath);
            }
        }

        if (strpos($relPath, '-') !== false)
        {
            $parts = explode('-', $relPath);
            if ($parts[0] == 'mgd')
            {
                $nativeProperty = $parts[1];
            }
        }

        if (!$this->midgardNode->typename)
        {
            $this->midgardNode->typename = 'nt_unstructured';
        }

        /* Unfortunatelly, we must initialize new midgard object and reflection object.
         * There's no other way to check if class has property registered.
         * Others, (very) old PHP introspection routines check properties, in class default properties scope */
        $ro = new \ReflectionObject(new $this->midgardNode->typename);
        if ($ro->hasProperty($nativeProperty))
        {
            return true;
        }

        if (property_exists($this->midgardNode->typename, $nativeProperty))
        {
            return true;
        }

        $this->populateProperties();

        if (array_key_exists($relPath, $this->properties))
        {
            return true;
        }

        return false;
    }
    
    public function hasNodes()
    {
        $this->populateChildren();
        if (empty($this->children))
        {
            return false;
        }

        return true;
    }
    
    public function hasProperties()
    {
        $this->populateProperties();
        if (empty($this->properties))
        {
            return false;
        }

        return true;
    }
    
    public function getPrimaryNodeType()
    {
        $primaryType = $this->getTypeName();
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        $nt = $ntm->getNodeType($primaryType);
        if (!$nt) {
            $name = $this->getName();
            throw new RepositoryException("Failed to get NodeType from current '{$name}' node ({$primaryType})");
        }
        return $nt;
    }
    
    public function getMixinNodeTypes()
    {
        $ret = array();
        if (!$this->hasProperty('jcr:mixinTypes')) {
            return $ret;
        }

        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        $mixins = $this->getPropertyValue('jcr:mixinTypes');
        if (!is_array($mixins)) {
            $tmp[] = $mixins;
            $mixins = $tmp;
        }

        foreach ($mixins as $mixin) {
            if (!$mixin) {
                continue;
            }
            $ret[] = $ntm->getNodeType($mixin);
        }

        return $ret;
    }
    
    public function isNodeType($nodeTypeName)
    {
        /* Check primaryType first */
        try 
        {
            $type = $this->getPropertyValue('jcr:primaryType');
        }
        catch (PathNotFoundException $e)
        {
            /* Do nothing */
        }
        if ($type == $nodeTypeName)
        {
            return true;
        }
        /* TODO 
         * Check supertypes */
        /*
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        $nt = $ntm->getNodeType($type);
         */

        /* Check mixin */
        try 
        {
            $mixins = $this->getPropertyValue('jcr:mixinTypes');
        }
        catch (PathNotFoundException $e)
        {
            return false;
        }

        if (!is_array($mixins))
        {
            $tmp[] = $mixins;
            $mixins = $tmp;
        }

        if (in_array($nodeTypeName, $mixins))
        {
            return true;
        }

        return false;
    }
    
    public function setPrimaryType($nodeTypeName)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function addMixin($mixinName)
    {
        if (!$this->canAddMixin($mixinName)) {
            throw new NoSuchNodeTypeException("{$mixinName} is not registered mixin type"); 
        }

        // Check if we already have such a mixin
        $mixins = $this->getMixinNodeTypes();
        foreach ($mixins as $mixin) {
            if ($mixin->getName() == $mixinName) {
                return;
            }
        }

        $hasMixin = false;
        try 
        {
            /* Check if node has such mixin */
            $mixinProperty = $this->getPropertyValue('jcr:mixinTypes');
            if (!is_array($mixinProperty))
            {
                $tmp[] = $mixinProperty;
                $mixinProperty = $tmp;
            }
            foreach ($mixinProperty as $mixin)
            {
                if ($mixin == $mixinName)
                {
                    $hasMixin = true;
                }
            }

            if ($hasMixin == false)
            {
                $this->setProperty('jcr:mixinTypes', $mixinName);
            }
            else 
            {
                /* If this node is already of type mixinName (either due to a previously 
                 * added mixin or due to its primary type, through inheritance) then this method has no effect.*/
                return;
            }
        }
        catch (PathNotFoundException $e)
        {
            $this->setProperty('jcr:mixinTypes', $mixinName);
        }

        $properties = \midgard_reflector_object::list_defined_properties ($midgardMixinName);
        foreach ($properties as $name => $v)
        {
            $jcrName = NodeMapper::getPHPCRProperty($name);
            if($this->hasProperty($jcrName))
            {
                continue;
            }

            /* FIXME, determine default value */
            $this->setProperty($jcrName, ' ');
        }
    }
    
    public function removeMixin($mixinName)
    {
    }
    
    public function canAddMixin($mixinName)
    {
        $midgardMixinName = NodeMapper::getMidgardName($mixinName);
        if ($midgardMixinName == null || !interface_exists($midgardMixinName, false)) {
            return false;
        }

        if (is_subclass_of($midgardMixinName, 'MidgardBaseMixin')) {
            return false;
        }

        return true;
    }
    
    public function getDefinition()
    {
        return new NodeType\NodeDefinition($this);
    }
    
    public function update($srcWorkspace)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getCorrespondingNodePath($workspaceName)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getSharedSet()
    {
        return new \ArrayIterator(array($this));
    }
    
    public function removeSharedSet()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function removeShare()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function isCheckedOut()
    {
        return false;
    }

    public function isLocked()
    {
        return false;
    }

    public function followLifecycleTransition($transition)
    {
        throw new \UnsupportedRepositoryOperationException();
    }

    public function getAllowedLifecycleTransitions()
    {
        throw new \UnsupportedRepositoryOperationException();
    }

    public function getIterator()
    {
        return $this->getNodes();
    }

    public function isSame(\PHPCR\ItemInterface $item)
    {
        if (!$item instanceof \PHPCR\NodeInterface)
        {
            return false;
        }

        /* TODO */
        /* Check session */
       
        $thisContentObject = $this->getMidgard2ContentObject();
        if (!$thisContentObject)
        {
            return false;
        }
        $itemContentObject = $item->getMidgard2ContentObject();
        if (!$itemContentObject)
        {
            return false;
        }

        if ($thisContentObject->guid == $itemContentObject->guid)
        {
            return true;
        }

        return false;
    }

    private static function getMidgardRelativePath($object)
    {
        $storage = new \midgard_query_storage('midgard_node');

        $joined_storage = new \midgard_query_storage('midgard_node');
        $left_property_join = new \midgard_query_property('id');
        $right_property_join = new \midgard_query_property('parent', $joined_storage);    

        $q = new \midgard_query_select($storage);
        $q->add_join("INNER", $left_property_join, $right_property_join);

        /* Set name and guid constraints */
        $group = new \midgard_query_constraint_group('AND');
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('name', $joined_storage), 
                '=', 
                new \midgard_query_value($object->name)
            )
        );
        $group->add_constraint(
            new \midgard_query_constraint(
                new \midgard_query_property('id', $joined_storage), 
                '=', 
                new \midgard_query_value($object->id)
            )
        );

        $q->set_constraint($group);
        
        $q->execute();

        /* Relative path is : $returnedobjects[0]->name / $object->name */

        return $q->list_objects();
    }

    public static function getMidgardPath($object)
    {
        $elements = array();

        /* We expect last object to have up property = 0.
         * Last object is root node. */
        do 
        {
            array_unshift($elements, $object->name);
            $objects = self::getMidgardRelativePath($object, null);
            if (empty($objects))
            {
                break;
            }
            $object = $objects[0];

        } while (!empty($objects));

        return '/' . implode("/", $elements);
    }

    public function move($dstNode, $dstName)
    {
        /* Unset parent's child */
        unset($this->parent->children[$this->getName()]);

        /* Set new parent */
        $this->parent = $dstNode;

        /* Update Midgard2 Node's properties, so it points to valid parent object */
        $this->midgardNode->parent = $dstNode->midgardNode->id;
        $this->midgardNode->parentguid = $dstNode->midgardNode->guid;
        $this->midgardNode->name = $dstName;

        /* Update parent's children */
        $dstNode->children[$dstName] = $this;

        /* Update node's state */
        if (!$this->midgardNode->guid)
        {
            $this->is_new = true;
            $this->is_modified = false;
        }
        else
        {
            $this->is_modified = true;
            $this->is_new = false;
        } 
    }

    public function save()
    {
        $mobject = $this->getMidgard2ContentObject();
        $midgardNode = $this->getMidgard2Node();

        /* Remove self instance if marked such */
        if ($this->remove == true)
        {
            $self::removeFromStorage($this);
            return;
        }

        /* Remove properties marked to be removed */
        if (!empty($this->removeProperties))
        {
            foreach ($this->removeProperties as $properties)
            {
                foreach ($properties as $mnp)
                {
                    if (!$mnp->purge_attachments(true)) {
                        Repository::checkMidgard2Exception($mnp);
                    }
                    if (!$mnp->purge()) {
                        Repository::checkMidgard2Exception($mnp);
                    }
                }
            }
        }

        /* Create */
        if ($this->isNew() === true)
        {
            if ($mobject->create() === true)
            { 
                $midgardNode->typename = get_class($mobject);
                $midgardNode->objectguid = $mobject->guid;

                $parentNode = $this->parent->getMidgard2Node();
                if (!$parentNode->id)
                {
                    // Refresh from DB in case reference has been lost
                    $parentNode = new \midgard_node($parentNode->guid);
                }
                $midgardNode->parentguid = $parentNode->guid;
                $midgardNode->parent = $parentNode->id;
                $midgardNode->create();
            }
            else 
            {
                throw new \Exception(\midgard_connection::get_instance()->get_error_string());
            }
        }

        if ($this->isModified() === true)
        {
            if (!$this->isRoot)
            {
                if ($mobject->update() === true)
                {    
                    $midgardNode->update();
                }
                if (\midgard_connection::get_instance()->get_error() != MGD_ERR_OK)
                {
                    throw new \PHPCR\RepositoryException(\midgard_connection::get_instance()->get_error_string());
                }
            }
        }

        $this->is_modified = false;
        $this->is_new = false; 

        if (empty($this->properties))
        {
            return;
        }

        foreach ($this->properties as $name => $property)
        {
            if (is_object($this->properties[$name]))
            {
                $this->properties[$name]->save();
            }
        }
    }

    public function refresh($keepChanges)
    {
        if ($keepChanges && ($this->isModified() || $this->isNew())) {
            return;
        }

        if ($this->midgardNode->guid) {
            $this->midgardNode = new \midgard_node($this->midgardNode->guid);
        }

        if ($this->properties) {
            foreach ($this->properties as $name => $property) {
                $this->getProperty($name)->refresh($keepChanges);
            }
        }
        $this->contentObject = null;
    }

    public function remove()
    {
        if ($this->remove == true)
        {
            return;
        }

        unset($this->parent->children[$this->getName()]);
        $this->remove = true;
        $this->session->removeNode($this);
    }

    public function removeMidgard2Node()
    {
        self::removeFromStorage($this);
    }

    private function removeFromStorage($node)
    { 
        $mobject = $node->getMidgard2ContentObject();
        $midgardNode = $node->getMidgard2Node();

        /* Remove properties first */
        $node->populateProperties();
        if (!empty($node->midgardPropertyNodes))
        {
            foreach ($node->midgardPropertyNodes as $properties)
            {
                foreach ($properties as $mnp)
                {
                    if (!$mnp->guid) {
                        continue;
                    }

                    $mnp->purge_attachments(true);
                    Repository::checkMidgard2Exception($mnp);
                    if (!$mnp->purge()) {
                        // Object's connection was somehow lost, refresh
                        try {
                            $mnp = new \midgard_node_property($mnp->guid);
                        } catch (\midgard_error_exception $e) {
                            // Object isn't in DB any longer, just skip
                            continue;
                        }
                        $mnp->purge();
                        Repository::checkMidgard2Exception($mnp);
                    }
                }
            }
        }

        /* Remove child objects */
        $children = $node->getNodes();
        foreach ($children as $child)
        {
            $this->removeFromStorage($child);
        }

        if ($mobject->purge() == true)
        {
            $midgardNode->purge();
            Repository::checkMidgard2Exception($midgardNode);
            /* TODO, FIXME, Remove properties from Propertymanager */
        }
    }

    private function removeProperty($name)
    {
        if (!isset($this->properties[$name]))
        {
            return;
        } 
        $this->is_modified = true;
        if (isset($this->midgardPropertyNodes[$name])) {
            $this->removeProperties[] = $this->midgardPropertyNodes[$name];
        }
        unset($this->properties[$name]);
        return;
    }

    /**
     * Shortcut to check if mix:referenceable is value of jcr:mixinTypes
     */
    public function isReferenceable()
    {
        $mixins = $this->getMixinNodeTypes();
        foreach ($mixins as $mixin) {
            if ($mixin->getName() == 'mix:referenceable') {
                return true;
            }
        }
        return false;
    } 
}
