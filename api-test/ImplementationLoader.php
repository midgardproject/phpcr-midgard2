<?php
class ImplementationLoader extends \PHPCR\Test\AbstractLoader
{
    protected $unsupportedChapters = array(
        'Versioning',
        'Transactions',
    );

    protected $unsupportedCases = array(
    );

    protected $unsupportedTests = array(
    );

    public static function getInstance()   
    {
        static $instance;
        if (!is_object($instance))
        {
            $instance = new ImplementationLoader('Midgard2CR\RepositoryFactory', 'tests');
        }
        return $instance;
    }

    private function getMidgardConnection() 
    {
        // Open connection
        $midgard = \midgard_connection::get_instance();
        if ($midgard->is_connected())
        {
            // Already connected
            return $midgard;
        }

        self::prepareMidgardTestDir('share');
        self::prepareMidgardTestDir('views');
        self::prepareMidgardTestDir('blobs');
        self::prepareMidgardTestDir('var');
        self::prepareMidgardTestDir('cache');

        exec("cp -r Midgard2/share/* /tmp/Midgard2CR/share");
        exec("cp Midgard2/midgard2.conf /tmp/Midgard2CR/midgard2.conf");
    
        $config = new \midgard_config();
        $config->read_file_at_path("/tmp/Midgard2CR/midgard2.conf");
        if (!$midgard->open_config($config))
        {
            throw new Exception('Could not open Midgard connection to test database: ' . $midgard->get_error_string());
        }

        $config->create_blobdir();

        self::prepareMidgardStorage();

        return $midgard;
    }

    private function prepareMidgardStorage()
    {
        /* Be prepared for workspace */
        if (version_compare(mgd_version(), '10.05.4', '>'))
        {
            midgard_connection::get_instance()->enable_workspace(true);
        }

        midgard_storage::create_base_storage();

        // And update as necessary
        $re = new ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        foreach ($classes as $refclass)
        {
            $parent_class = $refclass->getParentClass();
            if (!$parent_class)
            {
                continue;
            }
            if ($parent_class->getName() != 'midgard_object')
            {
                continue;
            }

            $type = $refclass->getName();            
            if (midgard_storage::class_storage_exists($type))
            {
                continue;
            }

            if (!midgard_storage::create_class_storage($type))
            {
                throw new Exception('Could not create ' . $type . ' tables in test database.' . midgard_connection::get_instance()->get_error_string());
            }
        }

        /* Prepare properties view */
        midgard_storage::create_class_storage("midgard_property_view");

        /* Prepare namespace registry */
        midgard_storage::create_class_storage("midgard_namespace_registry");

        /* Create required root node */
        $q = new \midgard_query_select(new \midgard_query_storage('midgard_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('parent'), '=', new \midgard_query_value(0)));
        $q->execute();
        if ($q->get_results_count() == 0)
        {
            $root_object = new \midgard_node();
            $root_object->name = "jackalope";
            $root_object->parent = 0;
            $root_object->create();
        }
    }

    function prepareMidgardTestDir($dir)
    {
        if (!file_exists("/tmp/Midgard2CR/{$dir}"))
        {
            mkdir("/tmp/Midgard2CR/{$dir}", 0777, true);
        }
    }

    public function getRepositoryFactoryParameters()
    {
        return array(
            'mgd' => self::getMidgardConnection()
        );
    }

    public function getCredentials()
    {
        return new \PHPCR\SimpleCredentials($GLOBALS['phpcr.user'], $GLOBALS['phpcr.pass']);
    }

    public function getInvalidCredentials()
    {
        return new \PHPCR\SimpleCredentials('foo', 'bar');
    }

    public function getRestrictedCredentials()
    {
        return new \PHPCR\SimpleCredentials('admin', 'password');
    }

    public function getUserId()
    {
        
    }

    public function getFixtureLoader()
    {
        require_once "Midgard2ImportExport.php";
        return new Midgard2ImportExport(__DIR__."/suite/fixtures/");
    }

    public function getRepository()
    {
        $mgd = self::getMidgardConnection();
        return Midgard2CR\RepositoryFactory::getRepository();
    }
}
