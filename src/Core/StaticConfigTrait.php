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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use BadMethodCallException;
use InvalidArgumentException;
use LogicException;

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
     * This method can be used to define configuration adapters for an application.
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
     * Setting a cache engine up.
     *
     * ```
     * Cache::setConfig('default', $settings);
     * ```
     *
     * Injecting a constructed adapter in:
     *
     * ```
     * Cache::setConfig('default', $instance);
     * ```
     *
     * Configure multiple adapters at once:
     *
     * ```
     * Cache::setConfig($arrayOfConfig);
     * ```
     *
     * @param string|array $key The name of the configuration, or an array of multiple configs.
     * @param array|object|null $config An array of name => configuration data for adapter.
     * @throws \BadMethodCallException When trying to modify an existing config.
     * @throws \LogicException When trying to store an invalid structured config array.
     * @return void
     */
    public static function setConfig($key, $config = null): void
    {
        if ($config === null) {
            if (!is_array($key)) {
                throw new LogicException('If config is null, key must be an array.');
            }
            foreach ($key as $name => $settings) {
                static::setConfig($name, $settings);
            }

            return;
        }

        if (isset(static::$_config[$key])) {
            /** @psalm-suppress PossiblyInvalidArgument */
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
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        static::$_config[$key] = $config;
    }

    /**
     * Reads existing configuration.
     *
     * @param string $key The name of the configuration.
     * @return mixed|null Configuration data at the named key or null if the key does not exist.
     */
    public static function getConfig(string $key)
    {
        return static::$_config[$key] ?? null;
    }

    /**
     * Reads existing configuration for a specific key.
     *
     * The config value for this key must exist, it can never be null.
     *
     * @param string $key The name of the configuration.
     * @return mixed Configuration data at the named key.
     * @throws \InvalidArgumentException If value does not exist.
     */
    public static function getConfigOrFail(string $key)
    {
        if (!isset(static::$_config[$key])) {
            throw new InvalidArgumentException(sprintf('Expected configuration `%s` not found.', $key));
        }

        return static::$_config[$key];
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
    public static function drop(string $config): bool
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
     * @return string[] Array of configurations.
     */
    public static function configured(): array
    {
        $configurations = array_keys(static::$_config);

        return array_map(function ($key) {
            return (string)$key;
        }, $configurations);
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
     * @throws \InvalidArgumentException If not passed a string, or passed an invalid string
     */
    public static function parseDsn(string $dsn): array
    {
        if (empty($dsn)) {
            return [];
        }

        $pattern = <<<'REGEXP'
{
    ^
    (?P<_scheme>
        (?P<scheme>[\w\\\\]+)://
    )
    (?P<_username>
        (?P<username>.*?)
        (?P<_password>
            :(?P<password>.*?)
        )?
        @
    )?
    (?P<_host>
        (?P<host>[^?#/:@]+)
        (?P<_port>
            :(?P<port>\d+)
        )?
    )?
    (?P<_path>
        (?P<path>/[^?#]*)
    )?
    (?P<_query>
        \?(?P<query>[^#]*)
    )?
    (?P<_fragment>
        \#(?P<fragment>.*)
    )?
    $
}x
REGEXP;

        preg_match($pattern, $dsn, $parsed);

        if (!$parsed) {
            throw new InvalidArgumentException("The DSN string '{$dsn}' could not be parsed.");
        }

        $exists = [];
        foreach ($parsed as $k => $v) {
            if (is_int($k)) {
                unset($parsed[$k]);
            } elseif (strpos($k, '_') === 0) {
                $exists[substr($k, 1)] = ($v !== '');
                unset($parsed[$k]);
            } elseif ($v === '' && !$exists[$k]) {
                unset($parsed[$k]);
            }
        }

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

        $parsed = $queryArgs + $parsed;

        if (empty($parsed['className'])) {
            $classMap = static::getDsnClassMap();

            $parsed['className'] = $parsed['scheme'];
            if (isset($classMap[$parsed['scheme']])) {
                /** @psalm-suppress PossiblyNullArrayOffset */
                $parsed['className'] = $classMap[$parsed['scheme']];
            }
        }

        return $parsed;
    }

    /**
     * Updates the DSN class map for this class.
     *
     * @param string[] $map Additions/edits to the class map to apply.
     * @return void
     * @psalm-param array<string, class-string> $map
     */
    public static function setDsnClassMap(array $map): void
    {
        static::$_dsnClassMap = $map + static::$_dsnClassMap;
    }

    /**
     * Returns the DSN class map for this class.
     *
     * @return string[]
     * @psalm-return array<string, class-string>
     */
    public static function getDsnClassMap(): array
    {
        return static::$_dsnClassMap;
    }
}
