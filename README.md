<p align="center"><a href="https://cakephp.org" target="_blank"><img src="https://cakephp.org/img/trademarks/logo-1.jpg"></a></p>
<p align="center">
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/cakephp/cakephp/master.svg?style=flat-square)](https://travis-ci.org/cakephp/cakephp)
[![Coverage Status](https://img.shields.io/codecov/c/github/cakephp/cakephp.svg?style=flat-square)](https://codecov.io/github/cakephp/cakephp)
[![Code Consistency](https://squizlabs.github.io/PHP_CodeSniffer/analysis/cakephp/cakephp/grade.svg)](http://squizlabs.github.io/PHP_CodeSniffer/analysis/cakephp/cakephp/)
[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/cakephp.svg?style=flat-square)](https://packagist.org/packages/cakephp/cakephp)
[![Latest Stable Version](https://img.shields.io/packagist/v/cakephp/cakephp.svg?style=flat-square&label=stable)](https://packagist.org/packages/cakephp/cakephp)
<p>

[CakePHP](http://www.cakephp.org) is a rapid development framework for PHP which
uses commonly known design patterns like Associative Data
Mapping, Front Controller, and MVC.  Our primary goal is to provide a structured
framework that enables PHP users at all levels to rapidly develop robust web
applications, without any loss to flexibility.

## Installing CakePHP via Composer

You can install CakePHP into your project using
[Composer](http://getcomposer.org).  If you're starting a new project, we
recommend using the [app skeleton](https://github.com/cakephp/app) as
a starting point. For existing applications you can run the following:

``` bash
$ composer require cakephp/cakephp:"~3.3"
```

## Running Tests

Assuming you have PHPUnit installed system wide using one of the methods stated
[here](http://phpunit.de/manual/current/en/installation.html), you can run the
tests for CakePHP by doing the following:

1. Copy `phpunit.xml.dist` to `phpunit.xml`.
2. Add the relevant database credentials to your `phpunit.xml` if you want to run tests against
   a non-SQLite datasource.
3. Run `phpunit`.

## Some Handy Links

* [CakePHP](http://www.cakephp.org) - The rapid development PHP framework.
* [CookBook](http://book.cakephp.org) - The CakePHP user documentation; start learning here!
* [API](http://api.cakephp.org) - A reference to CakePHP's classes.
* [Awesome CakePHP](https://github.com/FriendsOfCake/awesome-cakephp) - A list of featured resources around the framework.
* [Plugins](http://plugins.cakephp.org) - A repository of extensions to the framework.
* [The Bakery](http://bakery.cakephp.org) - Tips, tutorials and articles.
* [Community Center](http://community.cakephp.org) - A source for everything community related.
* [Training](http://training.cakephp.org) - Join a live session and get skilled with the framework.
* [CakeFest](http://cakefest.org) - Don't miss our annual CakePHP conference.
* [Cake Software Foundation](http://cakefoundation.org) - Promoting development related to CakePHP.

## Get Support!

* [Slack](http://cakesf.herokuapp.com/) - Join us on Slack.
* [#cakephp](http://webchat.freenode.net/?channels=#cakephp) on irc.freenode.net - Come chat with us, we have cake.
* [Forum](http://discourse.cakephp.org/) - Offical CakePHP forum.
* [GitHub Issues](https://github.com/cakephp/cakephp/issues) - Got issues? Please tell us!
* [Roadmaps](https://github.com/cakephp/cakephp/wiki#roadmaps) - Want to contribute? Get involved!

## Contributing

* [CONTRIBUTING.md](.github/CONTRIBUTING.md) - Quick pointers for contributing to the CakePHP project.
* [CookBook "Contributing" Section](http://book.cakephp.org/3.0/en/contributing.html) - Details about contributing to the project.

# Security

If you’ve found a security issue in CakePHP, please use the following procedure instead of the normal bug reporting system. Instead of using the bug tracker, mailing list or IRC please send an email to security [at] cakephp.org. Emails sent to this address go to the CakePHP core team on a private mailing list.

For each report, we try to first confirm the vulnerability. Once confirmed, the CakePHP team will take the following actions:

- Acknowledge to the reporter that we’ve received the issue, and are working on a fix. We ask that the reporter keep the issue confidential until we announce it.
- Get a fix/patch prepared.
- Prepare a post describing the vulnerability, and the possible exploits.
- Release new versions of all affected versions.
- Prominently feature the problem in the release announcement.
