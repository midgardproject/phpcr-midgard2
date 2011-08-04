<?php
namespace Midgard2CR;

class NamespaceRegistry implements \IteratorAggregate, \PHPCR\NamespaceRegistryInterface
{
    protected $session = null;
    protected $registry = null;
    protected $namespaceObjects = null;
    protected $manager = null;

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
                    $this->namespaceObjects[$ns->prefix] = $ns;
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

        /* Assigning a new prefix to a URI that already exists in the namespace registry erases the old prefix */
        if (in_array($uri, $this->registry))
        {
            $registeredPrefix = $this->getPrefix($uri);
            if ($prefix == $registeredPrefix)
            {
                return;
            }
            $this->unregisterNamespace($registeredPrefix);
        }

        /* Invalid prefix */
        if (strpos($prefix, ':') != false)
        {
            throw new \PHPCR\RepositoryException("Given prefix contains invalid ':' character");
        }

        $lowprefix = strtolower($prefix);
        if (   strpos($lowprefix, "xml") !== false
            || strpos($lowprefix, "mgd") !== false)
        {
            throw new \PHPCR\NamespaceException("Prefix beginning with 'xml' or 'mgd' can not be registered");
        }

        $this->registry[$prefix] = $uri;

        /* API doesn't clarify if it's session save, so create namespace on demand */ 
        if (!isset($this->namespaceObjects[$prefix]))
        {
            $ns = new \midgard_namespace_registry();
            $ns->prefix = $prefix;
            $ns->uri = $uri;

            $ns->create();
            $this->namespaceObjects[$prefix] = $ns;
        }
    }

    public function unregisterNamespace($prefix)
    {
        if (isset($this->builtins[$prefix]))
        {
            throw new \PHPCR\NamespaceException("Cannot unregister builtin namespaces");
        }
        if (!isset($this->registry[$prefix]))
        {
            throw new \PHPCR\NamespaceException("Can not unregister '{$prefix}' which is not registered");
        }
        unset($this->registry[$prefix]);

        /* On demand remove namespace from storage */
        $ns = $this->namespaceObjects[$prefix];
        $ns->purge();
        unset($this->namespaceObjects[$prefix]);
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
        if ($this->manager == null)
        {
            $this->manager = new \Midgard2CR\NamespaceManager($this);
        }
        return $this->manager;
    }
}

?>
