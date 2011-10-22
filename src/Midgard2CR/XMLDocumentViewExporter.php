<?php

namespace Midgard\PHPCR;

class XMLDocumentViewExporter extends XMLExporter
{
    public function __construct(Node $node, $skipBinary, $noRecurse)
    {
        $this->session = $node->getSession();
        $this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDoc->formatOutput = true;
        $this->node = $node;

        $this->nsRegistry = $this->session->getWorkspace()->getNamespaceRegistry();
        $prefixes = $this->nsRegistry->getPrefixes();

        $svUri = $this->nsRegistry->getUri($this->svNS);
        $this->svUri = $svUri;

        $this->serializeNode($node, null, $skipBinary, $noRecurse);
    }

    private function addValue(\DOMNode $pNode, $value)
    {
        $pValue = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'value', $value);
        $pNode->appendChild($pValue);
    }

    public function serializeProperties(Node $node, \DOMNode $xmlNode, $skipBinary)
    {
        $properties = self::sortProperties($node);
        if (empty($properties))
        {
            return;
        }
        foreach ($properties as $property)
        {
            /* Create property attribute */
            $propertyName = $property->getName();
            if ($property->isMultiple())
            {
                /* TODO, check if it's common approach to ignore multiple values in DocumentView */
                continue;
            }

            $propertyAttr = $this->xmlDoc->createAttribute($propertyName);
            if ($property->getType() == \PHPCR\PropertyType::BINARY)
            {
                $propertyAttr->value = $skipBinary ? '' : base64_encode($property->getString());
            }
            else
            {
                $propertyAttr->value = $property->getString();
            }
            $xmlNode->appendChild($propertyAttr);

            $this->addNamespaceAttribute($propertyName);
        }
    }

    /* Copied from Jackalope */
    private function escapeXmlName($name)
    {
        $name = preg_replace('/_(x[0-9a-fA-F]{4})/', '_x005f_\\1', $name);
        return str_replace(array(' ',       '<',       '>',       '"',       "'"),
            array('_x0020_', '_x003c_', '_x003e_', '_x0022_', '_x0027_'),
            $name); // TODO: more invalid characters?
    }

    public function serializeNode(Node $node, \DOMNode $xmlNode = null, $skipBinary, $noRecurse)
    {
        $nodeName = $node->getName();
        try
        {
            $this->xmlNode = $this->xmlDoc->createElement(self::escapeXmlName($nodeName));
        }
        catch (\DOMException $e)
        {
            echo "INVALID CHARACTER : $nodeName \n";
            exit;
        }
        if (!$xmlNode)
        {
            $this->xmlDoc->appendChild($this->xmlNode);
            $this->xmlRootNode = $this->xmlNode;
        }
        else 
        {
            $xmlNode->appendChild($this->xmlNode);
        }

        $this->serializeProperties($node, $this->xmlNode, $skipBinary);

        $this->addNamespaceAttribute($nodeName);    

        if ($noRecurse)
        {
            return;
        }

        $nodes = $node->getNodes();
        if (empty($nodes))
        {
            return;
        }   

        foreach ($nodes as $name => $child)
        {
            $this->serializeNode($child, $this->xmlNode, $skipBinary, $noRecurse);
        }
    }
}

?>
