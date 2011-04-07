<?php
use Midgard2CR as CR;

require 'SplClassLoader.php';
$midgard2crAutoloader = new SplClassLoader('Midgard2CR', 'src');
$midgard2crAutoloader->register();
$phpcrAutoloader = new SplClassLoader('PHPCR', 'lib/PHPCR/src');
$phpcrAutoloader->register();

$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$factory = new CR\RepositoryFactory();
$repo = $factory->getRepository();
$session = $repo->login($credentials);
$root = $session->getRootNode();
$title = $root->getProperty('mgd:title');
var_dump($root->getIdentifier(), $root->getName());
var_dump($title->getName(), $title->getString(), $root->getPropertyValue('mgd:title'));

$child = $root->getNode('planet');
var_dump($child->getPropertyValue('mgd:title'));

$another = $session->getNode('/development');
var_dump($another);
