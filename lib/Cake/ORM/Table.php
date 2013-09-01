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
use Cake\Event\EventManager;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\Utility\Inflector;

/**
 * Represents a single database table. Exposes methods for retrieving data out
 * of it and manages the associations it has to other tables. Multiple
 * instances of this class can be created for the same database table with
 * different aliases, this allows you to address your database structure in a
 * richer and more expressive way.
 *
 * ### Callbacks/events
 *
 * Table objects provide a few callbacks/events you can hook into to augment/replace
 * find operations. Each event uses the standard Event subsystem in CakePHP
 *
 * - beforeFind($event, $query, $options) - Fired before each find operation. By stopping
 *   the event and supplying a return value you can bypass the find operation entirely. Any
 *   changes done to the $query instance will be retained for the rest of the find.
 *
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
 * EventManager for this model.
 *
 * All model/behavior callbacks will be dispatched on this manager.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * Initializes a new instance
 *
 * The $config array understands the following keys:
 *
 * - table: Name of the database table to represent
 * - alias: Alias to be assigned to this table (default to table name)
 * - connection: The connection instance to use
 * - schema: A \Cake\Database\Schema\Table object or an array that can be
 *   passed to it.
 *
 * @param array config Lsit of options for this table
 * @return void
 */
	public function __construct($config = []) {
		if (!empty($config['table'])) {
			$this->table($config['table']);
		}

		if (!empty($config['alias'])) {
			$this->alias($config['alias']);
		}

		$table = $this->table();
		if (isset(static::$_tablesMap[$table])) {
			$config = array_merge(static::$_tablesMap[$table], $config);
		}

		if (!empty($config['connection'])) {
			$this->connection($config['connection']);
		}
		if (!empty($config['schema'])) {
			$this->schema($config['schema']);
		}
		$eventManager = null;
		if (!empty($config['eventManager'])) {
			$eventManager = $config['eventManager'];
		}
		$this->_eventManager = $eventManager ?: new EventManager();
	}

/**
 * A factory method to build a new Table instance. Once created, the instance
 * will be registered so it can be re-used if tried to build again for the same
 * alias.
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
 * @param string $alias the alias for the table
 * @param array $options a list of options as accepted by `__construct()`
 * @return Table
 */
	public static function build($alias, array $options = []) {
		if (isset(static::$_instances[$alias])) {
			return static::$_instances[$alias];
		}

		$options = ['alias' => $alias] + $options;
		if (empty($options['className'])) {
			$options['className'] = get_called_class();
		}

		return static::$_instances[$alias] = new $options['className']($options);
	}

/**
 * Returns the Table object associated to the provided alias, if any.
 * If a Table is passed as second parameter it will be associated to the
 * passed $alias even if another object was associated before.
 *
 * @param string $alias
 * @param Table $object
 * @return Table
 */
	public static function instance($alias, self $object = null) {
		if ($object === null) {
			return isset(static::$_instances[$alias]) ? static::$_instances[$alias] : null;
		}
		return static::$_instances[$alias] = $object;
	}

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
	public static function config($table = null, array $options = null) {
		if ($table === null) {
			return static::$_tablesMap;
		}
		if (!is_string($table)) {
			return static::$_tablesMap = $table;
		}
		if ($options === null) {
			return isset(static::$_tablesMap[$table]) ? static::$_tablesMap[$table] : [];
		}
		return static::$_tablesMap[$table] = $options;
	}

/**
 * Clears the registry of instantiated tables and default configurations
 *
 * @return void
 */
	public static function clearRegistry() {
		static::$_instances = [];
		static::$_tablesMap = [];
	}

/**
 * Returns the database table name or sets a new one
 *
 * @param string $table the new table name
 * @return string
 */
	public function table($table = null) {
		if ($table !== null) {
			$this->_table = $table;
		}
		if ($this->_table === null) {
			$table = namespaceSplit(get_class($this));
			$table = substr(end($table), 0, -5);
			if (empty($table)) {
				$table = $this->alias();
			}
			$this->_table = Inflector::tableize($table);
		}
		return $this->_table;
	}

/**
 * Returns the table alias or sets a new one
 *
 * @param string $table the new table alias
 * @return string
 */
	public function alias($alias = null) {
		if ($alias !== null) {
			$this->_alias = $alias;
		}
		if ($this->_alias === null) {
			$alias = namespaceSplit(get_class($this));
			$alias = substr(end($alias), 0, -5) ?: $this->_table;
			$this->_alias = $alias;
		}
		return $this->_alias;
	}

/**
 * Returns the connection instance or sets a new one
 *
 * @param \Cake\Database\Connection $conn the new connection instance
 * @return \Cake\Database\Connection
 */
	public function connection($conn = null) {
		if ($conn === null) {
			return $this->_connection;
		}
		return $this->_connection = $conn;
	}

