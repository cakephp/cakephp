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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Core\StaticConfigTrait;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Closure;

/**
 * Manages and loads instances of Connection
 *
 * Provides an interface to loading and creating connection objects. Acts as
 * a registry for the connections defined in an application.
 *
 * Provides an interface for loading and enumerating connections defined in
 * config/app.php
 */
class ConnectionManager
{
    use StaticConfigTrait {
        setConfig as protected _setConfig;
        parseDsn as protected _parseDsn;
    }

    /**
     * A map of connection aliases.
     *
     * @var array<string, string>
     */
    protected static array $_aliasMap = [];

    /**
     * An array mapping url schemes to fully qualified driver class names
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string>
     */
    protected static array $_dsnClassMap = [
        'mysql' => Mysql::class,
        'postgres' => Postgres::class,
        'sqlite' => Sqlite::class,
        'sqlserver' => Sqlserver::class,
    ];

    /**
     * The ConnectionRegistry used by the manager.
     *
     * @var \Cake\Datasource\ConnectionRegistry
     */
    protected static ConnectionRegistry $_registry;

    /**
     * Configure a new connection object.
     *
     * The connection will not be constructed until it is first used.
     *
     * @param array<string, mixed>|string $key The name of the connection config, or an array of multiple configs.
     * @param \Cake\Datasource\ConnectionInterface|\Closure|array<string, mixed>|null $config An array of name => config data for adapter.
     * @return void
     * @throws \Cake\Core\Exception\CakeException When trying to modify an existing config.
     * @see \Cake\Core\StaticConfigTrait::config()
     */
    public static function setConfig(array|string $key, ConnectionInterface|Closure|array|null $config = null): void
    {
        if (is_array($config)) {
            $config['name'] = $key;
        }

        static::_setConfig($key, $config);
    }

    /**
     * Parses a DSN into a valid connection configuration
     *
     * This method allows setting a DSN using formatting similar to that used by PEAR::DB.
     * The following is an example of its usage:
     *
     * ```
     * $dsn = 'mysql://user:pass@localhost/database';
     * $config = ConnectionManager::parseDsn($dsn);
     *
     * $dsn = 'Cake\Database\Driver\Mysql://localhost:3306/database?className=Cake\Database\Connection';
     * $config = ConnectionManager::parseDsn($dsn);
     *
     * $dsn = 'Cake\Database\Connection://localhost:3306/database?driver=Cake\Database\Driver\Mysql';
     * $config = ConnectionManager::parseDsn($dsn);
     * ```
     *
     * For all classes, the value of `scheme` is set as the value of both the `className` and `driver`
     * unless they have been otherwise specified.
     *
     * Note that query-string arguments are also parsed and set as values in the returned configuration.
     *
     * @param string $dsn The DSN string to convert to a configuration array
     * @return array<string, mixed> The configuration array to be stored after parsing the DSN
     */
    public static function parseDsn(string $dsn): array
    {
        $config = static::_parseDsn($dsn);

        if (isset($config['path']) && empty($config['database'])) {
            $config['database'] = substr($config['path'], 1);
        }

        if (empty($config['driver'])) {
            $config['driver'] = $config['className'] ?? null;
            $config['className'] = Connection::class;
        }

        unset($config['path']);

        return $config;
    }

    /**
     * Set one or more connection aliases.
     *
     * Connection aliases allow you to rename active connections without overwriting
     * the aliased connection. This is most useful in the test-suite for replacing
     * connections with their test variant.
     *
     * Defined aliases will take precedence over normal connection names. For example,
     * if you alias 'default' to 'test', fetching 'default' will always return the 'test'
     * connection as long as the alias is defined.
     *
     * You can remove aliases with ConnectionManager::dropAlias().
     *
     * ### Usage
     *
     * ```
     * // Make 'things' resolve to 'test_things' connection
     * ConnectionManager::alias('test_things', 'things');
     * ```
     *
     * @param string $source The existing connection to alias.
     * @param string $alias The alias name that resolves to `$source`.
     * @return void
     */
    public static function alias(string $source, string $alias): void
    {
        static::$_aliasMap[$alias] = $source;
    }

    /**
     * Drop an alias.
     *
     * Removes an alias from ConnectionManager. Fetching the aliased
     * connection may fail if there is no other connection with that name.
     *
     * @param string $alias The connection alias to drop
     * @return void
     */
    public static function dropAlias(string $alias): void
    {
        unset(static::$_aliasMap[$alias]);
    }

    /**
     * Returns the current connection aliases and what they alias.
     *
     * @return array<string, string>
     */
    public static function aliases(): array
    {
        return static::$_aliasMap;
    }

    /**
     * Get a connection.
     *
     * If the connection has not been constructed an instance will be added
     * to the registry. This method will use any aliases that have been
     * defined. If you want the original unaliased connections pass `false`
     * as second parameter.
     *
     * @param string $name The connection name.
     * @param bool $useAliases Whether connection aliases are used
     * @return \Cake\Datasource\ConnectionInterface
     * @throws \Cake\Datasource\Exception\MissingDatasourceConfigException When config
     * data is missing.
     */
    public static function get(string $name, bool $useAliases = true): ConnectionInterface
    {
        if ($useAliases && isset(static::$_aliasMap[$name])) {
            $name = static::$_aliasMap[$name];
        }

        if (!isset(static::$_config[$name])) {
            throw new MissingDatasourceConfigException(['name' => $name]);
        }
        static::$_registry ??= new ConnectionRegistry();

        return static::$_registry->{$name} ?? static::$_registry->load($name, static::$_config[$name]);
    }
}
