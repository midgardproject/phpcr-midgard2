<?php

if (gc_enabled()) {
    echo "Disabling Zend Garbage Collection to prevent segfaults, see:\n";
    echo "  https://bugs.php.net/bug.php?id=51091\n";
    echo "  https://github.com/midgardproject/midgard-php5/issues/50\n";
    gc_disable(); 
}

// Set up autoloader
require __DIR__ . '/../vendor/.composer/autoload.php';

// TODO: Remove once https://github.com/midgardproject/midgard-php5/issues/8 is fixed
if (getenv('MIDGARD_ENV_GLOBAL_SHAREDIR') != '/tmp/Midgard2CR/share')
{
    echo "\nBefore running these tests you need to run 'export MIDGARD_ENV_GLOBAL_SHAREDIR=/tmp/Midgard2CR/share'\n";
    exit(1);
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

require __DIR__ . '/suite/inc/FixtureLoaderInterface.php';
require __DIR__ . '/suite/inc/AbstractLoader.php';
require __DIR__ . '/ImplementationLoader.php';
