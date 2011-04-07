<?php
use Midgard2CR as CR;

// Register the autoloaders
require 'SplClassLoader.php';

// Midgard2CR is in the src dir
$midgard2crAutoloader = new SplClassLoader('Midgard2CR', 'src');
$midgard2crAutoloader->register();
// PHPCR is in a submodule in lib/PHPCR
$phpcrAutoloader = new SplClassLoader('PHPCR', 'lib/PHPCR/src');
$phpcrAutoloader->register();

// Set up credentials, in this case the default account
$credentials = new \PHPCR\SimpleCredentials('admin', 'password');

// Get a Midgard configuration
$factory = new CR\RepositoryFactory();
$repo = $factory->getRepository();

// Connect to Midgard repository with the credentials
$session = $repo->login($credentials);

// Get the root node matching our workspace
$root = $session->getRootNode();
$title = $root->getProperty('mgd:title');
var_dump($root->getIdentifier(), $root->getName());
var_dump($title->getName(), $title->getString(), $root->getPropertyValue('mgd:title'));

// Get a child node named "planet"
$child = $root->getNode('planet');
var_dump($child->getPropertyValue('mgd:title'));


// Get a node with absolute path
$another = $session->getNode('/development');
var_dump($another->getPropertyValue('mgd:title'));

// Get a property with absolute path
var_dump($session->getProperty('/planet/mgd:component')->getNativeValue());
