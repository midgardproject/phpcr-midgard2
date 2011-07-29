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
            /* Create property node */
            $pNode = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'property');
            
            /* Add name attribute */
            $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'name');
            $propertyName = $property->getName();
            $nodeAttr->value = $propertyName;
            $pNode->appendChild($nodeAttr);

            $this->addNamespaceAttribute($propertyName);

            /* Add type attribute */
            $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'type');
            $nodeAttr->value = \PHPCR\PropertyType::nameFromValue($property->getType());
            $pNode->appendChild($nodeAttr);

            /* Add multiple flag attribute */
            if ($property->isMultiple())
            {
                $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'multiple');
                $nodeAttr->value = 'true';
                $pNode->appendChild($nodeAttr);

                $values = $property->getString();
                /* FIXME, optimize binary values if skipBinary flag is true */
                foreach ($values as $v)
                {
                    if ($property->getType() == \PHPCR\PropertyType::BINARY)
                    {
                        if ($skipBinary)
                        {
                            $this->addValue($pNode, '');
                        }
                        else 
                        {
                            $this->addValue($pNode, base64_encode(base64_encode($v)));
                        }
                    }
                    else
                    {
                        $this->addValue($pNode, $v);
                    }
                }
            }
            else 
            {
                if ($property->getType() == \PHPCR\PropertyType::BINARY)
                {
                    if ($skipBinary)
                    {
                        $this->addValue($pNode, '');
                    }
                    else 
                    {
                        $this->addValue($pNode, base64_encode($property->getString()));
                    }
                }
                else
                { 
                    $this->addValue($pNode, $property->getString()); 
                }
            }

            $xmlNode->appendChild($pNode);
        }
    }

    public function serializeNode(Node $node, \DOMNode $xmlNode = null, $skipBinary, $noRecurse)
    {
        $this->xmlNode = $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'node');
        if (!$xmlNode)
        {
            $this->xmlDoc->appendChild($this->xmlNode);
            $this->xmlRootNode = $this->xmlNode;
        }
        else 
        {
            $xmlNode->appendChild($this->xmlNode);
        }

        $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'name');
        $nodeName = $node->getName();
        $nodeAttr->value = $nodeName;

        $this->xmlNode->appendChild($nodeAttr);
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

    public function serializeGraph(Node $node, $skipBinary)
    {
        $this->serializeNode($node, $skipBinary, true);
    }
}

?>
