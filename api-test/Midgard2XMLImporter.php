<?php
class Midgard2XMLImporter extends \DomDocument
{
    private $ns_sv = 'http://www.jcp.org/jcr/sv/1.0';

    public function __construct($filepath)
    {
        parent::__construct('1.0', 'UTF-8');
        $this->load($filepath);
    }

    private function append_nodes(\DomNode $node, $parent)
    {
        if ($node->localName != 'node')
        {
           return; 
        }

        $name = '';
        foreach ($node->attributes as $element) 
        {
            /* The name of each JCR node or property becomes the value of the sv:name */
            if ($element->name != 'name')
            {
                continue;
            } 
            $name = $element->value;
        }

        /* The hierarchy of the content repository nodes and properties is reflected in
         * the hierarchy of the corresponding XML elements. */
        $mvc_node = new \midgardmvc_core_node();
        $mvc_node->name = $name;
        $mvc_node->up = $parent->id;
        $mvc_node->create();

        /* If there's duplicate, get it and reuse */
        if (midgard_connection::get_instance()->get_error() == MGD_ERR_DUPLICATE) 
        {
            $q = new \midgard_query_select(new \midgard_query_storage('midgardmvc_core_node'));
            $group = new midgard_query_constraint_group('AND');
            $group->add_constraint(new \midgard_query_constraint(new \midgard_query_property('up'), '=', new \midgard_query_value($parent->id)));
            $group->add_constraint(new \midgard_query_constraint(new \midgard_query_property('name'), '=', new \midgard_query_value($name)));
            $q->set_constraint($group);
            $q->execute();
            $mvc_node = current($q->list_objects());
        }

        foreach ($node->childNodes as $snode) 
        {
            $this->append_nodes($snode, $mvc_node);
        }
    }

    private function getNodeType(\DOMElement $node)
    {
        $propertyElements = $node->getElementsByTagNameNS($this->ns_sv, 'property');
        foreach ($propertyElements as $property)
        {
            $propertyName = $property->getAttributeNS($this->ns_sv, 'name');
            if ($propertyName == 'jcr:primaryType')
            {
                $typeElement = $property->getElementsByTagNameNS($this->ns_sv, 'value');
                return $typeElement->item(0)->textContent;
            }
        }
        return null;
    }

    private function writeNode(\midgard_object $parent, \DOMElement $node)
    {
        $name = $node->getAttributeNS($this->ns_sv, 'name');
        $propertyElements = $node->getElementsByTagNameNS($this->ns_sv, 'property');

        $type = $this->getNodeType($node);
        $class = null;
        if ($type == 'nt:folder' ||
            $type == 'nt:unstructured')
        {
            $class = 'midgardmvc_core_node';
        }
        if ($type == 'nt:file')
        {
            $class = 'midgard_attachment';
        }

        if (!$class)
        {
            return;
        }
        
        $object = null;
        $siblings = $parent->list_children($class);
        foreach ($siblings as $sibling)
        {
            if ($sibling->name == $name)
            {
                $object = $sibling;
            }
        }
        if (!$object)
        {
            $object = new $class();
            $object->name = $name;
            $object->up = $parent->id;
        }

        if (!$object->guid)
        {
            $object->create();
        }
        else
        {
            $object->update();
        }

        $nodeElements = $node->getElementsByTagNameNS($this->ns_sv, 'node');
        foreach ($nodeElements as $nodeElement)
        {
            $this->writeNode($object, $nodeElement);
        }
    }

    public function execute()
    {
        $q = new \midgard_query_select(new \midgard_query_storage('midgardmvc_core_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('up'), '=', new \midgard_query_value(0)));
        $q->execute();
        $root_object = current($q->list_objects());
        if ($q->get_results_count() == 0)
        {
            $root_object = new \midgardmvc_core_node();
            $root_object->name = "jackalope";
            $root_object->create();
        }

        $root_node = $this->documentElement;
        $this->writeNode($root_object, $root_node);
    }
}
