<?php
class MidgardBootstrap 
{
    private $sharedir = null;
    private $sourceDir = null;

    public function __construct ($sourceDir)
    {
        $this->sourceDir = $sourceDir;
        $this->sharedir = getenv('MIDGARD_ENV_GLOBAL_SHAREDIR');

        /* Sharedir is not set. Ignore */
        if ($this->sharedir == "" || $this->sharedir === false)
            return;

        /* Common, system wide directory. Ignore */
        if ($this->sharedir == '/usr/share/midgard2'
            || $this->sharedir == '/usr/local/share/midgard2')
            return;

        if (is_dir($this->sharedir) && !is_writeable($this->sharedir))
            throw new Exception("Shared directory {$this->sharedir} is not writeable");
    }

    public function execute () 
    {
        $this->makeDirs();
        $this->getMidgardConnection();
        $this->prepareMidgardStorage();
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

        exec("cp -r " . $this->sourceDir . "/share/* " . $this->sharedir);
        exec("cp " . $this->sourceDir . "/midgard2.conf " . $this->sharedir);
   
        $config = new \midgard_config();
        $config->sharedir = $this->sharedir;
        $config->blobdir = $this->sharedir . "/blobs";
        $config->dbdir = $this->sharedir;
        $config->read_file_at_path($this->sharedir."/midgard2.conf");
        if (!$midgard->open_config($config))
        {
            throw new Exception('Could not open Midgard connection to test database: ' . $midgard->get_error_string());
        }

        $config->create_blobdir();

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
            $type = $refclass->getName();           

            if (!is_subclass_of ($type, 'MidgardDBObject')
                || $refclass->isAbstract()) {
                continue;
            }

            if (midgard_storage::class_storage_exists($type))
            {
                continue;
            }

            if (!midgard_storage::create_class_storage($type))
            {
                throw new Exception('Could not create ' . $type . ' tables in test database.' . midgard_connection::get_instance()->get_error_string());
            }
        }

        // Set up default workspace
        $ws = new midgard_workspace();
        $wmanager = new midgard_workspace_manager(midgard_connection::get_instance());
        if (!$wmanager->path_exists('default')) {
            $ws->name = 'default';
            $wmanager->create_workspace($ws, "");
        }

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

    private function makeDirs ()
    {       
        $dirs = array ('share', 'views', 'blobs', 'var', 'cache');
        foreach ($dirs as $dir) {
            $createDir = $this->sharedir . "/" . $dir;
            if (!file_exists($createDir))
            {
                if (!mkdir($createDir, 0777, true))
                    throw new Exception("Faile to create shared directory '{$createDir}'");
            }
        }
    }
}
