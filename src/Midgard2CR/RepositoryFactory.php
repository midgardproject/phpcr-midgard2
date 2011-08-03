<?php
namespace Midgard2CR;

class RepositoryFactory implements \PHPCR\RepositoryFactoryInterface
{
    protected $configurationKeys = array(
        'midgard2.configuration.file',
    );

    public static function getRepository(array $parameters = NULL)
    {
        if (!extension_loaded('midgard2'))
        {
            throw new \PHPCR\RepositoryException("The Midgard2 PHPCR provider requires 'midgard2' extension to be loaded.");
        }

        return new Repository($parameters);    
    }

    public static function getConfigurationKeys()
    {
        return $this->configurationKeys;
    }
}
