<?php

namespace Midgard2CR;

abstract class XMLExporter
{
    protected $xmlDoc = null;
    protected $session = null;
    protected $svNS = 'sv';
    protected $svUri = null;
    protected $xmlNode = null;
    protected $xmlRootNode = null;
    protected $node = null;
    protected $nsRegistry = null;

    public function serializeProperties(Node $node, DOMNode $xmlNode, $skipBinary)
    {

    }

    public function serializeNode(Node $node, $skipBinary)
    {

    }

    protected function sortProperties(Node $node)
    {
        $ret = array();
        $properties = $node->getProperties();
        if (empty($properties))
        {
            return $ret;
        }

        $ret[0] = ' ';
        $ret[1] = ' ';

        $hasMixin = false;
        foreach ($properties as $name => $property)
        {
            if ($name == 'jcr:primaryType')
            {
                $ret[0] = $property;
            }
            else if ($name == 'mix:mixinTypes')
            {
                $ret[1] = $property;
                $hasMixin = true;
            }
            else
            {
                $ret[] = $property;
            }
        }
        
        if (!$hasMixin)
        {
            unset($ret[1]);
        }
        return $ret;
    }

    protected function addNamespaceAttribute($name)
    {
        if (!$this->xmlRootNode)
        {
            return;
        }

        $nsManager = $this->nsRegistry->getNamespaceManager();
        $prefix = $nsManager->getPrefix($name);

        if (!$prefix)
        {
            return;
        }

        $uri = $this->nsRegistry->getURI($prefix);
        if (!$uri)
        {
            return;
        }

        try {
            $nsAttr = $this->xmlDoc->createAttribute('xmlns:'.$prefix);
            $nsAttr->value = $uri;
            $this->xmlRootNode->appendChild($nsAttr);
        }
        catch (\DOMException $e)
        {
            echo $e->getMessage();
        }
    }

    public function getXMLBuffer()
    {
        return $this->xmlDoc->saveXML();
    }

    public function serializeGraph(Node $node, $skipBinary)
    {
        $this->serializeNode($node, $skipBinary, true);
    }
}

?>
