<?php
namespace Cake\ORM;

use Cake\Utility\Inflector;

/**
 * Provides a registry/factory for Table gateways.
 *
 * This registry allows you to centralize the configuration for tables
 * their connections and other meta-data.
 */
class TableRegistry {

	protected static $_config = [];

	protected static $_instances = [];

/**
 * Stores a list of options to be used when instantiating an object for the table
 * with the same name as $table. The options that can be stored are those that
 * are recognized by `build()`
 *
 * If second argument is omitted, it will return the current settings for $table
 *
 * If no arguments are passed it will return the full configuration array for
 * all tables
 *
 * @param string $table name of the table
 * @param array $options list of options for the table
 * @return array
 */
	public static function config($table, $data) {
		if ($table === null) {
			return static::$_config;
		}
		if (!is_string($table)) {
			return static::$_config = $table;
		}
		if ($options === null) {
			return isset(static::$_config[$table]) ? static::$_tablesMap[$table] : [];
		}
		return static::$_config[$table] = $options;
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

		return static::$_instances[$alias] = new $options['className']($options);
	}

/**
 * Set an instance, this should be temporary.
 *
 */
	public static function set($alias, $object) {
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
