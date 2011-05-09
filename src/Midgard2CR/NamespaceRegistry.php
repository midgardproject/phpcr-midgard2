<?php
namespace Midgard2CR;

class NamespaceRegistry implements \IteratorAggregate, \PHPCR\NamespaceRegistryInterface
{
    protected $session = null;
    protected $registry = null;
    protected $builtins = array('jcr' => 'http://www.jcp.org/jcr/1.0',
                                 'nt'  => 'http://www.jcp.org/jcr/nt/1.0',
                                 'mix' => 'http://www.jcp.org/jcr/mix/1.0',
                                 'xml' => 'http://www.w3.org/XML/1998/namespace',
                                 'mgd' => 'http://www.midgard-project.org/repligard/1.4',
                                 ''    => '');

    public function __construct(\Midgard2CR\Session $session)
    {
        $this->session = $session;    
        $this->registry = $this->builtins;
    }

    public function registerNamespace($prefix, $uri)
    {
        if (isset($this->builtins[$prefix]))
        {
            throw new \PHPCR\NamespaceException("Cannot register builtin namespaces");
        }
        $this->registry[$prefix] = $uri;
    }

    public function unregisterNamespace($prefix)
    {
        if (isset($this->builtins[$prefix]))
        {
            throw new \PHPCR\NamespaceException("Cannot unregister builtin namespaces");
        }
        unset($this->registry[$prefix]);
    }

    public function getPrefixes()
    {
        return array_keys($this->registry);
    }

    public function getURIs()
    {
        return array_values($this->registry);
    }

    public function getURI($prefix)
    {
        return $this->registry[$prefix];
    }

    public function getPrefix($uri)
    {
        $reversed = array_flip($this->registry);
        return $reversed[$uri];
    }
    
    public function getIterator()
    {
        return new ArrayIterator($this->registery);
    }
}

?>
