<?php


/**
 * @node: DomNode object 
 * @parent: Midgard object
 */ 
interface JCRXMLImporter 
{
    public function XMLNode2MidgardObject ($node, $parent);
}

class SystemViewImporter implements JCRXMLImporter
{
    public function XMLNode2MidgardObject ($node, $parent)
    {
        if ($node->localName != 'node')
        {
           return; 
        }

        $name = "";

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
        $mvc_node = new midgardmvc_core_node();
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
            $this->XMLNode2MidgardObject($snode, $mvc_node);
        }

    }   
}

class DocumentViewImporter implements JCRXMLImporter
{
    public function XMLNode2MidgardObject ($node, $parent)
    {
    }
}

class APITestXMLImporter extends \DomDocument
{
    private $ns_sv = 'http://www.jcp.org/jcr/sv/1.0';

    public function __construct ($filepath)
    {
        parent::__construct ();
        $this->load($filepath); 
    }

    private function get_nodes($root)
    {
        /* Each JCR node becomes an XML element <sv:node>. */
        $node = $this->getElementsByTagNameNS($this->ns_sv, 'node')->item(0);
        $importer = new SystemViewImporter();
        if ($node == null)
        {
            /* TODO, this might be Document View */
            return;
        }

        $importer->XMLNode2MidgardObject($node, $root);
    }

    public function execute()
    {
        $q = new \midgard_query_select(new \midgard_query_storage('midgardmvc_core_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('up'), '=', new \midgard_query_value(0)));
        $q->execute();
        if ($q->get_results_count() == 0)
        {
            $root_object = new \midgardmvc_core_node();
            $root_object->name = "Root Node";
            $root_object->create();
        }
        else 
        {
            $root_object = current($q->list_objects());
        }

        $this->get_nodes($root_object);
    }
}

?>
