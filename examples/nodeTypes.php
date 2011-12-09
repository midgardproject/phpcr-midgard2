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
        $superTypeNames = array();
        foreach ($superTypes as $superType) {
            $superTypeNames[] = $superType->getName();
        }
        echo implode(', ', $superTypeNames);
    }
    echo "\n";

    $additionalInfo = array();
    if ($nodeType->isAbstract()) {
        $additionalInfo[] = 'abstract';
    }
    if ($nodeType->isMixin()) {
        $additionalInfo[] = 'mixin';
    }
    if ($nodeType->hasOrderableChildNodes()) {
        $additionalInfo[] = 'orderable';
    }
    if ($nodeType->getPrimaryItemName()) {
        $additionalInfo[] = 'primaryitem ' . $nodeType->getPrimaryItemName();
    }
    if ($additionalInfo) {
        echo "  " . implode(' ', $additionalInfo) . "\n";
    }

    foreach ($nodeType->getDeclaredChildNodeDefinitions() as $child) {
        echo "  + " . $child->getName();
        $childInfo = array();
        if ($child->getRequiredPrimaryTypeNames() !== null) {
            $typeNames = $child->getRequiredPrimaryTypeNames();
            $childInfo[] = '(' . $typeNames[0] . ')';
        }

        if ($child->getDefaultPrimaryTypeName()) {
            $childInfo[] = '= ' . $child->getDefaultPrimaryTypeName();
        }

        if ($child->isMandatory()) {
            $childInfo[] = 'mandatory';
        }

        if ($child->isAutoCreated()) {
            $childInfo[] = 'autocreated';
        }

        if ($child->isProtected()) {
            $childInfo[] = 'protected';
        }

        echo " " . implode(' ', $childInfo) . "\n";
    }

    foreach ($nodeType->getDeclaredPropertyDefinitions() as $property) {
        echo "  - " . $property->getName();
        $propertyInfo = array();
        if ($property->getRequiredType() !== null) {
            $typeName = PHPCR\PropertyType::nameFromValue($property->getRequiredType());
            $propertyInfo[] = '(' . strtoupper($typeName) . ')';
        }

        if ($property->isMandatory()) {
            $propertyInfo[] = 'mandatory';
        }

        if ($property->isAutoCreated()) {
            $propertyInfo[] = 'autocreated';
        }

        if ($property->isProtected()) {
            $propertyInfo[] = 'protected';
        }

        if ($property->isMultiple()) {
            $propertyInfo[] = 'multiple';
        }

        echo " " . implode(' ', $propertyInfo) . "\n";
    }

    echo "\n";
}
