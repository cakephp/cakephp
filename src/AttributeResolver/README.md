# CakePHP Attribute Resolver

The Attribute Resolver provides efficient discovery and querying of PHP 8 attributes across your codebase. It enables runtime attribute discovery for features like auto-discovery of routes, commands, and other framework components.

## Installation

```bash
composer require cakephp/attribute-resolver
```

## Configuration

Configure the resolver with paths to scan and optional caching. Paths support glob/wildcard patterns and are automatically expanded against both the application root and all loaded plugin paths:

```php
// In config/bootstrap.php or Application::bootstrap()
use AttributeResolver\AttributeResolver;

AttributeResolver::setConfig('default', [
    'paths' => [
        'src/**/*.php', // Scans app src/ and all plugin src/ directories
    ],
    'excludePaths' => ['vendor', 'tmp'],
    'cache' => '_cake_attributes_', // Cache configuration name (default)
    'validateFiles' => true, // Check if source files changed
    'basePath' => ROOT, // Optional: defaults to ROOT
]);

// Optional: Configure multiple collections
AttributeResolver::setConfig('routes', [
    'paths' => ['src/Controller/**/*.php'], // Scans Controllers in app and plugins
    'cache' => '_cake_attributes_',
    'excludeAttributes' => [SomeAttribute::class],
]);

// Disable caching (not recommended for production)
AttributeResolver::setConfig('nocache', [
    'paths' => ['src/**/*.php'],
    'cache' => false,
]);
```

### Cache Configuration

For maximum performance, configure the cache to use the File adapter which enables PhpEngine:

```php
// In config/app.php
use Cake\Cache\Cache;

Cache::setConfig('_cake_attributes_', [
    'className' => 'File', // Required for PhpEngine
    'path' => CACHE,
    'duration' => '+1 year',
]);
```

## Usage

```php
use AttributeResolver\AttributeResolver;

// Get all attributes from default configuration
$collection = AttributeResolver::collection();

// Filter by specific attribute class (uses magic method forwarding)
$routes = AttributeResolver::withAttribute(Route::class);

// Chain multiple filters
$adminRoutes = AttributeResolver::withAttribute(Route::class)
    ->withNamespace('App\Controller\Admin');

// Filter by target type
$classAttributes = AttributeResolver::withAttribute(MyAttribute::class)
    ->withTargetType(AttributeTargetType::CLASS_TYPE);

// Filter by plugin
$pluginCommands = AttributeResolver::withAttribute(ConsoleCommand::class)
    ->withPlugin('MyPlugin');

// Use named configurations
$commands = AttributeResolver::collection('commands')
    ->withAttribute(ConsoleCommand::class);

// Clear cache
AttributeResolver::clear('default');
```

## CLI Commands

### Cache Warming

```bash
# Warm up the attribute cache
bin/cake attributes warm

# Output:
# Warming attribute cache...
# Cached 28 attributes in 0.15s

# Use a specific configuration
bin/cake attributes warm --config custom
```

### List Attributes

```bash
# List all discovered attributes
bin/cake attributes list

# Filter by attribute type, class, or namespace
bin/cake attributes list --type method --class UsersController

# Show full class names without truncation
bin/cake attributes list --verbose
```

### Inspect Details

```bash
# Inspect specific attributes with full metadata
bin/cake attributes inspect Route

# Output:
# Found 3 attribute(s):
#
# 1. Route
#    Attribute Class: App\Attribute\Route
#    Target Class: App\Controller\UsersController
#    Plugin: -
#    Target: index (method)
#    File: src/Controller/UsersController.php:42
#    Arguments:
#      - path: /users/index
#      - methods: ["GET"]

# Inspect all attributes on a class
bin/cake attributes inspect --class ArticlesController
```

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/6/en/core-libraries/attributes.html)


