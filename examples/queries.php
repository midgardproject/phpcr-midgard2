<?php
/**
 * This example demonstrates how to add and access nodes in a
 * PHPCR tree.
 *
 * The basic concept comes from the PHPCR tutorial:
 * https://github.com/phpcr/phpcr/blob/master/doc/Tutorial.md 
 *
 * In order to run this, you'll need the 'midgard2' PHP extension
 * installed. No other setup should be needed.
 */

// Set up autoloader
require __DIR__ . "/includes.php";

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
$repository = Midgard2CR\RepositoryFactory::getRepository($parameters);

// Log in
$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$session = $repository->login($credentials, 'default');

// Add node unless it already exists
if (!$session->nodeExists('/example')) {
    $node = $session->getRootNode()->addNode('example', 'nt:unstructured');
    $node->setProperty('property', 'value');
    $session->save();
}

// Get the Query Manager
$qm = $session->getWorkspace()->getQueryManager();

// Prepare a SQL2 query
$query = $qm->createQuery('SELECT * FROM [nt:unstructured] 
                           WHERE property IS NOT NULL
                           ORDER BY property ASC', 
                          \PHPCR\Query\QueryInterface::JCR_SQL2);

// Execute
$results = $query->execute();

// Loop through results
$nodes = $results->getNodes();
foreach ($nodes as $node) {
  var_dump($node->getPath());
}
