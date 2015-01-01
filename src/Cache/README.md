# CakePHP Caching Library

The Cache library provides a `Cache` service locator for interfacing with multiple caching backends using
a simple to use interface.

The caching backends supported are:

* Files
* APC
* Memcached
* Redis
* Wincache
* Xcache

## Usage

Caching engines need to be configured with the `Cache::config()` method.

```php
use Cake\Cache\Cache;

// Using a short name
Cache::config('default', [
    'className' => 'File',
    'duration' => '+1 hours',
    'path' => sys_get_tmp_dir(),
    'prefix' => 'my_app_'
]);

// Using a fully namespaced name.
Cache::config('long', [
    'className' => 'Cake\Cache\Engine\ApcEngine',
    'duration' => '+1 week',
    'prefix' => 'my_app_'
]);

// Using a constructed object.
$object = new FileEngine($config);
Cache::config('other', $object);
```

You can now read a write from the cache:

```php
$data = Cache::remember('my_cache_key', function () {
	return Service::expensiveCall();
});
```

The code above will try to look for data stored in cache under the `my_cache_key`, if not found
the callback will be executed and the returned data will be cached for future calls.

## Documentation

Please make sure you check the [official documentation](http://book.cakephp.org/3.0/en/core-libraries/caching.html)


