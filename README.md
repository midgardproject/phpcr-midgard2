Midgard2 PHP Content Repository provider
========================================

This project attempts to implement a [Midgard2](http://midgard2.org/) -backed implementation of the PHP Content Repository (PHPCR) interfaces. The plan is to have a fully [Jackalope](http://jackalope.github.com/) compatible PHPCR provider that can be used in PHP Content Management Systems without requiring Java.

Using Midgard2 instead of [Apache Jackrabbit](http://jackrabbit.apache.org/) also has the benefit of making interoperability with regular relational databases used by many CMSs easy.

## About PHPCR

The PHP Content Repository API is a PHP version of the Java Content Repository specification. [From Wikipedia](http://en.wikipedia.org/wiki/Content_repository_API_for_Java):

> Content Repository API for Java (JCR) is a specification for a Java platform application programming interface (API) to access content repositories in a uniform manner. The content repositories are used in content management systems to keep the content data and also the metadata used in content management systems (CMS) such as versioning metadata.

This way a content management system, for example, would not be tied to a particular database or other storage scheme. Instead, the content repository providers could be chosen based on deployment requirements.

There is currently [discussion about including](http://java.net/jira/browse/JSR_333-28) PHPCR APIs into the Java Content Repository specification.

## About Midgard2

Midgard2 is an open source content repository library available for multiple programming languages. For PHP it is available as [an extension](https://github.com/midgardproject/midgard-php5). On many distributions setting this up is as simple as:

    $ sudo apt-get install php5-midgard2

The Midgard2 content repository is able to access and manage content stored in various common relational databases, including SQLite, MySQL and Postgres. For this, you get a reasonably simple object-oriented interface. An example:

    $article = new net_example_article();
    $article->title = "Hello, world";
    $article->create();
    echo "Article {$article->title} was stored with GUID {$article->guid}";

## PHPCR and Midgard2

There have been [some studies](http://bergie.iki.fi/blog/what_is_a_content_repository/) into the conceptual differences and similarities between the Midgard2 Content Repository model and the [Java Content Repository](http://en.wikipedia.org/wiki/Content_repository_API_for_Java) model used in PHPCR. Because of these differences, some conceptual mappings will be needed.

* Repository = Midgard config
* Session = Midgard connection
* Node = Midgard object
* Property = Property or Parameter of Midgard object
* Workspace = Midgard root node or workspace

### Making the Midgard tree single-rooted

While both Midgard2 and JCR build on the tree concept, the tree in Midgard is multi-rooted. We work around this by making each rootlevel object its own repository.

When user connects to a Midgard2 PHPCR repository, the connection will use configuration to map itself to a particular rootlevel object. A new PHPCR Session will be returned.

### Workspaces

When Midgard2's own [Workspaces implementation](http://www.midgard-project.org/development/mrfc/0042/) lands in 10.05.5, we will support using [JCR Workspaces](http://www.day.com/maven/jsr170/javadocs/jcr-1.0/javax/jcr/Workspace.html) as well. The workspace strings will be in format:

* `workspace`
* `workspace/subworkspace`

### Namespace mappings

The PHPCR API uses namespaces for node types and property names. The regular [Midgard2 MgdSchema RDF mappings](https://github.com/midgardproject/proposals/blob/master/Semantic%20Data/MgdSchemaRDF.md) should be used for this.

Midgard's MgdSchema types (PHPCR Node types) have a fixed set of properties. To implement the full PHPCR model, additional properties should be implemented using Midgard Parameters.

Basically setting value of Node property `foo:bar`, can mean depending on MgdSchema, either:

    $node->bar = $value;

or:

    $node->set_parameter('foo', 'bar', $value);

## Projects using PHPCR

* [Symfony CMF](http://pooteeweet.org/blog/0/1912#m1912)
* Flow3/TYPO3

## Licensing

Content Repositories are important piece of software infrastructure that must be usable by any projects or companies regardless of their business model. Because of this, the Midgard2 PHPCR implementation will be available under permissive terms of the [GNU Lesser General Public License](http://www.gnu.org/licenses/lgpl-2.1.html).

## Development

The Midgard2 PHPCR provider is in early stages of development. Our initial goal is to implement JCR level 1 compatibility, verified by passing the relevant [Jackalope API tests](https://github.com/jackalope/jackalope-api-tests). Once we know the exact approach to take, adding JCR level 2 should be relatively straightforward. The [David's Model notes](http://wiki.apache.org/jackrabbit/DavidsModel) should be useful for prioritizing features.

Contributions to the Midgard2 PHPCR provider are very much appreciated. The development is coordinated on a GitHub repository:

* [github.com/bergie/phpcr-midgard2](https://github.com/bergie/phpcr-midgard2)

Feel free to watch the repository, make a fork and [submit pull requests](http://help.github.com/pull-requests/). Code reviews, testing and [bug reports](https://github.com/bergie/phpcr-midgard2/issues) are also very welcome.
