<?php
namespace Midgard2CR;

class RepositoryFactory implements \PHPCR\RepositoryFactoryInterface
{
    public function __construct()
    {
        if (!extension_loaded('midgard2'))
        {
            throw new \PHPCR\RepositoryException();
        }
    }

    public function getRepository(array $parameters = NULL)
    {
        return new Repository();    
    }

    public function getConfigurationKeys()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
}
