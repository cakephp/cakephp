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

use Cake\Error\Exception;

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
 * @throws \Cake\Error\Exception When trying to modify an existing config.
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
			throw new Exception(sprintf('Cannot reconfigure existing key "%s"', $key));
		}
		if (is_object($config)) {
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
		if (isset(static::$_registry) && isset(static::$_registry->{$config})) {
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

}
