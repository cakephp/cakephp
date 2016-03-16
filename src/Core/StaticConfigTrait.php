<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * A trait that provides a set of static methods to manage configuration
 * for classes that provide an adapter facade or need to have sets of
 * configuration data registered and manipulated.
 *
 * Implementing objects are expected to declare a static `$_dsnClassMap` property.
 */
trait StaticConfigTrait
{

    /**
     * Configuration sets.
     *
     * @var array
     */
    protected static $_config = [];

    /**
     * This method can be used to define configuration adapters for an application
     * or read existing configuration.
     *
     * To change an adapter's configuration at runtime, first drop the adapter and then
     * reconfigure it.
     *
     * Adapters will not be constructed until the first operation is done.
     *
     * ### Usage
     *
     * Assuming that the class' name is `Cache` the following scenarios
     * are supported:
     *
     * Reading config data back:
     *
     * ```
     * Cache::config('default');
     * ```
     *
     * Setting a cache engine up.
     *
     * ```
     * Cache::config('default', $settings);
     * ```
     *
     * Injecting a constructed adapter in:
     *
     * ```
     * Cache::config('default', $instance);
     * ```
     *
     * Configure multiple adapters at once:
     *
     * ```
     * Cache::config($arrayOfConfig);
     * ```
     *
     * @param string|array $key The name of the configuration, or an array of multiple configs.
     * @param array|null $config An array of name => configuration data for adapter.
     * @return array|null Null when adding configuration or an array of configuration data when reading.
     * @throws \BadMethodCallException When trying to modify an existing config.
     */
    public static function config($key, $config = null)
    {
        if ($config === null) {
            // Read config.
            if (is_string($key)) {
                return isset(static::$_config[$key]) ? static::$_config[$key] : null;
            }

            if (is_array($key)) {
                foreach ($key as $name => $settings) {
                    static::config($name, $settings);
                }
                return;
            }
        }

        if (isset(static::$_config[$key])) {
            throw new BadMethodCallException(sprintf('Cannot reconfigure existing key "%s"', $key));
        }

        if (is_object($config)) {
            $config = ['className' => $config];
        }

        if (isset($config['url'])) {
            $parsed = static::parseDsn($config['url']);
            unset($config['url']);
            $config = $parsed + $config;
        }

        if (isset($config['engine']) && empty($config['className'])) {
            $config['className'] = $config['engine'];
            unset($config['engine']);
        }
        static::$_config[$key] = $config;
    }

    /**
     * Drops a constructed adapter.
     *
     * If you wish to modify an existing configuration, you should drop it,
     * change configuration and then re-add it.
     *
     * If the implementing objects supports a `$_registry` object the named configuration
     * will also be unloaded from the registry.
     *
     * @param string $config An existing configuration you wish to remove.
     * @return bool Success of the removal, returns false when the config does not exist.
     */
    public static function drop($config)
    {
        if (!isset(static::$_config[$config])) {
            return false;
        }
        if (isset(static::$_registry)) {
            static::$_registry->unload($config);
        }
        unset(static::$_config[$config]);
        return true;
    }

    /**
     * Returns an array containing the named configurations
     *
     * @return array Array of configurations.
     */
    public static function configured()
    {
        return array_keys(static::$_config);
    }

    /**
     * Parses a DSN into a valid connection configuration
     *
     * This method allows setting a DSN using formatting similar to that used by PEAR::DB.
     * The following is an example of its usage:
     *
     * ```
     * $dsn = 'mysql://user:pass@localhost/database?';
     * $config = ConnectionManager::parseDsn($dsn);
     *
     * $dsn = 'Cake\Log\Engine\FileLog://?types=notice,info,debug&file=debug&path=LOGS';
     * $config = Log::parseDsn($dsn);
     *
     * $dsn = 'smtp://user:secret@localhost:25?timeout=30&client=null&tls=null';
     * $config = Email::parseDsn($dsn);
     *
     * $dsn = 'file:///?className=\My\Cache\Engine\FileEngine';
     * $config = Cache::parseDsn($dsn);
     *
     * $dsn = 'File://?prefix=myapp_cake_core_&serialize=true&duration=+2 minutes&path=/tmp/persistent/';
     * $config = Cache::parseDsn($dsn);
     * ```
     *
     * For all classes, the value of `scheme` is set as the value of both the `className`
     * unless they have been otherwise specified.
     *
     * Note that querystring arguments are also parsed and set as values in the returned configuration.
     *
     * @param string $dsn The DSN string to convert to a configuration array
     * @return array The configuration array to be stored after parsing the DSN
     * @throws \InvalidArgumentException If not passed a string
     */
    public static function parseDsn($dsn)
    {
        if (empty($dsn)) {
            return [];
        }

        if (!is_string($dsn)) {
            throw new InvalidArgumentException('Only strings can be passed to parseDsn');
        }

        $scheme = '';
        if (preg_match("/^([\w\\\]+)/", $dsn, $matches)) {
            $scheme = $matches[1];
            $dsn = preg_replace("/^([\w\\\]+)/", 'file', $dsn);
        }

        $parsed = parse_url($dsn);
        if ($parsed === false) {
            return $dsn;
        }

        $parsed['scheme'] = $scheme;
        $query = '';

        if (isset($parsed['query'])) {
            $query = $parsed['query'];
            unset($parsed['query']);
        }

        parse_str($query, $queryArgs);

        foreach ($queryArgs as $key => $value) {
            if ($value === 'true') {
                $queryArgs[$key] = true;
            } elseif ($value === 'false') {
                $queryArgs[$key] = false;
            } elseif ($value === 'null') {
                $queryArgs[$key] = null;
            }
        }

        if (isset($parsed['user'])) {
            $parsed['username'] = $parsed['user'];
        }

        if (isset($parsed['pass'])) {
            $parsed['password'] = $parsed['pass'];
        }

        unset($parsed['pass'], $parsed['user']);
        $parsed = $queryArgs + $parsed;

        if (empty($parsed['className'])) {
            $classMap = static::dsnClassMap();

            $parsed['className'] = $parsed['scheme'];
            if (isset($classMap[$parsed['scheme']])) {
                $parsed['className'] = $classMap[$parsed['scheme']];
            }
        }

        return $parsed;
    }

    /**
     * Returns or updates the DSN class map for this class
     *
     * @param array|null $map Additions/edits to the class map to apply
     * @return array
     */
    public static function dsnClassMap(array $map = null)
    {
        if ($map !== null) {
            static::$_dsnClassMap = $map + static::$_dsnClassMap;
        }
        return static::$_dsnClassMap;
    }
}
