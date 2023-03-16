# How to contribute

CakePHP loves to welcome your contributions. There are several ways to help out:

* Create an [issue](https://github.com/cakephp/cakephp/issues) on GitHub, if you have found a bug
* Write test cases for open bug issues
* Write patches for open bug/feature issues, preferably with test cases included
* Contribute to the [documentation](https://github.com/cakephp/docs)

There are a few guidelines that we need contributors to follow so that we have a
chance of keeping on top of things.

## Code of Conduct

Help us keep CakePHP open and inclusive. Please read and follow our [Code of Conduct](https://github.com/cakephp/code-of-conduct/blob/master/CODE_OF_CONDUCT.md).

## Getting Started

* Make sure you have a [GitHub account](https://github.com/signup/free).
* Submit an [issue](https://github.com/cakephp/cakephp/issues), assuming one does not already exist.
  * Clearly describe the issue including steps to reproduce when it is a bug.
  * Make sure you fill in the earliest version that you know has the issue.
* Fork the repository on GitHub.

## Making Changes

* Create a topic branch from where you want to base your work.
  * This is usually the current default branch - `5.x` right now.
  * To quickly create a topic branch based on `5.x`
    `git branch 5.x/my_contribution 5.x` then checkout the new branch with `git
    checkout 5.x/my_contribution`. Better avoid working directly on the
    `5.x` branch, to avoid conflicts if you pull in updates from origin.
* Make commits of logical units.
* Check for unnecessary whitespace with `git diff --check` before committing.
* Use descriptive commit messages and reference the #issue number.
* [Core test cases, static analysis and codesniffer](#test-cases-codesniffer-and-static-analysis) should continue to pass.
* Your work should apply the [CakePHP coding standards](https://book.cakephp.org/4/en/contributing/cakephp-coding-conventions.html).

## Which branch to base the work

* Bugfix branches will be based on the current default branch - `5.x` right now.
* New features that are **backwards compatible** will be based on the appropriate `next` branch. For example if you want to contribute to the next 5.x branch, you should base your changes on `5.next`.
* New features or other **non backwards compatible** changes will go in the next major release branch.

## What is "backwards compatible" (BC)

`BC breaking` code changes mean, that a given PR introduces code changes which can't be performed by everyone without the need to manually adjust code.

Here are some rules which **prevent** `BC breaking` code changes:

* Configuration doesn't need to change
* Public API doesn't change. For example, any user land code using/overriding public methods shouldn't break.

Also see our current [Release Policy](https://book.cakephp.org/4/en/release-policy.html)

## Submitting Changes

* Push your changes to a topic branch in your fork of the repository.
* Submit a pull request to the repository in the CakePHP organization, with the
  correct target branch.

## Test cases, codesniffer and static analysis

To run the test cases locally use the following command:

    composer test

You can copy file `phpunit.xml.dist` to `phpunit.xml` and modify the database
driver settings as required to run tests for a particular database.

To run the sniffs for CakePHP coding standards:

    composer cs-check

Check the [cakephp-codesniffer](https://github.com/cakephp/cakephp-codesniffer)
repository to set up the CakePHP standard. The [README](https://github.com/cakephp/cakephp-codesniffer/blob/master/README.md) contains installation info
for the sniff and phpcs.

To run static analysis tools [PHPStan](https://github.com/phpstan/phpstan) and [Psalm](https://github.com/vimeo/psalm) you first have to install the additional packages via [phive](https://phar.io).

    composer stan-setup

The currently used PHPStan and Psalm versions can be found in `.phive/phars.xml`.

After that you can perform the checks via:

    composer stan

Note that updating the baselines need to be done with the same PHP version it is run online.
That is usually the minimum version.
Make sure to "composer install" and set up the stan tools with it and then also execute them.

## Reporting a Security Issue

If you've found a security related issue in CakePHP, please don't open an issue in github. Instead, contact us at security@cakephp.org. For more information on how we handle security issues, [see the CakePHP Security Issue Process](https://book.cakephp.org/4/en/contributing/tickets.html#reporting-security-issues).

# Additional Resources

* [CakePHP coding standards](https://book.cakephp.org/4/en/contributing/cakephp-coding-conventions.html)
* [Existing issues](https://github.com/cakephp/cakephp/issues)
* [Development Roadmaps](https://github.com/cakephp/cakephp/wiki#roadmaps)
* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/articles/creating-a-pull-request/)
* [Forum](https://discourse.cakephp.org/)
* [Stackoverflow](https://stackoverflow.com/tags/cakephp)
* [IRC channel #cakephp](https://kiwiirc.com/client/irc.freenode.net#cakephp)
* [Slack](https://slack-invite.cakephp.org/)
* [Discord](https://discord.gg/k4trEMPebj)
