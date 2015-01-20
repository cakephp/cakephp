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

And Write the configuration back into files:

```php
Configure::dump('my_config', 'default');
```

## Documentation

Please make sure you check the [official documentation](http://book.cakephp.org/3.0/en/development/configuration.html)
