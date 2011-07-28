<?php

namespace Midgard2CR;

class XMLSystemViewExporter extends XMLExporter
{
    public function __construct(Node $node, $skipBinary, $noRecurse)
    {
        $this->session = $node->getSession();
        $this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDoc->formatOutput = true;
        $this->node = $node;

        $nsRegistry = $this->session->getWorkspace()->getNamespaceRegistry();
        $prefixes = $nsRegistry->getPrefixes();

        $svUri = $nsRegistry->getUri($this->svNS);
        $this->svUri = $svUri;

        $this->serializeNode($node, $skipBinary);
    }

    public function serializeProperties(Node $node, \DOMNode $xmlNode, $skipBinary)
    {
        $properties = $node->getProperties();
        if (empty($properties))
        {
            return;
        }
        foreach ($properties as $name => $property)
        {
            /* Create property node */
            $pNode = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'property');
            
            /* Add name attribute */
            $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'name');
            $nodeAttr->value = $property->getName();
            $pNode->appendChild($nodeAttr);

            /* Add type attribute */
            $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'type');
            $nodeAttr->value = \PHPCR\PropertyType::nameFromValue($property->getType());
            $pNode->appendChild($nodeAttr);

            /* Add multiple attribute */
            if ($property->isMultiple())
            {
                $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'multiple');
                $nodeAttr->value = 'true';
                $pNode->appendChild($nodeAttr);
            }

            /* Create value node*/
            if ($property->getType() == \PHPCR\PropertyType::BINARY)
            {
                $pValue = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'value', $skpiBinary ? '' : $property->getString());
            }
            $pValue = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'value', $property->getString());
            $pNode->appendChild($pValue);
            $xmlNode->appendChild($pNode);
        }
    }

    public function serializeNode(Node $node, $skipBinary)
    {
        $this->xmlNode = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'node');
        $this->xmlDoc->appendChild($this->xmlNode);

        $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'name');
        $nodeAttr->value = $node->getName();

        $this->xmlNode->appendChild($nodeAttr);
        $this->serializeProperties($node, $this->xmlNode, $skipBinary);
    }

    public function serializeGraph()
    {

    }
}

?>