/**
 * Get the event manager for this Table.
 *
 * @return Cake\Event\EventManager
 */
	public function getEventManager() {
		return $this->_eventManager;
	}

/**
 * Returns the schema table object describing this table's properties.
 *
 * If an \Cake\Database\Schema\Table is passed, it will be used for this table
 * instead of the default one.
 *
 * If an array is passed, a new \Cake\Database\Schema\Table will be constructed
 * out of it and used as the schema for this table.
 *
 * @param array|\Cake\Database\Schema\Table new schema to be used for this table
 * @return \Cake\Database\Schema\Table
 */
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

/**
 * Returns the primary key field name or sets a new one
 *
 * @param string $key sets a new name to be used as primary key
 * @return string
 */
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
 * table. A "belongs to" association is a N-1 relationship where this table
 * is the N side, and where there is a single associated record in the target
 * table for each one in this table.
 *
 * Target table can be inferred by its name, which is provided in the
 * first argument, or you can either pass the to be instantiated or
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
 * This method will return the association object that was built.
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
 * This method will return the association object that was built.
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

/**
 * Creates a new HasMany association between this table and a target
 * table. A "has many" association is a 1-N relationship.
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
 * - sort: The order in which results for this association should be returned
 * - strategy: The strategy to be used for selecting results Either 'select'
 *   or 'subquery'. If subquery is selected the query used to return results
 *   in the source table will be used as conditions for getting rows in the
 *   target table.
 *
 * This method will return the association object that was built.
 *
 * @param string $associated the alias for the target table. This is used to
 * uniquely identify the association
 * @param array $options list of options to configure the association definition
 * @return Cake\ORM\Association\HasMany
 */
	public function hasMany($associated, array $options = []) {
		$options += ['sourceTable' => $this];
		$association = new HasMany($associated, $options);
		return $this->_associations[$association->name()] = $association;
	}

/**
 * Creates a new BelongsToMany association between this table and a target
 * table. A "belongs to many" association is a M-N relationship.
 *
 * Target table can be inferred by its name, which is provided in the
 * first argument, or you can either pass the class name to be instantiated or
 * an instance of it directly.
 *
 * The options array accept the following keys:
 *
 * - className: The class name of the target table object
 * - targetTable: An instance of a table object to be used as the target table
 * - foreignKey: The name of the field to use as foreign key
 * - joinTable: The name of the table representing the link between the two
 * - through: If you choose to use an already instantiated link table, set this
 *   key to a configured Table instance containing associations to both the source
 *   and target tables in this association.
 * - conditions: array with a list of conditions to filter the join with
 * - sort: The order in which results for this association should be returned
 * - strategy: The strategy to be used for selecting results Either 'select'
 *   or 'subquery'. If subquery is selected the query used to return results
 *   in the source table will be used as conditions for getting rows in the
 *   target table.
 *
 * This method will return the association object that was built.
 *
 * @param string $associated the alias for the target table. This is used to
 * uniquely identify the association
 * @param array $options list of options to configure the association definition
 * @return Cake\ORM\Association\BelongsToMany
 */
	public function belongsToMany($associated, array $options = []) {
		$options += ['sourceTable' => $this];
		$association = new BelongsToMany($associated, $options);
		return $this->_associations[$association->name()] = $association;
	}

/**
 * Creates a new Query for this table and applies some defaults based on the
 * type of search that was selected.
 *
 * ### Model.beforeFind event
 *
 * Each find() will trigger a `Model.beforeFind` event for all attached
 * listeners. Any listener can set a valid result set using $query
 *
 * @param string $type the type of query to perform
 * @param array $options
 * @return \Cake\ORM\Query
 */
	public function find($type, $options = []) {
		$query = $this->_buildQuery();
		$query->select()->applyOptions($options);
		return $this->{'find' . ucfirst($type)}($query, $options);
	}

/**
 * Returns the query as passed
 *
 * @param \Cake\ORM\Query $query
 * @param array $options
 * @return \Cake\ORM\Query
 */
	public function findAll(Query $query, array $options = []) {
		return $query;
	}

