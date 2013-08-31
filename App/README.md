# CakePHP Application Skeleton

A skeleton for creating applications with [CakePHP](http://cakephp.org).

## Installation

You can install this application skeleton using composer. You'll need to install
[composer](http://getcomposer.org/doc/00-intro.md) first. After installing `composer`
you can install this project & the required dependencies using:

	php composer.phar create-project cakephp/cakephp-app --dev

This will download this repository, install the CakePHP framework and testing libraries.

## Configuration

Once you've installed the dependencies copy the `Config/app.php.default` to `Config/app.php`.
You should edit this file and setup the 'Datasources' array to point at your database.

After creating `Config/app.php` you should go to the `/` route and ensure all the boxes are green.
