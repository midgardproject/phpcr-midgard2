<?php

require_once(dirname(__FILE__) . '/../src/Midgard2CR/PropertyManager.php');

class Midgard2XMLImporter extends \DomDocument
{
    private $ns_sv = 'http://www.jcp.org/jcr/sv/1.0';
    private $ns_prefix = 'sv';

    public function __construct($filepath)
    {
        parent::__construct('1.0', 'UTF-8');
        $this->load($filepath);
        $this->filepath = $filepath;
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

    private function getPropertyValue(\DOMElement $property)
    {
        $typeElement = $property->getElementsByTagNameNS($this->ns_sv, 'value');
        return $typeElement->item(0)->textContent;
    }

    private function getNodeType(\DOMElement $node)
    {
        $propertyElements = $node->getElementsByTagNameNS($this->ns_sv, 'property');
        foreach ($propertyElements as $property)
        {
            $propertyName = $property->getAttributeNS($this->ns_sv, 'name');
            if ($propertyName == 'jcr:primaryType')
            {
                return $this->getPropertyValue($property);
            }
        }
        return null;
    }

    private function writeProperty(\midgard_object $object, \DOMElement $property, $propertyManager)
    {
        $propertyName = $property->getAttributeNS($this->ns_sv, 'name'); 

        if (substr($propertyName, 0, 4) == 'mgd:')
        {
            $propertyName = substr($propertyName, 4);
            $object->$propertyName = $this->getPropertyValue($property);
            return $object->update();
        }

        $parts = explode(':', $propertyName);

        if (count($parts) == 2)
        {
            $GnsName = str_replace(':', '-', $propertyName);
            if (property_exists($object, $GnsName))
            {
                $vnode = $property->getElementsByTagName('value')->item(0);
                $object->GnsName = $this->getPropertyValue($property);

                /* Hack, FIXME, add generic node factory */
                $createdProperty = 'jcr-created';
                if (property_exists($object, $createdProperty))
                {
                    $object->$createdProperty = new DateTime('now');
                }

                return $object->update();
            }
        }

        if (count($parts) != 2)
        {
            $parts[1] = $parts[0];
            $parts[0] = 'phpcr:undefined';
        }

        /* Take multivalues into account */
        $isMultiple = false;
        $n_values = $property->getElementsByTagName('value')->length;
        $propertyType = $property->getAttributeNS($this->ns_sv, 'type');
        $multiAttr = $property->getAttributeNS($this->ns_sv, 'multiple');
        if ($n_values > 1 || $multiAttr == 'true')
        {
            $isMultiple = true;
        }

        $isBinary = false;

        for ($i = 0; $i < $n_values; $i++)
        {
            $vnode = $property->getElementsByTagName('value')->item($i);

            /* For every binary value, create attachment and midgard blob to store binary content */
            if ($propertyType == 'Binary')
            {
                /* Do not attempt to create new attachment or blob if there's already one */
                if ($isMultiple == false)
                {
                    $dups = $object->list_attachments();
                    foreach ($dups as $duplicate)
                    {
                        if ($duplicate->name == $propertyName)
                        {
                            /* Property is not multiple, silently return */
                            return true;
                        }
                    }
                }
                $att = new midgard_attachment();
                $att->name = $propertyName;
                $att->parentguid = $object->guid;

                $blob = new midgard_blob($att);
                if ($blob->write_content(base64_decode($vnode->nodeValue)))
                    $att->create();

                $isBinary = true;
            }

            /* Create property models and values */
            $propertyManager->factory($parts[1], $parts[0], $propertyType, $isMultiple, $isBinary == true ? " " : $vnode->nodeValue); 
        }    

        return true;
    }

    private function mapNodeType($type)
    {
        if ($type == 'nt:folder')
        {
            return 'nt_folder';
        }
        if ($type == 'nt:file')
        {
            return 'nt_file';
        }
        if ($type == 'nt:unstructured')
        {
            return 'nt_unstructured';
        }
        return null;
    }

    private function writeNode(\midgard_object $midgardParentNode, \DOMElement $node)
    {
        $name = $node->getAttributeNS($this->ns_sv, 'name');
        $propertyElements = $node->getElementsByTagNameNS($this->ns_sv, 'property');

        $type = $this->getNodeType($node);
        $class = $this->mapNodeType($type);
        if (!$class)
        {
            return;
        }

        $object = new $class();
        $object->name = $name;
        if ($class == 'midgard_attachment')
        {
            /* Try to get attachment if it exists already */
            $atts = $parent->find_attachments(array("name" => $name));
            if (!empty($atts))
            {
                $object = $atts[0];
            } 
            else 
            {
                $object->mimetype = 'nt:file';
            }
        }

        $object->create();

        if (\midgard_connection::get_instance()->get_error() != MGD_ERR_OK)
        {
            throw new \Exception(\midgard_connection::get_instance()->get_error_string());
        }

        $midgardNode = new \midgard_tree_node();
        $midgardNode->name = $name;
        $midgardNode->typename = get_class($object);
        $midgardNode->objectguid = $object->guid;
        $midgardNode->parentguid = $midgardParentNode->guid;
        $midgardNode->parent = $midgardParentNode->id;
        $midgardNode->create();
        
        if (\midgard_connection::get_instance()->get_error() != MGD_ERR_OK)
        {
            throw new \Exception(\midgard_connection::get_instance()->get_error_string());
        }

        $propertyManager = new \Midgard2CR\PropertyManager($object);

        foreach ($propertyElements as $propertyElement)
        {
            /* Check parent and current names.
             * getElementsByTagNameNS returns all descendants */
            if ($propertyElement->parentNode->getAttributeNS($this->ns_sv, 'name') == $name)
            {
                $this->writeProperty($object, $propertyElement, $propertyManager);
            }
        }

        $propertyManager->save();

        $nodeElements = $node->getElementsByTagNameNS($this->ns_sv, 'node');
        foreach ($nodeElements as $nodeElement)
        {
            if ($nodeElement->parentNode->getAttributeNS($this->ns_sv, 'name') == $name) 
            {           
                $this->writeNode($midgardNode, $nodeElement);
            }
        }
    }

    private function createNamespaces()
    {
        $simpleXML = simplexml_import_dom($this);

        /* For each XML namespace declaration with prefix P and URI U:
         *
         * If the namespace registry does not contain a mapping to U then
         * such a mapping is added to the registry. 
         *
         */
        foreach ($simpleXML->getDocNamespaces() as $prefix => $uri)
        {
            $q = new \midgard_query_select(new \midgard_query_storage('midgard_namespace_registry'));
            $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('prefix'), '=', new \midgard_query_value($prefix)));
            $q->execute();
            if ($q->get_results_count() == 0)
            {
                $ns = new \midgard_namespace_registry();
                $ns->prefix = $prefix;
                $ns->uri = $uri;
                $ns->create();
            }           
        }
    }

    public function execute()
    {
        $q = new \midgard_query_select(new \midgard_query_storage('midgard_tree_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('parent'), '=', new \midgard_query_value(0)));
        $q->execute();
        $root_object = current($q->list_objects());
        if ($q->get_results_count() == 0)
        {
            $root_object = new \midgard_tree_node();
            $root_object->parent = 0;
            $root_object->name = "jackalope";
            $root_object->create();
        }

        $root_node = $this->documentElement;

        $this->createNamespaces();

        $this->writeNode($root_object, $root_node);
    }
}
