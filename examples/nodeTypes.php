<?php
/**
 * This example demonstrates how to run JCR SQL2 queries
 *
 * In order to run this, you'll need the 'midgard2' PHP extension
 * installed. No other setup should be needed.
 */

// Set up autoloader
require __DIR__ . '/../vendor/.composer/autoload.php';

// Set up Midgard2 repository configs
$parameters = array(
    // Use local SQLite file for storage
    'midgard2.configuration.db.type' => 'SQLite',
    'midgard2.configuration.db.name' => 'midgard2cr',
    'midgard2.configuration.db.dir' => __DIR__,
    // Let Midgard2 initialize the DB as needed
    'midgard2.configuration.db.init' => true,
    // Enable this if you want to see the actual database queries
    //'midgard2.configuration.loglevel' => 'debug',
);

// Get a Midgard repository
$repository = Midgard\PHPCR\RepositoryFactory::getRepository($parameters);

// Log in
$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$session = $repository->login($credentials, 'default');

$mgr = $session->getWorkspace()->getNodeTypeManager();
$nodeTypes = $mgr->getAllNodeTypes();
foreach ($nodeTypes as $nodeType) {
    if (substr($nodeType->getName(), 0, 4) == 'mgd:') {
        continue;
    }
    echo "[{$nodeType->getName()}]";
    
    $superTypes = $nodeType->getDeclaredSuperTypes();
    if ($superTypes) {
        echo " > ";
        foreach ($superTypes as $superType) {
            echo $superType->getName();
        }
    }
    echo "\n";

    $additionalInfo = array();
    if ($nodeType->isMixin()) {
        $additionalInfo[] = 'MIXIN';
    }
    if ($nodeType->hasOrderableChildNodes()) {
        $additionalInfo[] = 'ORDERABLE';
    }
    if ($nodeType->getPrimaryItemName()) {
        $additionalInfo[] = 'PRIMARYITEM ' . strtoupper($nodeType->getPrimaryItemName());
    }
    if ($additionalInfo) {
        echo "  " . implode(' ', $additionalInfo) . "\n";
    }

    echo "\n";
}
