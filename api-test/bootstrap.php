<?php

/**
 * Bootstrap file for jackalope
 *
 * This file does some basic stuff that's project specific.
 *
 * function getRepository(config) which returns the repository
 * function getJCRSession(config) which returns the session
 *
 * TODO: remove the following once it has been moved to a base file
 * function getSimpleCredentials(user, password) which returns simpleCredentials
 *
 * constants necessary to the JCR 1.0/JSR-170 and JSR-283 specs
 */

if (getenv('MIDGARD_ENV_GLOBAL_SHAREDIR') != '/tmp/JackalopeMidgard2/share')
{
    die("\nBefore running these tests you need to run 'export MIDGARD_ENV_GLOBAL_SHAREDIR=/tmp/JackalopeMidgard2/share'\n");
}

function getMidgardConnection() {
    // Open connection
    $midgard = \midgard_connection::get_instance();
    if ($midgard->is_connected())
    {
        // Already connected
        return $midgard;
    }

    prepareMidgardTestDir('share');
    prepareMidgardTestDir('views');
    prepareMidgardTestDir('blobs');
    prepareMidgardTestDir('var');
    prepareMidgardTestDir('cache');

    exec("cp -r Midgard2/share/* /tmp/JackalopeMidgard2/share");
    exec("cp Midgard2/midgard2.conf /tmp/JackalopeMidgard2/midgard2.conf");
    
    $config = new \midgard_config();
    $config->read_file_at_path("/tmp/JackalopeMidgard2/midgard2.conf");
    if (!$midgard->open_config($config))
    {
        throw new Exception('Could not open Midgard connection to test database: ' . $midgard->get_error_string());
    }

    $config->create_blobdir();

    prepareMidgardStorage();

    return $midgard;
}

function prepareMidgardStorage()
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
            throw new Exception('Could not create ' . $type . ' tables in test database');
        }
    }

    /* Prepare properties view */
    midgard_storage::create_class_storage("midgard_property_view");

    /* Prepare namespace registry */
    midgard_storage::create_class_storage("midgard_namespace_registry");

    /* Create required root node */
    $q = new \midgard_query_select(new \midgard_query_storage('midgardmvc_core_node'));
    $q->set_constraint(new \midgard_query_constraint(new \midgard_query_property('up'), '=', new \midgard_query_value(0)));
    $q->execute();
    if ($q->get_results_count() == 0)
    {
        $root_object = new \midgardmvc_core_node();
        $root_object->name = "jackalope";
        $root_object->create();
    }
}

function prepareMidgardTestDir($dir)
{
    if (!file_exists("/tmp/JackalopeMidgard2/{$dir}"))
    {
        mkdir("/tmp/JackalopeMidgard2/{$dir}", 0777, true);
    }
}

// Make sure we have the necessary config
$necessaryConfigValues = array('phpcr.url', 'phpcr.user', 'phpcr.pass', 'phpcr.workspace', 'phpcr.transport');
foreach ($necessaryConfigValues as $val) {
    if (empty($GLOBALS[$val])) {
        die('Please set '.$val.' in your phpunit.xml.' . "\n");
    }
}

require_once(dirname(__FILE__) . '/../SplClassLoader.php');

// Midgard2CR is in the src dir
$midgard2crAutoloader = new SplClassLoader('Midgard2CR', dirname(__FILE__) . '/../src');
$midgard2crAutoloader->register();
// Midgard2CR\Query 
$midgard2crQAutoloader = new SplClassLoader('Midgard2CR\Query', dirname(__FILE__) . '/../src/Midgard2CR/Query');
$midgard2crQAutoloader->register();
// PHPCR is in a submodule in lib/PHPCR
$phpcrAutoloader = new SplClassLoader('PHPCR', dirname (__FILE__) . '/../lib/PHPCR/src');
$phpcrAutoloader->register();

function getRepository($config) {
    $mgd = getMidgardConnection();

    $factory = new Midgard2CR\RepositoryFactory();
    return $factory->getRepository();
}

/**
 * @param user The user name for the credentials
 * @param password The password for the credentials
 * @return the simple credentials instance for this implementation with the specified username/password
 */
function getSimpleCredentials($user, $password) {
    return new \PHPCR\SimpleCredentials($user, $password);
}

/**
 * Get a session for this implementation.
 * @param config The configuration that is passed to getRepository
 * @param credentials The credentials to log into the repository. If omitted, $config['user'] and $config['pass'] is used with getSimpleCredentials
 * @return A session resulting from logging into the repository found at the $config path
 */
function getPHPCRSession($config, $credentials = null) {
    $repository = getRepository($config);
    if (isset($config['pass']) || isset($credentials)) {
        if (empty($config['workspace'])) {
            $config['workspace'] = null;
        }
        if (empty($credentials)) {
            $credentials = getSimpleCredentials($config['user'], $config['pass']);
        }
        return $repository->login($credentials, $config['workspace']);
    } elseif (isset($config['workspace'])) {
        throw new \PHPCR\RepositoryException("Not supported login");
        //return $repository->login(null, $config['workspace']);
    } else {
        throw new \PHPCR\RepositoryException("Not supported login");
        //return $repository->login(null, null);
    }
}

function getFixtureLoader($config)
{ 
    require_once "Midgard2ImportExport.php";
    return new Midgard2ImportExport(__DIR__."/suite/fixtures/");
}

/** some constants */

define('SPEC_VERSION_DESC', 'jcr.specification.version');
define('SPEC_NAME_DESC', 'jcr.specification.name');
define('REP_VENDOR_DESC', 'jcr.repository.vendor');
define('REP_VENDOR_URL_DESC', 'jcr.repository.vendor.url');
define('REP_NAME_DESC', 'jcr.repository.name');
define('REP_VERSION_DESC', 'jcr.repository.version');
define('LEVEL_1_SUPPORTED', 'level.1.supported');
define('LEVEL_2_SUPPORTED', 'level.2.supported');
define('OPTION_TRANSACTIONS_SUPPORTED', 'option.transactions.supported');
define('OPTION_VERSIONING_SUPPORTED', 'option.versioning.supported');
define('OPTION_OBSERVATION_SUPPORTED', 'option.observation.supported');
define('OPTION_LOCKING_SUPPORTED', 'option.locking.supported');
define('OPTION_QUERY_SQL_SUPPORTED', 'option.query.sql.supported');
define('QUERY_XPATH_POS_INDEX', 'query.xpath.pos.index');
define('QUERY_XPATH_DOC_ORDER', 'query.xpath.doc.order');

