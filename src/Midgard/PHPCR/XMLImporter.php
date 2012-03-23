<?php

namespace Midgard\PHPCR;

abstract class XMLImporter
{
    protected $xmlDoc = null;
    protected $session = null;
    protected $svNS = 'http://www.jcp.org/jcr/sv/1.0';
    protected $svPrefix = 'sv';
    protected $svUri = null;
    protected $xmlNode = null;
    protected $xmlRootNode = null;
    protected $node = null;
    protected $nsRegistry = null;

    public function import()
    {

    }
}

?>
