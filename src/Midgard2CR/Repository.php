<?php
namespace Midgard2CR;

class Repository implements \PHPCR\RepositoryInterface
{
    protected $descriptors = array(
        'identifier.stability' => \PHPCR\RepositoryInterface::IDENTIFIER_STABILITY_INDEFINITE_DURATION,
        'jcr.repository.name' => 'midgard2cr',
        'jcr.repository.vendor' => 'The Midgard Project',
        'jcr.repository.vendor.url' => 'http://www.midgard-project.org',
        'jcr.repository.version' => '0.0.1',
        'jcr.specification.name' => false,
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
        'option.workspace.management.supported' => true,
        'option.xml.export.supported' => false,
        'option.xml.import.supported' => false,
        'query.full.text.search.supported' => false,
        'query.joins' => false,
        'query.languages' => '',
        'query.stored.queries.supported' => false,
        'query.xpath.doc.order' => false,
        'query.xpath.pos.index' => false,
        'write.supported' => true,
    );

    public function login(\PHPCR\CredentialsInterface $credentials = null, $workspaceName = null)
    {
        $connection = $this->midgard2Connect();
        $user = $this->midgard2Login($credentials);
        $rootObject = $this->getRootObject($workspaceName);

        if (   $credentials instanceof \PHPCR\GuestCredentials
            || is_null($credentials))
        {
            // Anonymous session
            return new Session($connection, $this, $user, $rootObject);
        }

        /* Create workspace if it doesn't exist and such has been requested */
        if ($workspaceName != null && (version_compare(mgd_version(), '10.05.4', '>')))
        {
            $ws = new \midgard_workspace();
            $wmanager = new \midgard_workspace_manager($connection);
            if ($wmanager->path_exists($workspaceName) == false)
            {
                $ws->name = $workspaceName;
                $wmanager->create_workspace($ws, "");
            }
            else 
            {
                $wmanager->get_workspace_by_path($ws, $workspaceName);   
            }

            $connection->enable_workspace(true);
            $connection->set_workspace($ws);
        }

        
        return new Session($connection, $this, $user, $rootObject);
    }
    
    private function midgard2Connect()
    {
        $mgd = \midgard_connection::get_instance();
        if ($mgd->is_connected())
        {
            return $mgd;
        }

        $filepath = ini_get('midgard.configuration_file');
        $config = new \midgard_config();
        $config->read_file_at_path($filepath);
        $mgd = \midgard_connection::get_instance();
        if (!$mgd->open_config($config))
        {
            throw new \PHPCR\RepositoryException($mgd->get_error_string());
        }
        return $mgd;
    }
    
    private function midgard2Login($credentials)
    {
        if (   !method_exists($credentials, 'getUserID')
            || !method_exists($credentials, 'getPassword'))
        {
            throw new \PHPCR\LoginException("Invalid credentials");
        }

        // TODO: Handle different authtypes
        $tokens = array
        (
            'login' => $credentials->getUserID(),
            'password' => $credentials->getPassword(),
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
    
    private function getRootObject($workspacename)
    {
        $rootnodes = $this->getRootNodes();
        if (empty($rootnodes))
        {
            throw new \PHPCR\NoSuchWorkspaceException('No workspaces defined');
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
        $ref = new ReflectionClass('\PHPCR\RepositoryInterface');
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
