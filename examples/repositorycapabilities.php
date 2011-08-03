<?php
/**
 * This example demonstrates how to introspect various capabilities
 * of a PHPCR repository.
 *
 * Capability introspection is useful, as with it your application
 * can tune itself to work with different PHPCR providers that may
 * not provide the full feature set of the PHPCR spec.
 */

require __DIR__ . "/includes.php";

// Set up Midgard2 repository configs
$parameters = array(
    'midgard2.configuration.db.type' => 'SQLite',
    'midgard2.configuration.db.name' => 'midgard2cr',
    'midgard2.configuration.db.dir' => __DIR__,
);

// Get a Midgard repository
$repository = Midgard2CR\RepositoryFactory::getRepository($parameters);

// Show basic information about the repository
echo "Opened new PHPCR repository\n\n";
echo sprintf("Provider: %s %s\n", $repository->getDescriptor('jcr.repository.name'), $repository->getDescriptor('jcr.repository.version'));
echo "Vendor: " . $repository->getDescriptor('jcr.repository.vendor') . "\n";
echo "\n";

// Introspect some interesting capabilities
if ($repository->getDescriptor('level.2.supported')) {
    echo "Repository supports PHPCR level 2\n";
} else {
    echo "Repository supports PHPCR level 1\n";
}

if ($repository->getDescriptor('option.access.control.supported')) {
    echo "Repository supports Access Controls\n";
}

if ($repository->getDescriptor('option.versioning.supported')) {
    echo "Repository supports versioning\n";
}

if ($repository->getDescriptor('option.workspace.management.supported')) {
    echo "Repository supports workspace management\n";
}
