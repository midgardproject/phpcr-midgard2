<?php

namespace Midgard\PHPCR;

use DomDocument;

class XMLDocumentViewImporter extends XMLIMporter
{
    public function __construct(Node $node, DomDocument $doc, $uuidBehavior)
    {
        $this->session = $node->getSession();
        $this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDoc->formatOutput = true;
        $this->node = $node;
    }

    public function import() {

    }
}
