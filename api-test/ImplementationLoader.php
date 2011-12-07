<?php

require __DIR__ . '/../MidgardBootstrap.php';

class ImplementationLoader extends \PHPCR\Test\AbstractLoader
{
    protected $unsupportedChapters = array(
        // Features we don't support in the Midgard provider
        'Versioning',
        'Transactions',
        'PermissionsAndCapabilities',
    );

    protected $unsupportedCases = array(
    );

    protected $unsupportedTests = array(
        // Workspace functionality isn't fully implemented
        'Connecting\WorkspaceReadMethodsTest::testGetAccessibleWorkspaceNames',
        'Connecting\WorkspaceReadMethodsTest::testGetAccessibleWorkspaceNames',
        'Writing\CopyMethodsTest::testWorkspaceCopy',
        'Writing\CopyMethodsTest::testCopyNoSuchWorkspace',
        'Writing\CopyMethodsTest::testCopySrcNotFound',
        'Writing\CopyMethodsTest::testCopyDstParentNotFound',
        'Writing\CopyMethodsTest::testCopyNoUpdateOnCopy',
        'Writing\CopyMethodsTest::testCopyUpdateOnCopy',
        'Writing\MoveMethodsTest::testWorkspaceMove',

        // Ordering is not implemented
        'Writing\MoveMethodsTest::testNodeOrderBeforeUp',
        'Writing\MoveMethodsTest::testNodeOrderBeforeDown',
        'Writing\MoveMethodsTest::testNodeOrderBeforeEnd',
        'Writing\MoveMethodsTest::testNodeOrderBeforeNoop',
        'Writing\MoveMethodsTest::testNodeOrderBeforeSrcNotFound',
        'Writing\MoveMethodsTest::testNodeOrderBeforeDestNotFound',

        // ACLs and impersonation are not yet supported
        'Reading\SessionReadMethodsTest::testImpersonate',
        'Reading\SessionReadMethodsTest::testCheckPermission',
        'Reading\SessionReadMethodsTest::testCheckPermissionAccessControlException',

        // Waiting of various bug fixes
        'Writing\DeleteMethodsTest::testDeleteCascade',
        'Writing\DeleteMethodsTest::testDeleteReferencedNodeException',
        'Writing\DeleteMethodsTest::testDeletePreviouslyReferencedNode',
        'Writing\DeleteMethodsTest::testDeleteWeakReferencedNode',
    );

    public static function getInstance()   
    {
        static $instance;
        if (!is_object($instance))
        {
            $instance = new ImplementationLoader('Midgard\PHPCR\RepositoryFactory', 'default');
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
        return $GLOBALS['phpcr.user'];
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
