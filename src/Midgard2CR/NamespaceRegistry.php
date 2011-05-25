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

        $q = new \midgard_query_select(new \midgard_query_storage('midgard_namespace_registry')); 
        $q->execute();
        if ($q->get_results_count() > 0)
        {
            foreach ($q->list_objects() as $ns)
            {
                try 
                {
                    $this->registerNamespace($ns->prefix, $ns->uri);
                }
                catch (\PHPCR\NamespaceException $e)
                {
                    /* Ignore */
                }
            }
        }
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
        if (array_key_exists ($prefix, $this->registry) == true)
        {
            return $this->registry[$prefix];
        }
        throw new \PHPCR\NamespaceException("{$prefix} not registered");
    }

    public function getPrefix($uri)
    {
        if (in_array ($uri, $this->registry) == true)
        {
            $reversed = array_flip($this->registry);
            return $reversed[$uri];
        }
        throw new \PHPCR\NamespaceException("{$uri} not registered");
    }
    
    public function getIterator()
    {
        return new \ArrayIterator($this->registry);
    }

    public function getNamespaceManager()
    {
        return new \Midgard2CR\NamespaceManager($this);
    }
}

?>
