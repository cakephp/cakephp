[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/core.svg?style=flat-square)](https://packagist.org/packages/cakephp/core)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Core Classes

A set of classes used for configuration files reading and storing.
This repository contains the classes that are used as glue for creating the CakePHP framework.

## Usage

You can use the `Configure` class to store arbitrary configuration data:

```php
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

Configure::write('Company.name','Pizza, Inc.');
Configure::read('Company.name'); // Returns: 'Pizza, Inc.'
```

It also possible to load configuration from external files:

```php
Configure::config('default', new PhpConfig('/path/to/config/folder'));
Configure::load('app', 'default', false);
Configure::load('other_config', 'default');
```

And write the configuration back into files:

```php
Configure::dump('my_config', 'default');
```

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/4/en/development/configuration.html)
