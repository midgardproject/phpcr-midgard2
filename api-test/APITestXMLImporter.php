<?php

class APITestXMLImporter extends \DomDocument
{
    private $ns_sv = 'http://www.jcp.org/jcr/sv/1.0';

    public function __construct ($filepath)
    {
        parent::__construct ();
        $this->load($filepath); 
    }

    private function append_nodes(\DomNode $node, $parent)
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

        foreach ($node->childNodes as $snode) 
        {
            $this->append_nodes($snode, $mvc_node);
        }
    }

    private function get_nodes($root)
    {
        /* Each JCR node becomes an XML element <sv:node>. */
        $node = $this->getElementsByTagNameNS($this->ns_sv, 'node')->item(0);
        if ($node == null)
        {
            return;
        }
        $this->append_nodes($node, $root);
    }

    public function execute()
    {
        $q = new \midgard_query_select(new \midgard_query_storage('midgardmvc_core_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('up'), '=', new \midgard_query_value(0)));
        $q->execute();
        $root_object = current($q->list_objects());

        $this->get_nodes($root_object);
    }
}

?>
