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
        $filepath = ini_get('midgard.configuration_file');
        $config = new \midgard_config();
        $config->read_file_at_path($filepath);
        $mgd = \midgard_connection::get_instance();
        if (!$mgd->open_config($config))
        {
            throw new \PHPCR\RepositoryException();
        }
        return new Repository();    
    }
}
