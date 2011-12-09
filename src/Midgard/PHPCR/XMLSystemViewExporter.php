<?php

namespace Midgard\PHPCR;

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
        $properties[] = $node->getProperty('jcr:primaryType');
        if (count($node->getMixinNodeTypes()) > 0) {
            $properties[] = $node->getProperty('jcr:mixinTypes');
        }
        foreach ($properties as $property) {
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
            if ($property->isMultiple()) {
                $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'multiple');
                $nodeAttr->value = 'true';
                $pNode->appendChild($nodeAttr);

                $values = $property->getString();
                /* FIXME, optimize binary values if skipBinary flag is true */
                if (!$values) {
                    continue;
                }
                foreach ($values as $v) {
                    if ($property->getType() == \PHPCR\PropertyType::BINARY) {
                        if ($skipBinary) {
                            $this->addValue($pNode, '');
                        }
                        else {
                            $this->addValue($pNode, base64_encode($v));
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
                if ($property->getType() == \PHPCR\PropertyType::BINARY) {
                    if ($skipBinary) {
                        $this->addValue($pNode, '');
                    }
                    else  {
                        $this->addValue($pNode, base64_encode($property->getString()));
                    }
                }
                else { 
                    $this->addValue($pNode, $property->getString()); 
                }
            }

            $xmlNode->appendChild($pNode);
        }
    }

    protected function createNodeElement()
    {
        return $this->xmlDoc->createElementNS($this->svUri, $this->svNS . ":" . 'node');
    }

    public function serializeNode(Node $node, \DOMNode $xmlNode = null, $skipBinary, $noRecurse)
    {
        $currentXmlNode = self::createNodeElement();
        if (!$xmlNode) {
            $this->xmlDoc->appendChild($currentXmlNode);
            $this->xmlRootNode = $currentXmlNode;
        }
        else {
            $xmlNode->appendChild($currentXmlNode);
        }

        $nodeAttr = $this->xmlDoc->createAttributeNS($this->svUri, $this->svNS . ":" . 'name');
        $nodeName = $node->getName();
        $nodeAttr->value = $nodeName;

        $currentXmlNode->appendChild($nodeAttr);
        $this->serializeProperties($node, $currentXmlNode, $skipBinary);

        $this->addNamespaceAttribute($nodeName);    

        if ($noRecurse) {
            return;
        }

        $nodes = $node->getNodes();
        if (empty($nodes)) {
            return;
        }   

        foreach ($nodes as $name => $child) {
            $this->serializeNode($child, $currentXmlNode, $skipBinary, $noRecurse);
        }
    }
}

?>
