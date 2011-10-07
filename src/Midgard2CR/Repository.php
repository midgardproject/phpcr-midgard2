<?php
namespace Midgard2CR;

class Repository implements \PHPCR\RepositoryInterface
{
    protected $descriptors = array(
        'identifier.stability' => \PHPCR\RepositoryInterface::IDENTIFIER_STABILITY_INDEFINITE_DURATION,
        'jcr.repository.name' => 'Midgard2CR',
        'jcr.repository.vendor' => 'The Midgard Project',
        'jcr.repository.vendor.url' => 'http://www.midgard-project.org',
        'jcr.repository.version' => '',
        'jcr.specification.name' => 'Content Repository for Java Technology API',
        'jcr.specification.version' => '2.0',
        'write.supported' => true,
        'level.1.supported' => true,
        'level.2.supported' => true,
        'node.type.management.autocreated.definitions.supported' => true,
        'node.type.management.inheritance' => true,
        'node.type.management.multiple.binary.properties.supported' => true,
        'node.type.management.multivalued.properties.supported' => true,
        'node.type.management.orderable.child.nodes.supported' => false,
        'node.type.management.overrides.supported' => false,
        'node.type.management.primary.item.name.supported' => true,
        'node.type.management.property.types' => true,
        'node.type.management.residual.definitions.supported' => false,
        'node.type.management.same.name.siblings.supported' => false,
        'node.type.management.update.in.use.suported' => false,
        'node.type.management.value.constraints.supported' => false,
        'option.access.control.supported' => false,
        'option.activities.supported' => false,
        'option.baselines.supported' => false,
        'option.journaled.observation.supported' => false,
        'option.lifecycle.supported' => false,
        'option.locking.supported' => false,
        'option.node.and.property.with.same.name.supported' => false,
        'option.node.type.management.supported' => true,
        'option.observation.supported' => false,
        'option.query.sql.supported' => true,
        'option.retention.supported' => false,
        'option.shareable.nodes.supported' => false,
        'option.simple.versioning.supported' => false,
        'option.transactions.supported' => false,
        'option.unfiled.content.supported' => false,
        'option.update.mixin.node.types.supported' => true,
        'option.update.primary.node.type.supported' => true,
        'option.versioning.supported' => false,
        'option.workspace.management.supported' => false,
        'option.xml.export.supported' => true,
        'option.xml.import.supported' => false,
        'query.full.text.search.supported' => false,
        'query.joins' => false,
        'query.languages' => '',
        'query.stored.queries.supported' => false,
        'query.xpath.doc.order' => false,
        'query.xpath.pos.index' => false,
        'write.supported' => true,
    );

    protected $connection = null;

    public function __construct(array $parameters = null)
    {
        $this->descriptors['jcr.repository.version'] = mgd_version();

        if (version_compare(mgd_version(), '10.05.4', '>'))
        {
            $this->descriptors['option.workspace.management.supported'] = true; 
        }

        $this->connection = $this->midgard2Connect($parameters);
    }

    public function login(\PHPCR\CredentialsInterface $credentials = null, $workspaceName = null)
    {
        $user = $this->midgard2Login($credentials);
        $rootObject = $this->getRootObject();

        if ($workspaceName != null)
        {
            $this->midgard2SetWorkspace($workspaceName);
        }

        /* if (   $credentials instanceof \PHPCR\GuestCredentials
            || is_null($credentials))
        {
            // Anonymous session
            return new Session($this->connection, $this, $user, $rootObject, $credentials);
        } */
        
        return new Session($this->connection, $this, $user, $rootObject, $credentials);
    }
    
    private function midgard2Connect(array $parameters = null)
    {
        $mgd = \midgard_connection::get_instance();
        if ($mgd->is_connected())
        {
            return $mgd;
        }

        $config = new \midgard_config();
        if (isset($parameters['midgard2.configuration.file']))
        {
            $config->read_file_at_path($parameters['midgard2.configuration.file']);
        }
        elseif (   isset($parameters['midgard2.configuration.db.type'])
                && isset($parameters['midgard2.configuration.db.name'])
                && isset($parameters['midgard2.configuration.db.dir']))
        {
            $config->dbtype = $parameters['midgard2.configuration.db.type'];
            $config->database = $parameters['midgard2.configuration.db.name'];
            $config->dbdir = $parameters['midgard2.configuration.db.dir'];
        }
        else
        {
            throw new \PHPCR\RepositoryException('No initialized Midgard2 connection or configuration parameters available');
        }

        if (isset($parameters['midgard2.configuration.loglevel']))
        {
            $config->loglevel = $parameters['midgard2.configuration.loglevel'];
        }

        $mgd = \midgard_connection::get_instance();
        if (!$mgd->open_config($config))
        {
            throw new \PHPCR\RepositoryException($mgd->get_error_string());
        }

        if (   isset($parameters['midgard2.configuration.db.init'])
            && $parameters['midgard2.configuration.db.init'])
        {
            $this->midgard2InitDb($mgd);
        }

        return $mgd;
    }
    
