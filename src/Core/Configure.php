<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Cache\Cache;
use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;
use RuntimeException;

/**
 * Configuration class. Used for managing runtime configuration information.
 *
 * Provides features for reading and writing to the runtime configuration, as well
 * as methods for loading additional configuration files or storing runtime configuration
 * for future use.
 *
 * @link https://book.cakephp.org/4/en/development/configuration.html
 */
class Configure
{
    /**
     * Array of values currently stored in Configure.
     *
     * @var array<string, mixed>
     */
    protected static $_values = [
        'debug' => false,
    ];

    /**
     * Configured engine classes, used to load config files from resources
     *
     * @see \Cake\Core\Configure::load()
     * @var array<\Cake\Core\Configure\ConfigEngineInterface>
     */
    protected static $_engines = [];

    /**
     * Flag to track whether ini_set exists.
     *
     * @var bool|null
     */
    protected static $_hasIniSet;

    /**
     * Used to store a dynamic variable in Configure.
     *
     * Usage:
     * ```
     * Configure::write('One.key1', 'value of the Configure::One[key1]');
     * Configure::write(['One.key1' => 'value of the Configure::One[key1]']);
     * Configure::write('One', [
     *     'key1' => 'value of the Configure::One[key1]',
     *     'key2' => 'value of the Configure::One[key2]'
     * ]);
     *
     * Configure::write([
     *     'One.key1' => 'value of the Configure::One[key1]',
     *     'One.key2' => 'value of the Configure::One[key2]'
     * ]);
     * ```
     *
     * @param array<string, mixed>|string $config The key to write, can be a dot notation value.
     * Alternatively can be an array containing key(s) and value(s).
     * @param mixed $value Value to set for the given key.
     * @return void
     * @link https://book.cakephp.org/4/en/development/configuration.html#writing-configuration-data
     */
    public static function write($config, $value = null): void
    {
        if (!is_array($config)) {
            $config = [$config => $value];
        }

        foreach ($config as $name => $valueToInsert) {
            static::$_values = Hash::insert(static::$_values, $name, $valueToInsert);
        }

        if (isset($config['debug'])) {
            if (static::$_hasIniSet === null) {
                static::$_hasIniSet = function_exists('ini_set');
            }
            if (static::$_hasIniSet) {
                ini_set('display_errors', $config['debug'] ? '1' : '0');
            }
        }
    }

    /**
     * Used to read information stored in Configure. It's not
     * possible to store `null` values in Configure.
     *
     * Usage:
     * ```
     * Configure::read('Name'); will return all values for Name
     * Configure::read('Name.key'); will return only the value of Configure::Name[key]
     * ```
     *
     * @param string|null $var Variable to obtain. Use '.' to access array elements.
     * @param mixed $default The return value when the configure does not exist
     * @return mixed Value stored in configure, or null.
     * @link https://book.cakephp.org/4/en/development/configuration.html#reading-configuration-data
     */
    public static function read(?string $var = null, $default = null)
    {
        if ($var === null) {
            return static::$_values;
        }

        return Hash::get(static::$_values, $var, $default);
    }

    /**
     * Returns true if given variable is set in Configure.
     *
     * @param string $var Variable name to check for
     * @return bool True if variable is there
     */
    public static function check(string $var): bool
    {
        if (empty($var)) {
            return false;
        }

        return static::read($var) !== null;
    }

    /**
     * Used to get information stored in Configure. It's not
     * possible to store `null` values in Configure.
     *
     * Acts as a wrapper around Configure::read() and Configure::check().
     * The configure key/value pair fetched via this method is expected to exist.
     * In case it does not an exception will be thrown.
     *
     * Usage:
     * ```
     * Configure::readOrFail('Name'); will return all values for Name
     * Configure::readOrFail('Name.key'); will return only the value of Configure::Name[key]
     * ```
     *
     * @param string $var Variable to obtain. Use '.' to access array elements.
     * @return mixed Value stored in configure.
     * @throws \RuntimeException if the requested configuration is not set.
     * @link https://book.cakephp.org/4/en/development/configuration.html#reading-configuration-data
     */
    public static function readOrFail(string $var)
    {
        if (!static::check($var)) {
            throw new RuntimeException(sprintf('Expected configuration key "%s" not found.', $var));
        }

        return static::read($var);
    }

