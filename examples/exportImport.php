<?php
/**
 * This example demonstrates how to export and import PHPCR
 * nodes.
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
    'midgard2.configuration.blobdir' => __DIR__ . '/blobs',
    // Let Midgard2 initialize the DB as needed
    'midgard2.configuration.db.init' => true,
    // Enable this if you want to see the actual database queries
    //'midgard2.configuration.loglevel' => 'debug',
);

// Get a Midgard repository
$repository = Midgard\PHPCR\RepositoryFactory::getRepository($parameters);

// Log in
$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$session = $repository->login($credentials);

// Add node unless it already exists
if (!$session->nodeExists('/test')) {
    $root = $session->getRootNode();
    $node = $root->addNode('test', 'nt:unstructured');
    $node->setProperty('prop', 'value');
    $session->save();
}

$node = $session->getNode('/test');
$node->addMixin('mix:referenceable');
$session->save();

// Export the node via XML
$session->exportDocumentView('/test', fopen('/tmp/exportedNode.xml', 'w'), true, true);

if (!$repository->getDescriptor(\PHPCR\RepositoryInterface::OPTION_XML_IMPORT_SUPPORTED)) {
    echo "XML import not supported by repository\n";
    echo "Exported XML was:\n\n";
    echo file_get_contents('/tmp/exportedNode.xml');
    die();
}

// Import the node into a new path
$session->importXML('/test', fopen('/tmp/exportedNode.xml', 'r'),
  \PHPCR\ImportUUIDBehaviourInterface::IMPORT_UUID_CREATE_NEW);
