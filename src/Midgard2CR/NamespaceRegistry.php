<?php
namespace Midgard2CR;

class NamespaceRegistry implements \IteratorAggregate, \PHPCR\NamespaceRegistryInterface
{
    protected $session = null;
    protected $registry = null;

    const MGD_PREFIX_MGD = 'mgd';
    const MGD_NAMESPACE_MGD = 'http://www.midgard-project.org/repligard/1.4';

    protected $builtins = array(
        self::PREFIX_JCR   => self::NAMESPACE_JCR,
        self::PREFIX_NT    => self::NAMESPACE_NT,
        self::PREFIX_MIX   => self::NAMESPACE_MIX,
        self::PREFIX_XML   => self::NAMESPACE_XML,
        self::PREFIX_EMPTY => self::NAMESPACE_EMPTY,
        self::MGD_PREFIX_MGD => self::MGD_NAMESPACE_MGD,
        ''    => ''
    );

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

    public function getNamespaceManager()
    {
        return new \Midgard2CR\NamespaceManager($this);
    }
}

?>
