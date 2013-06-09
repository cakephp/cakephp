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

use Cake\Database\Schema\Table as Schema;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\Utility\Inflector;

/**
 * Represents a single database table. Exposes methods for retrieving data out
 * of it  and manages the associations it has to other tables. Multiple
 * instances of this class can be created for the same database table with
 * different aliases, this allows you to address your database structure in a
 * richer and more expressive way.
 */
class Table {

/**
 * A list of all table instances that has been built using the factory
 * method. Instances are indexed by alias
 *
 * @var array
 */
	protected static $_instances = [];

/**
 * A collection of default options to apply to each table built with the
 * factory method. Indexed by table name
 *
 * @var array
 */
	protected static $_tablesMap = [];

/**
 * Name of the table as it can be found in the database
 *
 * @var string
 */
	protected $_table;

/**
 * Human name giving to this particular instance. Multiple objects representing
 * the same database table can exist by using different aliases.
 *
 * @var string
 */
	protected $_alias;

/**
 * Connection instance
 *
 * @var \Cake\Database\Connection
 */
	protected $_connection;

/**
 * The schema object containing a description of this table fields
 *
 * @var \Cake\Database\Schema\Table
 */
	protected $_schema;

/**
 * The name of the field that represents the primary key in the table
 *
 * @var string
 */
	protected $_primaryKey = 'id';

/**
 * The list of associations for this table. Indexed by association name,
 * values are Association object instances.
 *
 * @var array
 */
	protected $_associations = [];

/**
 * Initializes a new instance
 *
 * The $config array understands the following keys:
 *
 * - table: Name of the database table to represent
 * - alias: Alias to be assigned to this table (default to table name)
 * - connection: The connection instance to use
 * - schema: A \Cake\Database\Schema\Table object or an array that can be
 *   passed to it
 *
 * @param array config Lsit of options for this table
 * @return void
 */
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
				$this->_schema = $this->connection()
					->schemaCollection()
					->describe($this->_table);
			}
			return $this->_schema;
		}
		if (is_array($schema)) {
			$schema = new Schema($this->table(), $schema);
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
