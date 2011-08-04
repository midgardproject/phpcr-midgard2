<?php

$crRoot = realpath(__DIR__ . '/..');

// register the autoloaders
require "{$crRoot}/SplClassLoader.php";

// Midgard2CR is in the src dir
$midgard2crAutoloader = new SplClassLoader('Midgard2CR', "{$crRoot}/src");
$midgard2crAutoloader->register();
// PHPCR is in a submodule in lib/PHPCR
$phpcrAutoloader = new SplClassLoader('PHPCR', "{$crRoot}/lib/PHPCR/src");
$phpcrAutoloader->register();

?>
