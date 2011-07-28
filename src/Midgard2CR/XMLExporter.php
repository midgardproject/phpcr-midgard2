<?php

namespace Midgard2CR;

abstract class XMLExporter
{
    protected $xmlDoc = null;
    protected $xmlWriter = null;
    protected $session = null;
    protected $svNS = 'sv';
    protected $svUri = null;
    protected $xmlNode = null;
    protected $node = null;

    public function serializeProperties(Node $node, DOMNode $xmlNode, $skipBinary)
    {

    }

    public function serializeNode(Node $node, $skipBinary)
    {

    }

    public function getXMLBuffer()
    {
        return $this->xmlDoc->saveXML($this->xmlNode);
    }

    public function serializeGraph()
    {

    }
}

?>
