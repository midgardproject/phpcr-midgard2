<?php
namespace Midgard2CR;

class NamespaceRegistry implements \IteratorAggregate, \PHPCR\NamespaceRegistryInterface
{
    protected $session = null;
    protected $registry = null;

    public function NamespaceRegistry (\Midgard2CR\Session $session)
    {
        $this->session = $session;    
        $this->registry = array();
    }

    public function registerNamespace($prefix, $uri)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function unregisterNamespace($prefix)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getPrefixes()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getURIs()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getURI($prefix)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getPrefix($uri)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
    
    public function getIterator()
    {

    }
}

?>
