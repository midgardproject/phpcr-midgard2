<?php
use Midgard2CR as PHPCR;

function exampleAutoload($class)
{
    $namespaces = explode('\\', $class);
    if (count($namespaces) != 2)
    {
        return false;
    }
    
    if ($namespaces[0] == 'PHPCR')
    {
        require "lib/PHPCR/src/PHPCR/{$namespaces[1]}.php";
        return;
    }
    require "src/{$namespaces[1]}.php";
}
spl_autoload_register('exampleAutoload');

$credentials = new \PHPCR\SimpleCredentials('admin', 'password');
$factory = new PHPCR\RepositoryFactory();
$repo = $factory->getRepository();
$session = $repo->login($credentials);
$root = $session->getRootNode();
$title = $root->getProperty('mgd:title');
var_dump($title->getString());
var_dump($root->getIdentifier());
