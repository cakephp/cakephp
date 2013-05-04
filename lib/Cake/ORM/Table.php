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

use Cake\Utility\Inflector;

class Table {

	protected static $_instances = [];

	protected static $_tablesMap = [];

	protected $_table;

	protected $_alias;

	protected $_connection;

	protected $_schema;

	public function __construct($config = array()) {
		if (!empty($config['table'])) {
			$this->_table = $config['table'];
		}

		if (!empty($config['alias'])) {
			$this->alias($config['alias']);
		} else {
			$this->alias($this->_table);
		}

		if (!empty($config['connection'])) {
			$this->connection($config['connection']);
		}
		if (!empty($config['schema'])) {
			$this->schema($config['schema']);
		}
	}

	public static function build($alias, array $options = []) {
		if (isset(static::$_instances[$alias])) {
			return static::$_instances[$alias];
		}
		if (!empty($options['table']) && isset(static::$_tablesMap[$options['table']])) {
			$options = array_merge(static::$_tablesMap[$options['table']], $options);
		}

		$options = ['alias' => $alias] + $options;

		if (empty($options['table'])) {
			$options['table'] = Inflector::tableize($alias);
		}

		if (empty($options['className'])) {
			$options['className'] = get_called_class();
		}

		return static::$_instances[$alias] = new $options['className']($options);
	}

	public static function instance($alias, self $object = null) {
		if ($object === null) {
			return isset(static::$_instances[$alias]) ? static::$_instances[$alias] : null;
		}
		return static::$_instances[$alias] = $object;
	}

	public static function map($alias = null, array $options = null) {
		if ($alias === null) {
			return static::$_tablesMap;
		}
		if (!is_string($alias)) {
			static::$_tablesMap = $alias;
			return;
		}
		if ($options === null) {
			return isset(static::$_tablesMap[$alias]) ? static::$_tablesMap[$alias] : null;
		}
		static::$_tablesMap[$alias] = $options;
	}

	public static function clearRegistry() {
		static::$_instances = [];
		static::$_tablesMap = [];
	}

	public function table($table = null) {
		if ($table !== null) {
			$this->_table = $table;
		}
		return $this->_table;
	}


	public function alias($alias = null) {
		if ($alias !== null) {
			$this->_alias = $alias;
		}
		return $this->_alias;
	}

	public function connection($conn = null) {
		if ($conn === null) {
			return $this->_connection;
		}
		return $this->_connection = $conn;
	}

	public function schema($schema = null) {
		if ($schema === null) {
			if ($this->_schema === null) {
				$this->_schema = $this->connection()->describe($this->_table);
			}
			return $this->_schema;
		}
		return $this->_schema = $schema;
	}

	public function find($type, $options = []) {
		return $this->{'find' . ucfirst($type)}($this->buildQuery(), $options);
	}

	public function findAll(Query $query, array $options = []) {
		return $query;
	}

	public function findFirst(Query $query, array $options = []) {
		return $query->limit(1);
	}

	protected function buildQuery() {
		$query = new Query($this->connection());
		return $query
			->repository($this)
			->select()
			->from([$this->_alias => $this->_table]);
	}

}