    /**
     * Used to delete a variable from Configure.
     *
     * Usage:
     * ```
     * Configure::delete('Name'); will delete the entire Configure::Name
     * Configure::delete('Name.key'); will delete only the Configure::Name[key]
     * ```
     *
     * @param string $var the var to be deleted
     * @return void
     * @link https://book.cakephp.org/4/en/development/configuration.html#deleting-configuration-data
     */
    public static function delete(string $var): void
    {
        static::$_values = Hash::remove(static::$_values, $var);
    }

    /**
     * Used to consume information stored in Configure. It's not
     * possible to store `null` values in Configure.
     *
     * Acts as a wrapper around Configure::consume() and Configure::check().
     * The configure key/value pair consumed via this method is expected to exist.
     * In case it does not an exception will be thrown.
     *
     * @param string $var Variable to consume. Use '.' to access array elements.
     * @return mixed Value stored in configure.
     * @throws \RuntimeException if the requested configuration is not set.
     * @since 3.6.0
     */
    public static function consumeOrFail(string $var)
    {
        if (!static::check($var)) {
            throw new RuntimeException(sprintf('Expected configuration key "%s" not found.', $var));
        }

        return static::consume($var);
    }

    /**
     * Used to read and delete a variable from Configure.
     *
     * This is primarily used during bootstrapping to move configuration data
     * out of configure into the various other classes in CakePHP.
     *
     * @param string $var The key to read and remove.
     * @return array|string|null
     */
    public static function consume(string $var)
    {
        if (strpos($var, '.') === false) {
            if (!isset(static::$_values[$var])) {
                return null;
            }
            $value = static::$_values[$var];
            unset(static::$_values[$var]);

            return $value;
        }
        $value = Hash::get(static::$_values, $var);
        static::delete($var);

        return $value;
    }

    /**
     * Add a new engine to Configure. Engines allow you to read configuration
     * files in various formats/storage locations. CakePHP comes with two built-in engines
     * PhpConfig and IniConfig. You can also implement your own engine classes in your application.
     *
     * To add a new engine to Configure:
     *
     * ```
     * Configure::config('ini', new IniConfig());
     * ```
     *
     * @param string $name The name of the engine being configured. This alias is used later to
     *   read values from a specific engine.
     * @param \Cake\Core\Configure\ConfigEngineInterface $engine The engine to append.
     * @return void
     */
    public static function config(string $name, ConfigEngineInterface $engine): void
    {
        static::$_engines[$name] = $engine;
    }

    /**
     * Returns true if the Engine objects is configured.
     *
     * @param string $name Engine name.
     * @return bool
     */
    public static function isConfigured(string $name): bool
    {
        return isset(static::$_engines[$name]);
    }

    /**
     * Gets the names of the configured Engine objects.
     *
     * @return array<string>
     */
    public static function configured(): array
    {
        $engines = array_keys(static::$_engines);

        return array_map(function ($key) {
            return (string)$key;
        }, $engines);
    }

    /**
     * Remove a configured engine. This will unset the engine
     * and make any future attempts to use it cause an Exception.
     *
     * @param string $name Name of the engine to drop.
     * @return bool Success
     */
    public static function drop(string $name): bool
    {
        if (!isset(static::$_engines[$name])) {
            return false;
        }
        unset(static::$_engines[$name]);

        return true;
    }

    /**
     * Loads stored configuration information from a resource. You can add
     * config file resource engines with `Configure::config()`.
     *
     * Loaded configuration information will be merged with the current
     * runtime configuration. You can load configuration files from plugins
     * by preceding the filename with the plugin name.
     *
     * `Configure::load('Users.user', 'default')`
     *
     * Would load the 'user' config file using the default config engine. You can load
     * app config files by giving the name of the resource you want loaded.
     *
     * ```
     * Configure::load('setup', 'default');
     * ```
     *
     * If using `default` config and no engine has been configured for it yet,
     * one will be automatically created using PhpConfig
     *
     * @param string $key name of configuration resource to load.
     * @param string $config Name of the configured engine to use to read the resource identified by $key.
     * @param bool $merge if config files should be merged instead of simply overridden
     * @return bool True if load successful.
     * @throws \Cake\Core\Exception\CakeException if the $config engine is not found
     * @link https://book.cakephp.org/4/en/development/configuration.html#reading-and-writing-configuration-files
     */
    public static function load(string $key, string $config = 'default', bool $merge = true): bool
    {
        $engine = static::_getEngine($config);
        if (!$engine) {
            throw new CakeException(
                sprintf(
                    'Config %s engine not found when attempting to load %s.',
                    $config,
                    $key
                )
            );
        }

        $values = $engine->read($key);

        if ($merge) {
            $values = Hash::merge(static::$_values, $values);
        }

        static::write($values);

        return true;
    }

