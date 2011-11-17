<?php
namespace Midgard\PHPCR;

class RepositoryFactory implements \PHPCR\RepositoryFactoryInterface
{
    protected static $configurationKeys = array(
        // You can either provide a path to a configuration file
        'midgard2.configuration.file',
        // Or a direct set of configurations
        'midgard2.configuration.db.type',
        'midgard2.configuration.db.name',
        'midgard2.configuration.db.dir',
        'midgard2.configuration.loglevel',
        // Whether to enable automatic initialization of Midgard2 database
        'midgard2.configuration.db.init',
    );

    public static function getRepository(array $parameters = NULL)
    {
        if (!extension_loaded('midgard2'))
        {
            throw new \PHPCR\RepositoryException("The Midgard2 PHPCR provider requires 'midgard2' extension to be loaded.");
        }

        if (!class_exists('\\midgard_node'))
        {
            $shareDir = getenv('MIDGARD_ENV_GLOBAL_SHAREDIR');
            if (!$shareDir)
            {
                $config = new \midgard_config();
                $shareDir = $config->sharedir;
            }

            throw new \PHPCR\RepositoryException("Midgard2 PHPCR MgdSchema definitions not found from '{$shareDir}'. You can change this path by 'export MIDGARD_ENV_GLOBAL_SHAREDIR=/some/path'.");
        }

        return new Repository($parameters);    
    }

    public static function getConfigurationKeys()
    {
        return self::$configurationKeys;
    }
}
