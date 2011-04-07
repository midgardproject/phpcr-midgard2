Midgard2 PHP Content Repository provider
========================================

This project attempts to implement a [Midgard2](http://midgard2.org/) -backed implementation of the PHP Content Repository (PHPCR) interfaces. The idea is to have a fully [Jackalope](http://jackalope.github.com/) -compatible PHPCR provider that can be used in PHP Content Management Systems without requiring Java.

Using Midgard2 instead of Apache Jackrabbit also has the benefit of making interoperability with regular relational databases used by many CMSs easy.

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

While both Midgard2 and JCR build on the tree concept, the tree in Midgard is multi-rooted.

## Development

The Midgard2 PHPCR provider is in early stages of development. Our initial goal is to implement JCR level 1 compatibility, verified by passing the relevant [Jackalope API tests](https://github.com/jackalope/jackalope-api-tests). Once we know the exact approach to take, adding JCR level 2 should be relatively straightforward.

Contributions to the Midgard2 PHPCR provider are very much appreciated. The development is coordinated on a GitHub repository:

* [github.com/bergie/phpcr-midgard2](https://github.com/bergie/phpcr-midgard2)

Feel free to watch the repository, make a fork and [submit pull requests](http://help.github.com/pull-requests/). Code reviews, testing and [bug reports](https://github.com/bergie/phpcr-midgard2/issues) are also very welcome.
