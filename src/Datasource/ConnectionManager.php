<?php
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
use Cake\Datasource\Exception\MissingDatasourceConfigException;

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
     * @var array
     */
    protected static $_aliasMap = [];

    /**
     * An array mapping url schemes to fully qualified driver class names
     *
     * @return array
     */
    protected static $_dsnClassMap = [
        'mysql' => 'Cake\Database\Driver\Mysql',
        'postgres' => 'Cake\Database\Driver\Postgres',
        'sqlite' => 'Cake\Database\Driver\Sqlite',
        'sqlserver' => 'Cake\Database\Driver\Sqlserver',
    ];

    /**
     * The ConnectionRegistry used by the manager.
     *
     * @var \Cake\Datasource\ConnectionRegistry
     */
    protected static $_registry;

    /**
     * Configure a new connection object.
     *
     * The connection will not be constructed until it is first used.
     *
     * @param string|array $key The name of the connection config, or an array of multiple configs.
     * @param array|null $config An array of name => config data for adapter.
     * @return void
     * @throws \Cake\Core\Exception\Exception When trying to modify an existing config.
     * @see \Cake\Core\StaticConfigTrait::config()
     */
    public static function setConfig($key, $config = null)
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
     * @param string|null $config The DSN string to convert to a configuration array
     * @return array The configuration array to be stored after parsing the DSN
     */
    public static function parseDsn($config = null)
    {
        $config = static::_parseDsn($config);

        if (isset($config['path']) && empty($config['database'])) {
            $config['database'] = substr($config['path'], 1);
        }

        if (empty($config['driver'])) {
            $config['driver'] = $config['className'];
            $config['className'] = 'Cake\Database\Connection';
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
     * @param string $alias The alias to add. Fetching $source will return $alias when loaded with get.
     * @param string $source The connection to add an alias to.
     * @return void
     * @throws \Cake\Datasource\Exception\MissingDatasourceConfigException When aliasing a
     * connection that does not exist.
     */
    public static function alias($alias, $source)
    {
        if (empty(static::$_config[$source]) && empty(static::$_config[$alias])) {
            throw new MissingDatasourceConfigException(
                sprintf('Cannot create alias of "%s" as it does not exist.', $alias)
            );
        }
        static::$_aliasMap[$source] = $alias;
    }

    /**
     * Drop an alias.
     *
     * Removes an alias from ConnectionManager. Fetching the aliased
     * connection may fail if there is no other connection with that name.
     *
     * @param string $name The connection name to remove aliases for.
     * @return void
     */
    public static function dropAlias($name)
    {
        unset(static::$_aliasMap[$name]);
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
     * @param bool $useAliases Set to false to not use aliased connections.
     * @return \Cake\Datasource\ConnectionInterface A connection object.
     * @throws \Cake\Datasource\Exception\MissingDatasourceConfigException When config
     * data is missing.
     */
    public static function get($name, $useAliases = true)
    {
        if ($useAliases && isset(static::$_aliasMap[$name])) {
            $name = static::$_aliasMap[$name];
        }
        if (empty(static::$_config[$name])) {
            throw new MissingDatasourceConfigException(['name' => $name]);
        }
        if (empty(static::$_registry)) {
            static::$_registry = new ConnectionRegistry();
        }
        if (isset(static::$_registry->{$name})) {
            return static::$_registry->{$name};
        }

        return static::$_registry->load($name, static::$_config[$name]);
    }
}
