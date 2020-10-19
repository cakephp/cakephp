[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/log.svg?style=flat-square)](https://packagist.org/packages/cakephp/log)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.txt)

# CakePHP Logging Library

The Log library provides a `Log` service locator for interfacing with
multiple logging backends using a simple interface. With the `Log` class it is
possible to send a single message to multiple logging backends at the same time
or just a subset of them based on the log level or context.

By default, you can use Files or Syslog as logging backends, but you can use any
object implementing `Psr\Log\LoggerInterface` as an engine for the `Log` class.

## Usage

You can define as many or as few loggers as your application needs. Loggers
should be configured using `Cake\Core\Log.` An example would be:

```php
use Cake\Cache\Cache;

use Cake\Log\Log;

// Short classname
Log::config('local', [
    'className' => 'FileLog',
    'levels' => ['notice', 'info', 'debug'],
    'file' => '/path/to/file.log',
]);

// Fully namespaced name.
Log::config('production', [
    'className' => \Cake\Log\Engine\SyslogLog::class,
    'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
]);
```

It is also possible to create loggers by providing a closure.

```php
Log::config('special', function () {
	// Return any PSR-3 compatible logger
	return new MyPSR3CompatibleLogger();
});
```

Or by injecting an instance directly:

```php
Log::config('special', new MyPSR3CompatibleLogger());
```

You can then use the `Log` class to pass messages to the logging backends:

```php
Log::write('debug', 'Something did not work');
```

Only the logging engines subscribed to the log level you are writing to will
get the message passed. In the example above, only the 'local' engine will get
the log message.

### Filtering messages with scopes

The Log library supports another level of message filtering. By using scopes,
you can limit the logging engines that receive a particular message.

```php
// Configure /logs/payments.log to receive all levels, but only
// those with `payments` scope.
Log::config('payments', [
    'className' => 'FileLog',
    'levels' => ['error', 'info', 'warning'],
    'scopes' => ['payments'],
    'file' => '/logs/payments.log',
]);

Log::warning('this gets written only to payments.log', ['scope' => ['payments']]);
```

## Documentation

Please make sure you check the [official documentation](https://book.cakephp.org/4/en/core-libraries/logging.html)
