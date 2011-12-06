<?php
namespace Midgard\PHPCR;

class RepositoryFactory implements \PHPCR\RepositoryFactoryInterface
{
    protected static $configurationKeys = array(
        // You can either provide a path to a configuration file
        'midgard2.configuration.file' => 'string: path to a Midgard2 configuration file',
        // Or a direct set of configurations
        'midgard2.configuration.db.type' => 'string: database type (SQLite, MySQL, ...)',
        'midgard2.configuration.db.name' => 'string: database name',
        'midgard2.configuration.db.dir' => 'string: database directory path (when used with SQLite)',
        'midgard2.configuration.loglevel' => 'string: Midgard2 log level',
        'midgard2.configuration.blobdir' => 'string: path of the attachment storage root directory',
        // Whether to enable automatic initialization of Midgard2 database
        'midgard2.configuration.db.init' => 'boolean: whether Midgard2 database should be initialized automatically',
    );

    public static function getRepository(array $parameters = NULL)
    {
        static $repository = null;

        if ($repository !== null)
            return $repository;

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

        $repository = new Repository($parameters);    
        return $repository;
    }

    public static function getConfigurationKeys()
    {
        return self::$configurationKeys;
    }
}
