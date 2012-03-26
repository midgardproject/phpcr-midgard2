<?php

namespace Midgard\PHPCR;

use DomDocument;
use DomElement;

class XMLSystemViewImporter extends XMLImporter
{
    public function __construct(Node $node, DomDocument $doc, $uuidBehavior)
    {
        $this->session = $node->getSession();
        $this->xmlDoc = $doc;
        $this->node = $node;
    }

    private function getPropertyValue(\DOMElement $property)
    {
        $typeElement = $property->getElementsByTagNameNS($this->svNS, 'value');
        return $typeElement->item(0)->textContent;
    }

    private function getNodeType(\DOMElement $node)
    {
        $propertyElements = $node->getElementsByTagNameNS($this->svNS, 'property');
        foreach ($propertyElements as $property)
        {
            $propertyName = $property->getAttributeNS($this->svNS, 'name');
            if ($propertyName == 'jcr:primaryType')
            {
                return $this->getPropertyValue($property);
            }
        }
        return null;
    }

    private function createNamespaces()
    {
        $simpleXML = simplexml_import_dom($this->xmlDoc);

        /* For each XML namespace declaration with prefix P and URI U:
         * 
         * If the namespace registry does not contain a mapping to U then
         * such a mapping is added to the registry. 
         */
        foreach ($simpleXML->getDocNamespaces() as $prefix => $uri)
        {
            $q = new \midgard_query_select(new \midgard_query_storage('midgard_namespace_registry'));
            $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('prefix'), '=', new \midgard_query_value($prefix)));
            $q->execute();
            if ($q->get_results_count() == 0) {
                $ns = new \midgard_namespace_registry();
                $ns->prefix = $prefix;
                $ns->uri = $uri;
                $ns->create();
            }
        }
    }

    private function addProperties(DomElement $element, Node $node)
    {
        $elementName = $element->getAttributeNS($this->svNS, 'name');
        $propertyElements = $element->getElementsByTagNameNS($this->svNS, 'property');

        foreach ($propertyElements as $property)
        {
            /* Check parent and current names.
             * getElementsByTagNameNS returns all descendants 
             */
            if ($property->parentNode->getAttributeNS($this->svNS, 'name') != $elementName) {
                continue;
            }

            $propertyName = $property->getAttributeNS($this->svNS, 'name');
            $type = $property->getAttributeNS($this->svNS, 'type');
            $propertyType = \PHPCR\PropertyType::valueFromName($type);
            $n_values = $property->getElementsByTagName('value')->length;
            for ($i = 0; $i < $n_values; $i++) {
                $value = $property->getElementsByTagName('value')->item($i);
                $node->setProperty($propertyName, $value->nodeValue, $propertyType);
            }
        }
    }

    private function recursiveImport(DomElement $element, Node $node)
    {
        $nodeName = $element->getAttributeNS($this->svNS, 'name');
        $nodeType = $this->getNodeType($element);

        /* Add new node to session */
        $newNode = $node->addNode($nodeName, $nodeType);

        /* Add properties */
        $this->addProperties($element, $newNode);

        /* Register node explicitly, as we add properties after node is added to session */
        $this->session->getNodeRegistry()->registerNode($newNode);

        $childElements = $element->getElementsByTagNameNS($this->svNS, 'node');
        foreach ($childElements as $child)
        {
            if ($child->parentNode->getAttributeNS($this->svNS, 'name') == $nodeName)
            {
                $this->recursiveImport($child, $newNode);
            }
        }
    }

    public function import() 
    {
        /* Create namepsaces */
        $this->createNamespaces();

        /* Import the whole document */
        $this->recursiveImport($this->xmlDoc->documentElement, $this->node);
    }
}
