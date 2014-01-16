<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 0.10.x.1402
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\StaticConfigTrait;
use Cake\Database\Connection;
use Cake\Database\ConnectionRegistry;
use Cake\Error;

/**
 * Manages and loads instances of Connection
 *
 * Provides an interface to loading and creating connection objects. Acts as
 * a registry for the connections defined in an application.
 *
 * Provides an interface for loading and enumerating connections defined in
 * app/Config/datasources.php
 */
class ConnectionManager {

	use StaticConfigTrait {
		config as protected _config;
	}

/**
 * Holds a list of connection configurations
 *
 * @var array
 */
	protected static $_config = [];

/**
 * The ConnectionRegistry used by the manager.
 *
 * @var Cake\Database\ConnectionRegistry
 */
	protected static $_registry = null;

/**
 * Configure a new connection object.
 *
 * The connection will not be constructed until it is first used.
 *
 * @see Cake\Core\StaticConfigTrait::config()
 *
 * @param string|array $key The name of the connection config, or an array of multiple configs.
 * @param array $config An array of name => config data for adapter.
 * @return mixed null when adding configuration and an array of configuration data when reading.
 * @throws Cake\Error\Exception When trying to modify an existing config.
 */
	public static function config($key, $config = null) {
		if (is_array($config)) {
			$config['name'] = $key;
		}
		return static::_config($key, $config);
	}

/**
 * Get a connection.
 *
 * If the connection has not been constructed an instance will be added
 * to the registry.
 *
 * @param string $name The connection name.
 * @return Connection A connection object.
 * @throws Cake\Error\MissingDatasourceConfigException When config data is missing.
 */
	public static function get($name) {
		if (empty(static::$_config[$name])) {
			throw new Error\MissingDatasourceConfigException(['name' => $name]);
		}
		if (empty(static::$_registry)) {
			static::$_registry = new ConnectionRegistry();
		}
		if (isset(static::$_registry->{$name})) {
			return static::$_registry->{$name};
		}
		return static::$_registry->load($name, static::$_config[$name]);
	}

/**
 * Gets a reference to a DataSource object
 *
 * @param string $name The name of the DataSource, as defined in app/Config/datasources.php
 * @return DataSource Instance
 * @throws Cake\Error\MissingDatasourceException
 * @deprecated Will be removed in 3.0 stable.
 */
	public static function getDataSource($name) {
		return static::get($name);
	}

/**
 * Dynamically creates a DataSource object at runtime, with the given name and settings
 *
 * @param string $name The DataSource name
 * @param array $config The DataSource configuration settings
 * @return DataSource A reference to the DataSource object, or null if creation failed
 * @deprecated Will be removed in 3.0 stable
 */
	public static function create($name = '', $config = array()) {
		static::config($name, $config);
		return static::get($name);
	}

}
