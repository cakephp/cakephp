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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Core\StaticConfigTrait;
use Cake\Datasource\ConnectionRegistry;
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
        config as protected _config;
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
    protected static $_registry = null;

    /**
     * Configure a new connection object.
     *
     * The connection will not be constructed until it is first used.
     *
     * @param string|array $key The name of the connection config, or an array of multiple configs.
     * @param array $config An array of name => config data for adapter.
     * @return mixed null when adding configuration and an array of configuration data when reading.
     * @throws \Cake\Core\Exception\Exception When trying to modify an existing config.
     * @see \Cake\Core\StaticConfigTrait::config()
     */
    public static function config($key, $config = null)
    {
        if (is_array($config)) {
            $config['name'] = $key;
        }
        return static::_config($key, $config);
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
     * Note that querystring arguments are also parsed and set as values in the returned configuration.
     *
     * @param array $config An array with a `url` key mapping to a string DSN
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
     * the aliased connection. This is most useful in the testsuite for replacing
     * connections with their test variant.
     *
     * Defined aliases will take precedence over normal connection names. For example,
     * if you alias 'default' to 'test', fetching 'default' will always return the 'test'
     * connection as long as the alias is defined.
     *
     * You can remove aliases with ConnectionManager::dropAlias().
     *
     * @param string $from The connection to add an alias to.
     * @param string $to The alias to create. $from should return when loaded with get().
     * @return void
     * @throws \Cake\Datasource\Exception\MissingDatasourceConfigException When aliasing a
     * connection that does not exist.
     */
    public static function alias($from, $to)
    {
        if (empty(static::$_config[$to]) && empty(static::$_config[$from])) {
            throw new MissingDatasourceConfigException(
                sprintf('Cannot create alias of "%s" as it does not exist.', $from)
            );
        }
        static::$_aliasMap[$to] = $from;
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
     * defined. If you want the original unaliased connections use getOriginal()
     *
     * @param string $name The connection name.
     * @param bool $useAliases Set to false to not use aliased connections.
     * @return \Cake\Database\Connection A connection object.
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
