<?php
/**
 * PHP Version 5.4
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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\Core\App;
use Cake\Database\ConnectionManager;
use Cake\Utility\Inflector;

/**
 * Provides a registry/factory for Table gateways.
 *
 * This registry allows you to centralize the configuration for tables
 * their connections and other meta-data.
 */
class TableRegistry {

/**
 * Configuration for aliases.
 *
 * @var array
 */
	protected static $_config = [];

/**
 * Instances that belong to the registry.
 *
 * @var array
 */
	protected static $_instances = [];

/**
 * Stores a list of options to be used when instantiating an object
 * with a matching alias.
 *
 * The options that can be stored are those that are recognized by `build()`
 * If second argument is omitted, it will return the current settings
 * for $alias.
 *
 * If no arguments are passed it will return the full configuration array for
 * all aliases
 *
 * @param string $alias Name of the alias
 * @param null|array $options list of options for the alias
 * @return array The config data.
 */
	public static function config($alias = null, $options = null) {
		if ($alias === null) {
			return static::$_config;
		}
		if (!is_string($alias)) {
			return static::$_config = $alias;
		}
		if ($options === null) {
			return isset(static::$_config[$alias]) ? static::$_config[$alias] : [];
		}
		return static::$_config[$alias] = $options;
	}

/**
 * Get a table instance from the registry.
 *
 * Tables are only created once until the registry is flushed.
 * This means that aliases must be unique across your application.
 * This is important because table associations are resolved at runtime
 * and cyclic references need to be handled correctly.
 *
 * The options that can be passed are the same as in `__construct()`, but the
 * key `className` is also recognized.
 *
 * When $options contains `className` this method will try to instantiate an
 * object of that class instead of this default Table class.
 *
 * If no `table` option is passed, the table name will be the tableized version
 * of the provided $alias.
 *
 * If no `connection` option is passed the table's defaultConnectionName() method
 * will be called to get the default connection name to use.
 *
 * @param string $alias The alias you want to get.
 * @param array $options The options you want to build the table with.
 *   If a table has already been loaded the options will be ignored.
 * @return Cake\Database\Table
 */
	public static function get($alias, $options = []) {
		if (isset(static::$_instances[$alias])) {
			return static::$_instances[$alias];
		}


		list($plugin, $baseClass) = pluginSplit($alias);
		$options = ['alias' => $baseClass] + $options;

		if (empty($options['className'])) {
			$class = Inflector::classify($alias);
			$className = App::classname($class, 'Model\Repository', 'Table');
			$options['className'] = $className ?: 'Cake\ORM\Table';
			$options['className'] = $className;
		}

		if (isset(static::$_config[$alias])) {
			$options = array_merge(static::$_config[$alias], $options);
		}
		if (empty($options['connection'])) {
			$connectionName = $options['className']::defaultConnectionName();
			$options['connection'] = ConnectionManager::get($connectionName);
		}
		return static::$_instances[$alias] = new $options['className']($options);
	}

/**
 * Set an instance.
 *
 * @param string $alias The alias to set.
 * @param Cake\ORM\Table The table to set.
 * @return void
 */
	public static function set($alias, Table $object) {
		return static::$_instances[$alias] = $object;
	}

/**
 * Clears the registry of configuration and instances.
 *
 * @return void
 */
	public static function clear() {
		static::$_instances = [];
		static::$_config = [];
	}

}