/**
 * Sets up a query object so results appear as an indexed array, useful for any
 * place where you would want a list such as for populating input select boxes.
 *
 * When calling this finder, the fields passed are used to determine what should
 * be used as the array key, value and optionally what to group the results by.
 * By default the primary key for the model is used for the key, and the display
 * field as value.
 *
 * The results of this finder will be in the following form:
 *
 *	[
 *		1 => 'value for id 1',
 *		2 => 'value for id 2',
 *		4 => 'value for id 4'
 *	]
 *
 * @param \Cake\ORM\Query $query
 * @param array $options
 * @return \Cake\ORM\Query
 */
	public function findList(Query $query, array $options = []) {
		$mapper = function($key, $row, $mapReduce) use (&$columns) {
			if (empty($columns)) {
				$columns = array_slice(array_keys($row), 0, 3);
			}

			list($rowKey, $rowVal) = $columns;
			if (!isset($columns[2])) {
				$mapReduce->emit($row[$rowVal], $row[$rowKey]);
				return;
			}

			$key = $row[$columns[2]];
			$mapReduce->emitIntermediate($key, [$row[$rowKey] => $row[$rowVal]]);
		};

		$reducer = function($key, $values, $mapReduce) {
			$result = [];
			foreach ($values as $value) {
				$result += $value;
			}
			$mapReduce->emit($result, $key);
		};

		return $query->mapReduce($mapper, $reducer);
	}

/**
 * Results for this finder will be a nested array, and is appropriate if you want
 * to use the parent_id field of your model data to build nested results.
 *
 * Values belonging to a parent row based on their parent_id value will be
 * recursively nested inside the parent row values using the `children` property
 *
 * @param \Cake\ORM\Query $query
 * @param array $options
 * @return \Cake\ORM\Query
 */
	public function findThreaded(Query $query, array $options = []) {
		$parents = [];
		$mapper = function($key, $row, $mapReduce) use (&$parents) {
			$parents[$row['id']] =& $row;
			$mapReduce->emitIntermediate($row['parent_id'], $row['id']);
		};

		$reducer = function($key, $values, $mapReduce) use (&$parents) {
			if (empty($key) || !isset($parents[$key])) {
				foreach ($values as $id) {
					$parents[$id] = new \ArrayObject($parents[$id]);
					$mapReduce->emit($parents[$id]);
				}
				return;
			}
			foreach ($values as $id) {
				$parents[$key]['children'][] =& $parents[$id];
			}
		};

		$formatter = function($key, $row, $mapReduce) {
			$mapReduce->emit($row->getArrayCopy());
		};
		return $query->mapReduce($mapper, $reducer)->mapReduce($formatter);
	}

/**
 * Creates a new Query instance for this table
 *
 * @return \Cake\ORM\Query
 */
	protected function _buildQuery() {
		return new Query($this->connection(), $this);
	}

/**
 * Update all matching rows.
 *
 * Sets the $fields to the provided values based on $conditions.
 * This method will *not* trigger beforeSave/afterSave events. If you need those
 * first load a collection of records and update them.
 *
 * @param array $fields A hash of field => new value.
 * @param array $conditions An array of conditions, similar to those used with find()
 * @return boolean Success Returns true if one or more rows are effected.
 */
	public function updateAll($fields, $conditions) {
		$query = $this->_buildQuery();
		$query->update($this->table())
			->set($fields)
			->where($conditions);
		$statement = $query->executeStatement();
		return $statement->rowCount() > 0;
	}

/**
 * Delete all matching rows.
 *
 * Deletes all rows matching the provided conditions.
 *
 * This method will *not* trigger beforeDelete/afterDelete events. If you
 * need those first load a collection of records and delete them.
 *
 * This method will *not* execute on associations `cascade` attribute. You should
 * use database foreign keys + ON CASCADE rules if you need cascading deletes combined
 * with this method.
 *
 * @param array $conditions An array of conditions, similar to those used with find()
 * @return boolean Success Returns true if one or more rows are effected.
 */
	public function deleteAll($conditions) {
		$query = $this->_buildQuery();
		$query->delete($this->table())
			->where($conditions);
		$statement = $query->executeStatement();
		return $statement->rowCount() > 0;
	}

/**
 * Calls a finder method directly and applies it to the passed query,
 * if no query is passed a new one will be created and returned
 *
 * @param string $type name of the finder to be called
 * @param \Cake\ORM\Query $query The query object to apply the finder options to
 * @param array $args List of options to pass to the finder
 * @return \Cake\ORM\Query
 * @throws \BadMethodCallException
 */
	public function callFinder($type, Query $query = null, $options = []) {
		if (!method_exists($this, 'find' . ucfirst($type))) {
			throw new \BadMethodCallException(
				__d('cake_dev', 'Unknown table method %s', $type)
			);
		}
		if ($query === null) {
			return $this->find($type, $options);
		}
		return $this->{'find' . ucfirst($type)}($query, $options);
	}

/**
 * Magic method to be able to call scoped finders without the
 * find prefix
 *
 * @param string $method name of the method to be invoked
 * @param array $args List of arguments passed to the function
 * @return mixed
 * @throws \BadMethodCallException
 */
	public function __call($method, $args) {
		$query = null;
		if (isset($args[0]) && $args[0] instanceof Query) {
			$query = array_shift($args);
		}
		$options = array_shift($args) ?: [];
		return $this->callFinder($method, $query, $options);
	}

}
