[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/container.svg?style=flat-square)](https://packagist.org/packages/cakephp/container)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Container Library

The Container library provides a PSR-11 compatible dependency injection container
for defining application services and resolving their dependencies.

## Usage

Services can be defined with concrete classes, shared instances, factory
functions, or interface mappings:

```php
use App\Service\AuditLogService;
use App\Service\AuditLogServiceInterface;
use App\Service\ApiClient;
use App\Service\BillingService;
use Cake\Container\Container;

$container = new Container();

// Add a concrete class.
$container->add(BillingService::class);

// Add a singleton service.
$container->addShared('apiClient', fn () => new ApiClient('https://example.com'));

// Add an implementation for an interface.
$container->add(AuditLogServiceInterface::class, AuditLogService::class);

$billing = $container->get(BillingService::class);
```

Definitions can also have arguments, tags, and service providers attached when
your application needs more control over how objects are built.

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/5/en/development/dependency-injection.html)
