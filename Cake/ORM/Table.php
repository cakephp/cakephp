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
use Cake\Database\Schema\Table as Schema;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;

/**
 * Represents a single database table.
 *
 * Exposes methods for retrieving data out of it, and manages the associations
 * this table has to other tables. Multiple instances of this class can be created
 * for the same database table with different aliases, this allows you to address
 * your database structure in a richer and more expressive way.
 *
 * ### Retrieving data
 *
 * The primary way to retrieve data is using Table::find(). See that method
 * for more information.
 *
 * ### Bulk updates/deletes
 *
 * You can use Table::updateAll() and Table::deleteAll() to do bulk updates/deletes.
 * You should be aware that events will *not* be fired for bulk updates/deletes.
 *
 * ### Callbacks/events
 *
 * Table objects provide a few callbacks/events you can hook into to augment/replace
 * find operations. Each event uses the standard event subsystem in CakePHP
 *
 * - beforeFind($event, $query, $options) - Fired before each find operation. By stopping
 *   the event and supplying a return value you can bypass the find operation entirely. Any
 *   changes done to the $query instance will be retained for the rest of the find.
 *
 * @see Cake\Event\EventManager for reference on the events system.
 */
class Table {

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
 * The name of the field that represents a human readable representation of a row
 *
 * @var string
 */
	protected $_displayField;

/**
 * The list of associations for this table. Indexed by association name,
 * values are Association object instances.
 *
 * @var array
 */
	protected $_associations = [];

/**
 * EventManager for this table.
 *
 * All model/behavior callbacks will be dispatched on this manager.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * BehaviorRegistry for this table
 *
 * @var Cake\ORM\BehaviorRegistry
 */
	protected $_behaviors;

/**
 * The name of the class that represent a single row for this table
 *
 * @var string
 */
	protected $_entityClass;

/**
 * Initializes a new instance
 *
 * The $config array understands the following keys:
 *
 * - table: Name of the database table to represent
 * - alias: Alias to be assigned to this table (default to table name)
 * - connection: The connection instance to use
 * - entityClass: The fully namespaced class name of the entity class that will
 *   represent rows in this table.
 * - schema: A \Cake\Database\Schema\Table object or an array that can be
 *   passed to it.
 * - eventManager: An instance of an event manager to use for internal events
 * - behaviors: A BehaviorRegistry. Generally not used outside of tests.
 *
 * @param array config Lsit of options for this table
 * @return void
 */
	public function __construct(array $config = []) {
		if (!empty($config['table'])) {
			$this->table($config['table']);
		}
		if (!empty($config['alias'])) {
			$this->alias($config['alias']);
		}
		if (!empty($config['connection'])) {
			$this->connection($config['connection']);
		}
		if (!empty($config['schema'])) {
			$this->schema($config['schema']);
		}
		if (!empty($config['entityClass'])) {
			$this->entityClass($config['entityClass']);
		}
		$eventManager = $behaviors = null;
		if (!empty($config['eventManager'])) {
			$eventManager = $config['eventManager'];
		}
		if (!empty($config['behaviors'])) {
			$behaviors = $config['behaviors'];
		}
		$this->_eventManager = $eventManager ?: new EventManager();
		$this->_behaviors = $behaviors ?: new BehaviorRegistry($this);
		$this->initialize($config);
	}

/**
 * Get the default connection name.
 *
 * This method is used to get the fallback connection name if an
 * instance is created through the TableRegistry without a connection.
 *
 * @return string
 * @see Cake\ORM\TableRegistry::get()
 */
	public static function defaultConnectionName() {
		return 'default';
	}

/**
 * Initialize a table instance. Called after the constructor.
 *
 * You can use this method to define associations, attach behaviors
 * define validation and do any other initialization logic you need.
 *
 * {{{
 *	public function initialize(array $config) {
 *		$this->belongsTo('Users');
 *		$this->belongsToMany('Tagging.Tags');
 *		$this->primaryKey('something_else');
 *	}
 * }}}
 *
 * @param array $config Configuration options passed to the constructor
 * @return void
 */
	public function initialize(array $config) {
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
			$this->_table = Inflector::underscore($table);
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
					->describe($this->table());
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
 * Returns the display field or sets a new one
 *
 * @param string $key sets a new name to be used as display field
 * @return string
 */
	public function displayField($key = null) {
		if ($key !== null) {
			$this->_displayField = $key;
		}
		if ($this->_displayField === null) {
			$schema = $this->schema();
			$this->_displayField = $this->primaryKey();
			if ($schema->column('title')) {
				$this->_displayField = 'title';
			}
			if ($schema->column('name')) {
				$this->_displayField = 'name';
			}
		}
		return $this->_displayField;
	}

/**
 * Returns the class used to hydrate rows for this table or sets
 * a new one
 *
 * @param string $name the name of the class to use
 * @throws \Cake\ORM\Error\MissingEntityException when the entity class cannot be found
 * @return string
 */
	public function entityClass($name = null) {
		if ($name === null && !$this->_entityClass) {
			$default = '\Cake\ORM\Entity';
			$self = get_called_class();
			$parts = explode('\\', $self);

			if ($self === __CLASS__ || count($parts) < 3) {
				return $this->_entityClass = $default;
			}

			$alias = Inflector::singularize(substr(array_pop($parts), 0, -5));
			$name = implode('\\', array_slice($parts, 0, -1)) . '\Entity\\' . $alias;
			if (!class_exists($name)) {
				return $this->_entityClass = $default;
			}
		}

		if ($name !== null) {
			$class = App::classname($name, 'Model\Entity');
			$this->_entityClass = $class;
		}

		if (!$this->_entityClass) {
			throw new Error\MissingEntityException([$name]);
		}

		return $this->_entityClass;
	}

/**
 * Add a behavior.
 *
 * Adds a behavior to this table's behavior collection. Behaviors
 * provide an easy way to create horizontally re-usable features
 * that can provide trait like functionality, and allow for events
 * to be listened to.
 *
 * Example:
 *
 * Load a behavior, with some settings.
 *
 * {{{
 * $this->addBehavior('Tree', ['parent' => 'parentId']);
 * }}}
 *
 * Behaviors are generally loaded during Table::initialize().
 *
 * @param string $name The name of the behavior. Can be a short class reference.
 * @param array $options The options for the behavior to use.
 * @return void
 * @see Cake\ORM\Behavior
 */
	public function addBehavior($name, $options = []) {
		$this->_behaviors->load($name, $options);
	}

/**
 * Returns a association objected configured for the specified alias if any
 *
 * @param string $name the alias used for the association
 * @return Cake\ORM\Association
 */
	public function association($name) {
		$name = strtolower($name);
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
		return $this->_associations[strtolower($association->name())] = $association;
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
 * - dependent: Set to true if you want CakePHP to cascade deletes to the
 *   associated table when an entity is removed on this table. Set to false
 *   if you don't want CakePHP to remove associated data, for when you are using
 *   database constraints.
 * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
 *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
 *   When true records will be loaded and then deleted.
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
		return $this->_associations[strtolower($association->name())] = $association;
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
 * - dependent: Set to true if you want CakePHP to cascade deletes to the
 *   associated table when an entity is removed on this table. Set to false
 *   if you don't want CakePHP to remove associated data, for when you are using
 *   database constraints.
 * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
 *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
 *   When true records will be loaded and then deleted.
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
		return $this->_associations[strtolower($association->name())] = $association;
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
 * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
 *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
 *   When true pivot table records will be loaded and then deleted.
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
		return $this->_associations[strtolower($association->name())] = $association;
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
		return $this->callFinder($type, $query, $options);
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
		$options += [
			'idField' => $this->primaryKey(),
			'valueField' => $this->displayField(),
			'groupField' => false
		];
		$mapper = function($key, $row, $mapReduce) use ($options) {
			$rowKey = $options['idField'];
			$rowVal = $options['valueField'];
			if (!($options['groupField'])) {
				$mapReduce->emit($row[$rowVal], $row[$rowKey]);
				return;
			}

			$key = $row[$options['groupField']];
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
		$success = $statement->rowCount() > 0;
		$statement->closeCursor();
		return $success;
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
 * @see Cake\ORM\Table::delete()
 */
	public function deleteAll($conditions) {
		$query = $this->_buildQuery();
		$query->delete($this->table())
			->where($conditions);
		$statement = $query->executeStatement();
		$success = $statement->rowCount() > 0;
		$statement->closeCursor();
		return $success;
	}

/**
 * Returns true if there is any row in this table matching the specified
 * conditions.
 *
 * @param array $conditions list of conditions to pass to the query
 * @return boolean
 */
	public function exists(array $conditions) {
		return (bool)count($this->find('all')
			->select(['existing' => 1])
			->where($conditions)
			->limit(1)
			->hydrate(false)
			->toArray());
	}

/**
 * Persists an entity based on the fields that are marked as dirty on it and
 * matching the list of fields passed in the `fieldList` inside the options
 * array. Returns the same entity after a successful save or false in case
 * of any error.
 *
 * The options array can receive the following keys:
 *
 * - fieldList: An array of field names that should be saved, if empty all
 * properties in the passed entity will be saved
 * - atomic: Whether to execute the save and callbacks inside a database
 * transaction (default: true)
 *
 * When saving, this method will trigger two events:
 *
 * - Model.beforeSave: Will be triggered just before the list of fields to be
 * persisted is calculated. It receives both the entity and the options as
 * arguments. The options array is passed as an ArrayObject, so any changes in
 * it will be reflected in every listener and remembered at the end of the event
 * so it can be used for the rest of the save operation. Returning false in any
 * of the listeners will abort the saving process. If the event is stopped
 * using the event API, the event object's `result` property will be returned.
 * This can be useful when having your own saving strategy implemented inside a
 * listener.
 * - Model.afterSave: Will be triggered after a successful insert or save,
 * listeners will receive the entity and the options array as arguments. The type
 * of operation performed (insert or update) can be determined by checking the
 * entity's method `isNew`, true meaning an insert and false an update.
 *
 * This method will determine whether the passed entity needs to be
 * inserted or updated in the database. It does that by checking the `isNew`
 * method on the entity, if no information can be found there, it will go
 * directly to the database to check the entity's status.
 *
 * @param \Cake\ORM\Entity the entity to be saved
 * @param array $options
 * @return \Cake\ORM\Entity|boolean
 */
	public function save(Entity $entity, array $options = []) {
		$options = new \ArrayObject($options + ['atomic' => true, 'fieldList' => []]);
		if ($options['atomic']) {
			$connection = $this->connection();
			$success = $connection->transactional(function() use ($entity, $options) {
				return $this->_processSave($entity, $options);
			});
		} else {
			$success = $this->_processSave($entity, $options);
		}

		return $success;
	}

/**
 * Performs the actual saving of an entity based on the passed options.
 *
 * @param \Cake\ORM\Entity the entity to be saved
 * @param array $options
 * @return \Cake\ORM\Entity|boolean
 */
	protected function _processSave($entity, $options) {
		$primary = $entity->extract((array)$this->primaryKey());
		if ($primary && $entity->isNew() === null) {
			$entity->isNew(!$this->exists($primary));
		}

		if ($entity->isNew() === null) {
			$entity->isNew(true);
		}

		$event = new Event('Model.beforeSave', $this, compact('entity', 'options'));
		$this->getEventManager()->dispatch($event);

		if ($event->isStopped()) {
			return $event->result;
		}

		$list = $options['fieldList'] ?: $this->schema()->columns();
		$data = $entity->extract($list, true);
		$keys = array_keys($data);

		if ($entity->isNew()) {
			$success = $this->_insert($entity, $data);
		} else {
			$success = $this->_update($entity, $data);
		}

		if ($success) {
			$event = new Event('Model.afterSave', $this, compact('entity', 'options'));
			$this->getEventManager()->dispatch($event);
			$entity->isNew(false);
		}

		return $success;
	}

/**
 * Auxiliary function to handle the insert of an entity's data in the table
 *
 * @param \Cake\ORM\Entity the subject entity from were $data was extracted
 * @param array $data The actual data that needs to be saved
 * @return \Cake\ORM\Entity|boolean
 */
	protected function _insert($entity, $data) {
		$query = $this->_buildQuery();
		$statement = $query->insert($this->table(), array_keys($data))
			->values($data)
			->executeStatement();

		$success = false;
		if ($statement->rowCount() > 0) {
			$primary = $this->primaryKey();
			$id = $statement->lastInsertId($this->table(), $primary);
			$entity->set($primary, $id);
			$entity->clean();
			$success = $entity;
		}
		$statement->closeCursor();
		return $success;
	}

/**
 * Auxiliary function to handle the update of an entity's data in the table
 *
 * @param \Cake\ORM\Entity the subject entity from were $data was extracted
 * @param array $data The actual data that needs to be saved
 * @return \Cake\ORM\Entity|boolean
 * @throws \InvalidArgumentException When primary key data is missing.
 */
	protected function _update($entity, $data) {
		$primaryKey = $entity->extract((array)$this->primaryKey());
		$data = array_diff_key($data, $primaryKey);

		if (empty($data)) {
			return $entity;
		}

		$filtered = array_filter($primaryKey, 'strlen');
		if (count($filtered) < count($primaryKey)) {
			$message = __d('cake_dev', 'A primary key value is needed for updating');
			throw new \InvalidArgumentException($message);
		}

		$query = $this->_buildQuery();
		$statement = $query->update($this->table())
			->set($data)
			->where($primaryKey)
			->executeStatement();

		$success = false;
		if ($statement->rowCount() > 0) {
			$entity->clean();
			$success = $entity;
		}
		$statement->closeCursor();
		return $success;
	}

/**
 * Delete a single entity.
 *
 * Deletes an entity and possibly related associations from the database
 * based on the 'dependent' option used when defining the association.
 * For HasMany and HasOne associations records will be removed based on
 * the dependent option. Join table records in BelongsToMany associations
 * will always be removed. You can use the `cascadeCallbacks` option
 * when defining associations to change how associated data is deleted.
 *
 * ## Options
 *
 * - `atomic` Defaults to true. When true the deletion happens within a transaction.
 *
 * ## Events
 *
 * - `beforeDelete` Fired before the delete occurs. If stopped the delete
 *   will be aborted. Receives the event, entity, and options.
 * - `afterDelete` Fired after the delete has been successful. Receives
 *   the event, entity, and options.
 *
 * The options argument will be converted into an \ArrayObject instance
 * for the duration of the callbacks, this allows listeners to modify
 * the options used in the delete operation.
 *
 * @param Entity $entity The entity to remove.
 * @param array $options The options fo the delete.
 * @return boolean success
 */
	public function delete(Entity $entity, array $options = []) {
		$options = new \ArrayObject($options + ['atomic' => true]);

		$process = function() use ($entity, $options) {
			return $this->_processDelete($entity, $options);
		};

		if ($options['atomic']) {
			return $this->connection()->transactional($process);
		}
		return $process();
	}

/**
 * Perform the delete operation.
 *
 * Will delete the entity provided. Will remove rows from any
 * dependent associations, and clear out join tables for BelongsToMany associations.
 *
 * @param Entity $entity The entity to delete.
 * @param ArrayObject $options The options for the delete.
 * @return boolean success
 */
	protected function _processDelete($entity, $options) {
		$eventManager = $this->getEventManager();
		$event = new Event('Model.beforeDelete', $this, [
			'entity' => $entity,
			'options' => $options
		]);
		$eventManager->dispatch($event);
		if ($event->isStopped()) {
			return $event->result;
		}

		if ($entity->isNew()) {
			return false;
		}
		$primaryKey = (array)$this->primaryKey();
		$conditions = (array)$entity->extract($primaryKey);

		$query = $this->_buildQuery();
		$statement = $query->delete($this->table())
			->where($conditions)
			->executeStatement();

		$success = $statement->rowCount() > 0;
		if (!$success) {
			return $success;
		}

		foreach ($this->_associations as $assoc) {
			$assoc->cascadeDelete($entity, $options->getArrayCopy());
		}

		$event = new Event('Model.afterDelete', $this, [
			'entity' => $entity,
			'options' => $options
		]);
		$eventManager->dispatch($event);

		return $success;
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
	public function callFinder($type, Query $query, $options = []) {
		$finder = 'find' . ucfirst($type);
		if (method_exists($this, $finder)) {
			return $this->{$finder}($query, $options);
		}

		if ($this->_behaviors && $this->_behaviors->hasFinder($type)) {
			return $this->_behaviors->callFinder($type, [$query, $options]);
		}

		throw new \BadMethodCallException(
			__d('cake_dev', 'Unknown finder method "%s"', $type)
		);
	}

/**
 * Magic method to be able to call scoped finders & behaviors
 * without the find prefix.
 *
 * ## Behavior delegation
 *
 * If your Table uses any behaviors you can call them as if
 * they were on the table object.
 *
 * @param string $method name of the method to be invoked
 * @param array $args List of arguments passed to the function
 * @return mixed
 * @throws \BadMethodCallException
 */
	public function __call($method, $args) {
		if ($this->_behaviors && $this->_behaviors->hasMethod($method)) {
			return $this->_behaviors->call($method, $args);
		}

		throw new \BadMethodCallException(
			__d('cake_dev', 'Unknown method "%s"', $method)
		);
	}

}
