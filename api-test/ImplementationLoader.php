<?php

require __DIR__ . '/../MidgardBootstrap.php';

class ImplementationLoader extends \PHPCR\Test\AbstractLoader
{
    protected $unsupportedChapters = array(
        'Versioning',
        'Transactions',
    );

    protected $unsupportedCases = array(
    );

    protected $unsupportedTests = array(
        'Connecting\WorkspaceReadMethodsTest::testGetAccessibleWorkspaceNames',
        'Reading\SessionReadMethodsTest::testImpersonate',
        'Reading\SessionReadMethodsTest::testCheckPermission',
        'Reading\SessionReadMethodsTest::testCheckPermissionAccessControlException',
        'Connecting\WorkspaceReadMethodsTest::testGetAccessibleWorkspaceNames',
        'Writing\MoveMethodsTest::testWorkspaceMove'
    );

    public static function getInstance()   
    {
        static $instance;
        if (!is_object($instance))
        {
            $instance = new ImplementationLoader('Midgard\PHPCR\RepositoryFactory', 'tests');
        }
        return $instance;
    }

    public function getRepositoryFactoryParameters()
    {
        return array(
            'mgd' => midgard_connection::get_instance()
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
        require_once __DIR__ . "/Midgard2ImportExport.php";
        return new Midgard2ImportExport(__DIR__."/suite/fixtures/");
    }

    public function getRepository()
    {   
        static $initialized = false;

        if ($initialized == false) {
            $mb = new MidgardBootstrap (__DIR__  . "/../data");
            $mb->execute ();
            $initialized = true;
        }
        return Midgard\PHPCR\RepositoryFactory::getRepository();
    }
}
