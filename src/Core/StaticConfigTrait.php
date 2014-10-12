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

/**
 * A trait that provides a set of static methods to manage configuration
 * for classes that provide an adapter facade or need to have sets of
 * configuration data registered and manipulated.
 *
 * Implementing objects are expected to declare a static `$_config` property.
 */
trait StaticConfigTrait {

/**
 * Configuration sets.
 *
 * @var array
 */
	protected static $_config = [];

/**
 * This method can be used to define confguration adapters for an application
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
 * `Cache::config('default');`
 *
 * Setting a cache engine up.
 *
 * `Cache::config('default', $settings);`
 *
 * Injecting a constructed adapter in:
 *
 * `Cache::config('default', $instance);`
 *
 * Configure multiple adapters at once:
 *
 * `Cache::config($arrayOfConfig);`
 *
 * @param string|array $key The name of the configuration, or an array of multiple configs.
 * @param array $config An array of name => configuration data for adapter.
 * @return mixed null when adding configuration and an array of configuration data when reading.
 * @throws \BadMethodCallException When trying to modify an existing config.
 */
	public static function config($key, $config = null) {
		// Read config.
		if ($config === null && is_string($key)) {
			return isset(static::$_config[$key]) ? static::$_config[$key] : null;
		}
		if ($config === null && is_array($key)) {
			foreach ($key as $name => $settings) {
				static::config($name, $settings);
			}
			return;
		}
		if (isset(static::$_config[$key])) {
			throw new BadMethodCallException(sprintf('Cannot reconfigure existing key "%s"', $key));
		}
		if (is_array($config)) {
			$config = static::parseDsn($config);
		} elseif ($config === null && is_array($key)) {
			foreach ($key as $name => $settings) {
				$key[$name] = static::parseDsn($settings);
			}
		} elseif (is_object($config)) {
			$config = ['className' => $config];
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
 * @param string $config An existing configuation you wish to remove.
 * @return bool success of the removal, returns false when the config does not exist.
 */
	public static function drop($config) {
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
	public static function configured() {
		return array_keys(static::$_config);
	}

/**
 * Parses a dsn into a valid connection configuration
 *
 * This method allows setting a dsn using PEAR::DB formatting, with added support for drivers
 * in the SQLAlchemy format. The following is an example of it's usage:
 *
 * {{{
 * 	 $dsn = 'mysql+Cake\Database\Driver\Mysql://user:password@localhost:3306/database_name';
 * 	 $config = ConnectionManager::parseDsn($dsn);
 * }}
 *
 * If an array is given, the parsed dsn will be merged into this array. Note that querystring
 * arguments are also parsed and set as values in the returned configuration.
 *
 * @param array $config An array with a `dsn` key mapping to a string dsn
 * @return mixed null when adding configuration and an array of configuration data when reading.
 */
	public static function parseDsn($config) {
		if (!is_array($config) || !isset($config['url'])) {
			return $config;
		}

		$driver = null;
		$dsn = $config['url'];
		unset($config['url']);

		if (preg_match("/^([\w]+)\+([\w\\\]+)/", $dsn, $matches)) {
			$scheme = $matches[1];
			$driver = $matches[2];
			$dsn = preg_replace("/^([\w]+)\+([\w\\\]+)/", $scheme, $dsn);
		}

		$parsed = parse_url($dsn);
		$query = '';

		if (isset($parsed['query'])) {
			$query = $parsed['query'];
			unset($parsed['query']);
		}

		parse_str($query, $queryArgs);

		if ($driver !== null) {
			$queryArgs['driver'] = $driver;
		}

		$config = array_merge($queryArgs, $parsed, $config);

		foreach ($config as $key => $value) {
			if ($value === 'true') {
				$config[$key] = true;
			} elseif ($value === 'false') {
				$config[$key] = false;
			}
		}

		return $config;
	}

}
