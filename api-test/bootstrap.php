<?php

if (gc_enabled()) {
    echo "Disabling Zend Garbage Collection to prevent segfaults, see:\n";
    echo "  https://bugs.php.net/bug.php?id=51091\n";
    echo "  https://github.com/midgardproject/midgard-php5/issues/50\n";
    gc_disable(); 
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

if (getenv('MIDGARD_ENV_GLOBAL_SHAREDIR') != '/tmp/Midgard2CR/share')
{
    die("\nBefore running these tests you need to run 'export MIDGARD_ENV_GLOBAL_SHAREDIR=/tmp/Midgard2CR/share'\n");
}

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

require './suite/inc/FixtureLoaderInterface.php';
require './suite/inc/AbstractLoader.php';
require 'ImplementationLoader.php';
