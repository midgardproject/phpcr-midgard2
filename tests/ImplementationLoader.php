<?php
class ImplementationLoader extends \PHPCR\Test\AbstractLoader
{
    private static $instance = null;

    protected $unsupportedChapters = array(
        // Features we don't support in the Midgard provider
        'Versioning',
        'Transactions',
        'PermissionsAndCapabilities',
        'Locking',
        'Import',
        'Observation',
        'OrderableChildNodes',
    );

    protected $unsupportedCases = array(
        // XPath and SQL1 are deprecated JCR features, skip tests
        'Query\\Sql1',
        'Query\\XPath'
    );

    protected $unsupportedTests = array(
        // This test has missing fixtures in api-tests
        'Query\QueryObjectSql2Test::testGetStoredQueryPath',

        // These two tests below fail due to phpcr-utils issue
        'Query\QOM\ConvertQueriesBackAndForthTest::testBackAndForth',
        'Query\QOM\Sql2ToQomConverterTest::testQueries',
        'Query\QOM::testBackAndForth',
        'Query\QOM::testQueries',

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

        // CND tests
        'NodeTypeManagement\ManipulationTest::testRegisterNodeTypesCndNoUpdate',
        'NodeTypeManagement\ManipulationTest::testPrimaryItem',
        'NodeTypeManagement\ManipulationTest::testRegisterNodeTypesCnd',

        // Transactions
        //'Transactions\TransactionMethodsTest::testTransactionCommit',
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

    public function prepareAnonymousLogin()
    {
        return true;
    }

    public function getFixtureLoader()
    {
        require_once __DIR__ . "/Midgard2ImportExport.php";
        $rep = self::getInstance()->getRepository();
        return new Midgard2ImportExport(__DIR__."/../vendor/phpcr/phpcr-api-tests/fixtures/");
    }
}