    private function midgard2Login($credentials = null)
    {
        if (   !method_exists($credentials, 'getUserID')
            || !method_exists($credentials, 'getPassword'))
        {
            throw new \PHPCR\LoginException("Invalid credentials");
        }

        // TODO: Handle different authtypes
        $tokens = array
        (
            'login' => $credentials ? $credentials->getUserID() : 'admin',
            'password' => $credentials ? $credentials->getPassword() : 'password',
            'authtype' => 'Plaintext',
            'active' => true
        );
        
        try
        {
            $user = new \midgard_user($tokens);
            $user->login();
        }
        catch (\midgard_error_exception $e)
        {
            throw new \PHPCR\LoginException($e->getMessage());
        }
        
        return $user;
    }

    /** 
     * Create workspace if it doesn't exist and such has been requested
     */
    private function midgard2SetWorkspace($workspaceName)
    {
        if (!$this->descriptors['option.workspace.management.supported'])
        {
            return;
        }

        $ws = new \midgard_workspace();
        $wmanager = new \midgard_workspace_manager($this->connection);
        if ($wmanager->path_exists($workspaceName) == false)
        {
            $ws->name = $workspaceName;
            $wmanager->create_workspace($ws, "");
        }
        else 
        {
            $wmanager->get_workspace_by_path($ws, $workspaceName);   
        }

        $this->connection->enable_workspace(true);
        $this->connection->set_workspace($ws);
    }

    private function midgard2InitDb($connection)
    {
        if ($this->descriptors['option.workspace.management.supported'])
        {
            $connection->enable_workspace(true);
        }

        \midgard_storage::create_base_storage();

        $re = new \ReflectionExtension('midgard2');
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
            if (\midgard_storage::class_storage_exists($type))
            {
                continue;
            }

            \midgard_storage::create_class_storage($type);
        }

        /* Prepare properties view */
        \midgard_storage::create_class_storage("midgard_property_view");

        /* Prepare namespace registry */
        \midgard_storage::create_class_storage("midgard_namespace_registry");

        /* Create required root node */
        $q = new \midgard_query_select(new \midgard_query_storage('midgard_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('parent'), '=', new \midgard_query_value(0)));
        $q->execute();
        if ($q->get_results_count() == 0)
        {
            $root_object = new \midgard_node();
            $root_object->name = "root";
            $root_object->parent = 0;
            $root_object->create();
        }

        if ($this->descriptors['option.workspace.management.supported'])
        {
            $connection->enable_workspace(false);
        }
    }
    
    private function getRootObject()
    {
        $rootnodes = $this->getRootNodes();
        if (empty($rootnodes))
        {
            throw new \PHPCR\NoSuchWorkspaceException('No root nodes defined');
        }
        return $rootnodes[0];
    }
    
    private function getRootNodes()
    {
        $q = new \midgard_query_select(new \midgard_query_storage('midgard_node'));
        $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('parent'), '=', new \midgard_query_value(0)));
        $q->toggle_readonly = false;
        $q->execute();
        return $q->list_objects();
    }
    
    public function getDescriptorKeys()
    {
        return array_keys($this->descriptors);
    }
    
    public function isStandardDescriptor($key)
    {
        $ref = new \ReflectionClass('\PHPCR\RepositoryInterface');
        $consts = $ref->getConstants();
        return in_array($key, $consts);
    }
    
    public function getDescriptor($key)
    {
        return (isset($this->descriptors[$key])) ?  $this->descriptors[$key] : null;
    }

    public static function checkMidgard2Exception($object = null)
    {
        if (\midgard_connection::get_instance()->get_error() != MGD_ERR_OK)
        {
            $msg = "";
            if ($object != null)
            {
                $msg = get_class($object) . "." . $object->name . " : ";
            }
            throw new \PHPCR\RepositoryException($msg . \midgard_connection::get_instance()->get_error_string());
        }
    }
}
