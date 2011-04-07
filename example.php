<?php
use Midgard2CR as PHPCR;

require 'SplClassLoader.php';
$midgard2crAutoloader = new SplClassLoader('Midgard2CR', 'src');
$midgard2crAutoloader->register();
$phpcrAutoloader = new SplClassLoader('PHPCR', 'lib/PHPCR/src');
$phpcrAutoloader->register();

$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$factory = new PHPCR\RepositoryFactory();
$repo = $factory->getRepository();
$session = $repo->login($credentials);
$root = $session->getRootNode();
$title = $root->getProperty('mgd:title');
var_dump($root->getIdentifier(), $root->getName());
var_dump($title->getName(), $title->getString(), $root->getPropertyValue('mgd:title'));
