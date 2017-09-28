<p align="center">
  <a href="https://cakephp.org/" target="_blank" >
    <img alt="CakePHP" src="https://cakephp.org/v2/img/logos/CakePHP_Logo.svg" width="400" />
  </a>
</p>
<p align="center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://travis-ci.org/cakephp/cakephp" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/travis/cakephp/cakephp/master.svg?style=flat-square">
    </a>
    <a href="https://codecov.io/github/cakephp/cakephp" target="_blank">
        <img alt="Coverage Status" src="https://img.shields.io/codecov/c/github/cakephp/cakephp.svg?style=flat-square">
    </a>
    <a href="https://squizlabs.github.io/PHP_CodeSniffer/analysis/cakephp/cakephp/" target="_blank">
        <img alt="Code Consistency" src="https://squizlabs.github.io/PHP_CodeSniffer/analysis/cakephp/cakephp/grade.svg">
    </a>
    <a href="https://packagist.org/packages/cakephp/cakephp" target="_blank">
        <img alt="Total Downloads" src="https://img.shields.io/packagist/dt/cakephp/cakephp.svg?style=flat-square">
    </a>
    <a href="https://packagist.org/packages/cakephp/cakephp" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/cakephp/cakephp.svg?style=flat-square&label=stable">
    </a>
</p>

[CakePHP](https://cakephp.org) is a rapid development framework for PHP which
uses commonly known design patterns like Associative Data
Mapping, Front Controller, and MVC.  Our primary goal is to provide a structured
framework that enables PHP users at all levels to rapidly develop robust web
applications, without any loss to flexibility.

## Installing CakePHP via Composer

You can install CakePHP into your project using
[Composer](https://getcomposer.org).  If you're starting a new project, we
recommend using the [app skeleton](https://github.com/cakephp/app) as
a starting point. For existing applications you can run the following:

``` bash
$ composer require cakephp/cakephp:"~3.5"
```

## Running Tests

Assuming you have PHPUnit installed system wide using one of the methods stated
[here](https://phpunit.de/manual/current/en/installation.html), you can run the
tests for CakePHP by doing the following:

1. Copy `phpunit.xml.dist` to `phpunit.xml`.
2. Add the relevant database credentials to your `phpunit.xml` if you want to run tests against
   a non-SQLite datasource.
3. Run `phpunit`.

## Some Handy Links

* [CakePHP](https://cakephp.org) - The rapid development PHP framework.
* [CookBook](https://book.cakephp.org) - The CakePHP user documentation; start learning here!
* [API](https://api.cakephp.org) - A reference to CakePHP's classes.
* [Awesome CakePHP](https://github.com/FriendsOfCake/awesome-cakephp) - A list of featured resources around the framework.
* [Plugins](https://plugins.cakephp.org) - A repository of extensions to the framework.
* [The Bakery](https://bakery.cakephp.org) - Tips, tutorials and articles.
* [Community Center](https://community.cakephp.org) - A source for everything community related.
* [Training](https://training.cakephp.org) - Join a live session and get skilled with the framework.
* [CakeFest](https://cakefest.org) - Don't miss our annual CakePHP conference.
* [Cake Software Foundation](https://cakefoundation.org) - Promoting development related to CakePHP.

## Get Support!

* [Slack](https://cakesf.herokuapp.com/) - Join us on Slack.
* [#cakephp](https://webchat.freenode.net/?channels=#cakephp) on irc.freenode.net - Come chat with us, we have cake.
* [Forum](http://discourse.cakephp.org/) - Official CakePHP forum.
* [GitHub Issues](https://github.com/cakephp/cakephp/issues) - Got issues? Please tell us!
* [Roadmaps](https://github.com/cakephp/cakephp/wiki#roadmaps) - Want to contribute? Get involved!

## Contributing

* [CONTRIBUTING.md](.github/CONTRIBUTING.md) - Quick pointers for contributing to the CakePHP project.
* [CookBook "Contributing" Section](https://book.cakephp.org/3.0/en/contributing.html) - Details about contributing to the project.

# Security

If you’ve found a security issue in CakePHP, please use the following procedure instead of the normal bug reporting system. Instead of using the bug tracker, mailing list or IRC please send an email to security [at] cakephp.org. Emails sent to this address go to the CakePHP core team on a private mailing list.

For each report, we try to first confirm the vulnerability. Once confirmed, the CakePHP team will take the following actions:

- Acknowledge to the reporter that we’ve received the issue, and are working on a fix. We ask that the reporter keep the issue confidential until we announce it.
- Get a fix/patch prepared.
- Prepare a post describing the vulnerability, and the possible exploits.
- Release new versions of all affected versions.
- Prominently feature the problem in the release announcement.
