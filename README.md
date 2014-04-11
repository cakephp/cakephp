# CakePHP framework

[![CakePHP](http://cakephp.org/img/cake-logo.png)](http://www.cakephp.org)

CakePHP is a rapid development framework for PHP which uses commonly known
design patterns like Active Record, Association Data Mapping, Front Controller
and MVC.  Our primary goal is to provide a structured framework that enables
PHP users at all levels to rapidly develop robust web applications, without any
loss to flexibility.

## Installing CakePHP via composer

You can install CakePHP into your project using
[composer](http://getcomposer.org).  If you're starting a new project, we
recommend using the [app skeleton](https://github.com/cakephp/app) as
a starting point. For existing applications you can add the following to your
`composer.json` file:

	"require": {
		"cakephp/cakephp": "3.0.*-dev"
	}

And run `php composer.phar update`

## Running tests

Assuming you have PHPUnit installed system wide using one of the methods stated
[here](http://phpunit.de/manual/current/en/installation.html), you can run the
tests for cakephp by doing the following:

1. Copy `phpunit.xml.dist` to `phpunit.xml`
2. Add the relevant database credentials to your phpunit.xml if you want to run tests against
   a non-SQLite datasource.
3. Run `phpunit --stderr`

## Contributing

See CONTRIBUTING.md for more information.

Some Handy Links
----------------

[CakePHP](http://www.cakephp.org) - The rapid development PHP framework

[CookBook](http://book.cakephp.org) - THE CakePHP user documentation; start learning here!

[API](http://api.cakephp.org) - A reference to CakePHP's classes

[Plugins](http://plugins.cakephp.org) - A repository of extensions to the framework

[The Bakery](http://bakery.cakephp.org) - Tips, tutorials and articles

[Community Center](http://community.cakephp.org) - A source for everything community related

[Training](http://training.cakephp.org) - Join a live session and get skilled with the framework

[CakeFest](http://cakefest.org) - Don't miss our annual CakePHP conference

[Cake Software Foundation](http://cakefoundation.org) - Promoting development related to CakePHP

Get Support!
------------

[#cakephp](http://webchat.freenode.net/?channels=#cakephp) on irc.freenode.net - Come chat with us, we have cake

[Google Group](https://groups.google.com/group/cake-php) - Community mailing list and forum

[GitHub Issues](https://github.com/cakephp/cakephp/issues) - Got issues? Please tell us!

[Roadmaps](https://github.com/cakephp/cakephp/wiki#roadmaps) - Want to contribute? Get involved!

[![Bake Status](https://secure.travis-ci.org/cakephp/cakephp.png?branch=3.0)](http://travis-ci.org/cakephp/cakephp)

![Cake Power](https://raw.github.com/cakephp/cakephp/master/lib/Cake/Console/Templates/skel/webroot/img/cake.power.gif)
