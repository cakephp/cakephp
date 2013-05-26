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

use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\BelongsToMany;
use Cake\Utility\Inflector;

class Table {

	protected static $_instances = [];

	protected static $_tablesMap = [];

	protected static $_aliasMap = [];

	protected $_table;

	protected $_alias;

	protected $_connection;

	protected $_schema;

	protected $_primaryKey = 'id';

	protected $_associations = [];

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

		if (isset(static::$_aliasMap[$alias])) {
			$options += ['table' => static::$_aliasMap[$alias]];
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

		static::map($alias, $options['table']);

		return static::$_instances[$alias] = new $options['className']($options);
	}

	public static function map($alias, $table) {
		static::$_aliasMap[$alias] = $table;
	}

	public static function instance($alias, self $object = null) {
		if ($object === null) {
			return isset(static::$_instances[$alias]) ? static::$_instances[$alias] : null;
		}
		return static::$_instances[$alias] = $object;
	}

	public static function config($table = null, array $options = null) {
		if ($table === null) {
			return static::$_tablesMap;
		}
		if (!is_string($table)) {
			static::$_tablesMap = $table;
			return;
		}
		if ($options === null) {
			return isset(static::$_tablesMap[$table]) ? static::$_tablesMap[$table] : null;
		}
		static::$_tablesMap[$table] = $options;
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

	public function primaryKey($key = null) {
		if ($key !== null) {
			$this->_primaryKey = $key;
		}
		return $this->_primaryKey;
	}

/**
 * Returns a association objected configured for the specified alias if any
 *
 * @param string $name the alias used for the association
 * @return Cake\ORM\Association
 */
	public function association($name) {
		if (isset($this->_associations[$name])) {
			return $this->_associations[$name];
		}

		return null;
	}

/**
 * Creates a new BelongsTo association between this table and a target
 * table. A "belongs to" association is a 1-N relationship where this table
 * is the N side, and where there is a single associated record in the target
 * table for each one in this table.
 *
 * Target table can be inferred by its name, which is provided in the
 * first argument, or you can either pass the class name to be instantiated or
 * an instance of it directly.
 *
 * The options array accept the following keys:
 *
 * - className: The class name of the target table object
 * - targetTable: An instance of a table object to be used as the target table
 * - foreignKey: The name of the field to use as foreign key, if false none
 *   will be used
 * - conditions: array with a list of conditions to filter the join with
 * - joinType: The type of join to be used (e.g. INNER)
 *
 * This method will return the recently built association object
 *
 * @param string $associated the alias for the target table. This is used to
 * uniquely identify the association
 * @param array $options list of options to configure the association definition
 * @return Cake\ORM\Association\BelongsTo
 */
	public function belongsTo($associated, array $options = []) {
		$options += ['sourceTable' => $this];
		$association = new BelongsTo($associated, $options);
		return $this->_associations[$association->name()] = $association;
	}

/**
 * Creates a new HasOne association between this table and a target
 * table. A "has one" association is a 1-1 relationship.
 *
 * Target table can be inferred by its name, which is provided in the
 * first argument, or you can either pass the class name to be instantiated or
 * an instance of it directly.
 *
 * The options array accept the following keys:
 *
 * - className: The class name of the target table object
 * - targetTable: An instance of a table object to be used as the target table
 * - foreignKey: The name of the field to use as foreign key, if false none
 *   will be used
 * - conditions: array with a list of conditions to filter the join with
 * - joinType: The type of join to be used (e.g. LEFT)
 *
 * This method will return the recently built association object
 *
 * @param string $associated the alias for the target table. This is used to
 * uniquely identify the association
 * @param array $options list of options to configure the association definition
 * @return Cake\ORM\Association\HasOne
 */
	public function hasOne($associated, array $options = []) {
		$options += ['sourceTable' => $this];
		$association = new HasOne($associated, $options);
		return $this->_associations[$association->name()] = $association;
	}

	public function hasMany($associated, array $options = []) {
		$options += ['sourceTable' => $this];
		$association = new HasMany($associated, $options);
		return $this->_associations[$association->name()] = $association;
	}

	public function belongsToMany($associated, array $options = []) {
		$options += ['sourceTable' => $this];
		$association = new BelongsToMany($associated, $options);
		return $this->_associations[$association->name()] = $association;
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
			->select();
	}

}
