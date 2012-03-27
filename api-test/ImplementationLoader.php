<?php
class ImplementationLoader extends \PHPCR\Test\AbstractLoader
{
    private static $instance = null;

    protected $unsupportedChapters = array(
        // Features we don't support in the Midgard provider
        'Versioning',
        'PermissionsAndCapabilities',
        'Locking'
    );

    protected $unsupportedCases = array(
    );

    protected $unsupportedTests = array(
        // This test has missing fixtures in api-tests
        'Query\QueryObjectSql2Test::testGetStoredQueryPath',

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
        if (null === self::$instance) {
            self::$instance = new ImplementationLoader('\Midgard\PHPCR\RepositoryFactory', 'default');
        }
        return self::$instance;
    }

    public function getRepositoryFactoryParameters()
    {
        $factoryclass = $this->factoryclass;
        return array_intersect_key($GLOBALS, $factoryclass::getConfigurationKeys());
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
        $rep = self::getInstance()->getRepository();
        return new Midgard2ImportExport(__DIR__."/suite/fixtures/");
    }
}