    /**
     * Dump data currently in Configure into $key. The serialization format
     * is decided by the config engine attached as $config. For example, if the
     * 'default' adapter is a PhpConfig, the generated file will be a PHP
     * configuration file loadable by the PhpConfig.
     *
     * ### Usage
     *
     * Given that the 'default' engine is an instance of PhpConfig.
     * Save all data in Configure to the file `my_config.php`:
     *
     * ```
     * Configure::dump('my_config', 'default');
     * ```
     *
     * Save only the error handling configuration:
     *
     * ```
     * Configure::dump('error', 'default', ['Error', 'Exception'];
     * ```
     *
     * @param string $key The identifier to create in the config adapter.
     *   This could be a filename or a cache key depending on the adapter being used.
     * @param string $config The name of the configured adapter to dump data with.
     * @param array<string> $keys The name of the top-level keys you want to dump.
     *   This allows you save only some data stored in Configure.
     * @return bool Success
     * @throws \Cake\Core\Exception\CakeException if the adapter does not implement a `dump` method.
     */
    public static function dump(string $key, string $config = 'default', array $keys = []): bool
    {
        $engine = static::_getEngine($config);
        if (!$engine) {
            throw new CakeException(sprintf('There is no "%s" config engine.', $config));
        }
        $values = static::$_values;
        if (!empty($keys)) {
            $values = array_intersect_key($values, array_flip($keys));
        }

        return $engine->dump($key, $values);
    }

    /**
     * Get the configured engine. Internally used by `Configure::load()` and `Configure::dump()`
     * Will create new PhpConfig for default if not configured yet.
     *
     * @param string $config The name of the configured adapter
     * @return \Cake\Core\Configure\ConfigEngineInterface|null Engine instance or null
     */
    protected static function _getEngine(string $config): ?ConfigEngineInterface
    {
        if (!isset(static::$_engines[$config])) {
            if ($config !== 'default') {
                return null;
            }
            static::config($config, new PhpConfig());
        }

        return static::$_engines[$config];
    }

    /**
     * Used to determine the current version of CakePHP.
     *
     * Usage
     * ```
     * Configure::version();
     * ```
     *
     * @return string Current version of CakePHP
     */
    public static function version(): string
    {
        $version = static::read('Cake.version');
        if ($version !== null) {
            return $version;
        }

        $path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'config/config.php';
        if (is_file($path)) {
            $config = require $path;
            static::write($config);

            return static::read('Cake.version');
        }

        return 'unknown';
    }

    /**
     * Used to write runtime configuration into Cache. Stored runtime configuration can be
     * restored using `Configure::restore()`. These methods can be used to enable configuration managers
     * frontends, or other GUI type interfaces for configuration.
     *
     * @param string $name The storage name for the saved configuration.
     * @param string $cacheConfig The cache configuration to save into. Defaults to 'default'
     * @param array|null $data Either an array of data to store, or leave empty to store all values.
     * @return bool Success
     * @throws \RuntimeException
     */
    public static function store(string $name, string $cacheConfig = 'default', ?array $data = null): bool
    {
        if ($data === null) {
            $data = static::$_values;
        }
        if (!class_exists(Cache::class)) {
            throw new RuntimeException('You must install cakephp/cache to use Configure::store()');
        }

        return Cache::write($name, $data, $cacheConfig);
    }

    /**
     * Restores configuration data stored in the Cache into configure. Restored
     * values will overwrite existing ones.
     *
     * @param string $name Name of the stored config file to load.
     * @param string $cacheConfig Name of the Cache configuration to read from.
     * @return bool Success.
     * @throws \RuntimeException
     */
    public static function restore(string $name, string $cacheConfig = 'default'): bool
    {
        if (!class_exists(Cache::class)) {
            throw new RuntimeException('You must install cakephp/cache to use Configure::restore()');
        }
        $values = Cache::read($name, $cacheConfig);
        if ($values) {
            static::write($values);

            return true;
        }

        return false;
    }

    /**
     * Clear all values stored in Configure.
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$_values = [];
    }
}
