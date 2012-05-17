Midgard2 PHP Content Repository provider [![Build Status](https://secure.travis-ci.org/midgardproject/phpcr-midgard2.png?branch=master)](http://travis-ci.org/midgardproject/phpcr-midgard2) 
========================================

This project implements a [Midgard2](http://midgard2.org/) -backed provider of the [PHP Content Repository](http://phpcr.github.com/) (PHPCR) interfaces. `phpcr-midgard2` is a fully [Jackalope](http://jackalope.github.com/) compatible PHPCR provider that can be used in PHP Content Management Systems without requiring Java.

Using Midgard2 instead of [Apache Jackrabbit](http://jackrabbit.apache.org/) also has the benefit of making interoperability with regular relational databases used by many CMSs easy. Midgard2 supports [multiple databases](http://www.gnome-db.org/Providers_status), including MySQL and SQLite.

## Installing

You need to have a [midgard2 PHP extension](https://github.com/midgardproject/midgard-php5) installed. On many distributions setting this up is as simple as:

    $ sudo apt-get install php5-midgard2

If your distribution doesn't come with Midgard2, then you can either compile it manually or use [our precompiled packages](http://download.opensuse.org/repositories/home:/midgardproject:/ratatoskr/).

Then set your project to depend on `midgard/phpcr` by having your `composer.json` to include:

    "require": {
        "midgard/phpcr": ">=1.0"
    }

Then just install the provider via [Composer](http://packagist.org/):

    $ wget http://getcomposer.org/composer.phar
    $ php composer.phar install

You also need to copy the Midgard2 PHPCR schemas from `vendor/midgard/phpcr/data/share` to your schema directory (by default `/usr/share/midgard2`):

    $ sudo cp vendor/midgard/phpcr/data/share/schema/* /usr/share/midgard2/schema/
    $ sudo cp vendor/midgard/phpcr/data/share/views/* /usr/share/midgard2/views/

## Getting started

You can use the Composer-generated autoloader to load all needed classes:

    require 'vendor/.composer/autoload.php';

After you've included the autoloader you should be able to open a Midgard2 repository session:

    // Set up Midgard2 connection
    $parameters = array(
        // Use local SQLite file for storage
        'midgard2.configuration.db.type' => 'SQLite',
        'midgard2.configuration.db.name' => 'midgard2cr',
        'midgard2.configuration.db.dir' => __DIR__,
        // Where you want to store file attachments
        'midgard2.configuration.blobdir' => '/var/lib/midgard2/blobs',
        // Let Midgard2 initialize the DB as needed
        'midgard2.configuration.db.init' => true,
    );

    // Get a Midgard repository
    $repository = Midgard\PHPCR\RepositoryFactory::getRepository($parameters);

    // Log in to get a session
    $credentials = new \PHPCR\SimpleCredentials('admin', 'password');
    $session = $repository->login($credentials, 'default');

After this the whole [PHPCR API](http://phpcr.github.com/doc/html/index.html) will be available. See some example code in the [examples` directory](https://github.com/bergie/phpcr-midgard2/tree/master/examples).

With MySQL, the connection parameters could for example be:

    $parameters = array(
        // MySQL connection settings. The database has to exist
        'midgard2.connection.db.type' => 'MySQL',
        'midgard2.connection.db.name' => 'midgard2',
        'midgard2.connection.db.username' => 'midgard',
        'midgard2.connection.db.password' => 'midgard',
        'midgard2.connection.db.host' => '127.0.0.1',
        'midgard2.connection.db.port' => '3306'
        // Let Midgard2 initialize the DB as needed
        'midgard2.configuration.db.init' => true,
    );

This is the only different part from the example using SQLite above.

## About PHPCR

The PHP Content Repository API is a PHP version of the Java Content Repository specification. [From Wikipedia](http://en.wikipedia.org/wiki/Content_repository_API_for_Java):

> Content Repository API for Java (JCR) is a specification for a Java platform application programming interface (API) to access content repositories in a uniform manner. The content repositories are used in content management systems to keep the content data and also the metadata used in content management systems (CMS) such as versioning metadata.

This way a content management system, for example, would not be tied to a particular database or other storage scheme. Instead, the content repository providers could be chosen based on deployment requirements.

There is currently [discussion about including](http://java.net/jira/browse/JSR_333-28) PHPCR APIs into the Java Content Repository specification.

## About Midgard2

Midgard2 is an open source content repository library available for multiple programming languages.

Midgard2 is able to access and manage content stored in various common relational databases, including SQLite, MySQL and Postgres. For this, you get a reasonably simple object-oriented interface. An example:

    $article = new net_example_article();
    $article->title = "Hello, world";
    $article->create();
    echo "Article {$article->title} was stored with GUID {$article->guid}";

## PHPCR and Midgard2

There have been [some studies](http://bergie.iki.fi/blog/what_is_a_content_repository/) into the conceptual differences and similarities between the Midgard2 Content Repository model and the [Java Content Repository](http://en.wikipedia.org/wiki/Content_repository_API_for_Java) model used in PHPCR. Because of these differences, some conceptual mappings are needed.

* Repository = Midgard config
* Session = Midgard connection
* Node = Midgard object
* Node Type = MgdSchema
* Property = Property or Parameter of Midgard object
* Workspace = Midgard workspace

### Making the Midgard tree single-rooted

The Midgard PHPCR tree is built out of `midgard_node` objects. These objects are only used for building the tree, and are connected to the real content objects in the tree by their `objectguid` property.

Properties that are not managed by MgdSchemas (so, properties not registered to Node Types) are handled by `midgard_node_property` objects.

### How does PHPCR map to your database?

Midgard2 uses standard relational databases for content storage. The PHPCR model is mapped to database tables in the following way:

* Tree structure is defined in `midgard_node` table, which contains `name`, `parent` reference, and a reference to a content object
* Properties of a node type are stored in the table defined for that node type in its MgdSchema ([see examples](https://github.com/midgardproject/phpcr-midgard2/blob/master/data/share/schema/phpcr_schemas.xml))
* Multivalued properties, and properties not defined in the node type (for example with `nt:unstructured`) are stored in the `midgard_node_property` table, which contains `name`, reference to `midgard_node` entry, `type` and `value`
* Binary property contents are stored as files into the `blobdir` defined in repository configuration

New Node Types can be registered by writing MgdSchemas for them and copying them to Midgard's schema directory (by default `/usr/share/midgard2/schema`).

### Workspaces

When Midgard2's own [Workspaces implementation](http://www.midgard-project.org/development/mrfc/0042/) lands in 10.05.5, we will support using [JCR Workspaces](http://www.day.com/maven/jsr170/javadocs/jcr-1.0/javax/jcr/Workspace.html) as well. The workspace strings will be in format:

* `workspace`
* `workspace/subworkspace`

### Namespace mappings

The PHPCR API uses namespaces for node types and property names. The regular [Midgard2 MgdSchema RDF mappings](https://github.com/midgardproject/proposals/blob/master/Semantic%20Data/MgdSchemaRDF.md) should be used for this.

## Projects using PHPCR

* [Symfony CMF](http://pooteeweet.org/blog/0/1912#m1912)
* [Doctrine ODM](https://github.com/doctrine/phpcr-odm)
* Flow3/TYPO3

## Licensing

Content Repositories are important piece of software infrastructure that must be usable by any projects or companies regardless of their business model. Because of this, the Midgard2 PHPCR implementation will be available under permissive terms of the [GNU Lesser General Public License](http://www.gnu.org/licenses/lgpl-2.1.html).

## Development

Contributions to the Midgard2 PHPCR provider are very much appreciated. The development is coordinated on a GitHub repository:

* [github.com/midgardproject/phpcr-midgard2](https://github.com/midgardproject/phpcr-midgard2)

Feel free to watch the repository, make a fork and [submit pull requests](http://help.github.com/pull-requests/). Code reviews, testing and [bug reports](https://github.com/midgardproject/phpcr-midgard2/issues) are also very welcome.

### Continuous Integration

The Midgard2 PHPCR provider is using [Travis](http://travis-ci.org) for Continuous Integration.

If you have a fork of this repository and want it tested, enable it on the Travis website. Each push will be automatically tested.
