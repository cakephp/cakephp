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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\Core\App;
use Cake\Database\Schema\Table as Schema;
use Cake\Database\Type;
use Cake\Event\Event;
use Cake\Event\EventListener;
use Cake\Event\EventManager;
use Cake\ORM\Associations;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Error\MissingEntityException;
use Cake\ORM\Error\RecordNotFoundException;
use Cake\ORM\Marshaller;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

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
 * ### Dynamic finders
 *
 * In addition to the standard find($type) finder methods, CakePHP provides dynamic
 * finder methods. These methods allow you to easily set basic conditions up. For example
 * to filter users by username you would call
 *
 * {{{
 * $query = $users->findByUsername('mark');
 * }}}
 *
 * You can also combine conditions on multiple fields using either `Or` or `And`:
 *
 * {{{
 * $query = $users->findByUsernameOrEmail('mark', 'mark@example.org');
 * }}}
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
 * - `beforeFind($event, $query, $options)` - Fired before each find operation. By stopping
 *   the event and supplying a return value you can bypass the find operation entirely. Any
 *   changes done to the $query instance will be retained for the rest of the find.
 * - `beforeValidate($event, $entity, $options, $validator)` - Fired before an entity is validated.
 *   By stopping this event, you can abort the validate + save operations.
 * - `afterValidate($event, $entity, $options, $validator)` - Fired after an entity is validated.
 * - `beforeSave($event, $entity, $options)` - Fired before each entity is saved. Stopping this
 *   event will abort the save operation. When the event is stopped the result of the event will
 *   be returned.
 * - `afterSave($event, $entity, $options)` - Fired after an entity is saved.
 * - `beforeDelete($event, $entity, $options)` - Fired before an entity is deleted.
 *   By stopping this event you will abort the delete operation.
 * - `afterDelete($event, $entity, $options)` - Fired after an entity has been deleted.
 *
 * @see Cake\Event\EventManager for reference on the events system.
 */
class Table implements EventListener {

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
 * @var array
 */
	protected $_primaryKey;

/**
 * The name of the field that represents a human readable representation of a row
 *
 * @var string
 */
	protected $_displayField;

/**
 * The associations container for this Table.
 *
 * @var Cake\ORM\Associations
 */
	protected $_associated;

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
 * A list of validation objects indexed by name
 *
 * @var array
 */
	protected $_validators = [];

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
		$associations = null;
		if (!empty($config['associations'])) {
			$associations = $config['associations'];
		}
		$this->_eventManager = $eventManager ?: new EventManager();
		$this->_associated = $associations ?: new Associations();
		$this->_behaviors = $behaviors ?: new BehaviorRegistry($this);

		$this->initialize($config);
		$this->_eventManager->attach($this);
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
			$constraints = [];

			if (isset($schema['_constraints'])) {
				$constraints = $schema['_constraints'];
				unset($schema['_constraints']);
			}

			$schema = new Schema($this->table(), $schema);

			foreach ($constraints as $name => $value) {
				$schema->addConstraint($name, $value);
			}
		}

		return $this->_schema = $schema;
	}

/**
 * Test to see if a Table has a specific field/column.
 *
 * Delegates to the schema object and checks for column presence
 * using the Schema\Table instance.
 *
 * @param string $field The field to check for.
 * @return boolean True if the field exists, false if it does not.
 */
	public function hasField($field) {
		$schema = $this->schema();
		return $schema->column($field) !== null;
	}

/**
 * Test to see if a Table has any of these specific fields/columns.
 *
 * Delegates to the schema object and checks for column presence
 * using the Schema\Table instance.
 *
 * @param array $fields The fields to check for.
 * @return boolean True if any field exists, false if it does not.
 */
	public function hasAnyFields($fields) {
		foreach ($fields as $field) {
			if ($this->hasField($field)) {
				return true;
			}
		}
		return false;
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
		if ($this->_primaryKey === null) {
			$key = current((array)$this->schema()->primaryKey());
			$this->_primaryKey = $key ?: null;
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
			$class = App::classname($name, 'Model/Entity');
			$this->_entityClass = $class;
		}

		if (!$this->_entityClass) {
			throw new MissingEntityException([$name]);
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
 * Get the list of Behaviors loaded.
 *
 * This method will return the *aliases* of the behaviors attached
 * to this instance.
 *
 * @return array
 */
	public function behaviors() {
		return $this->_behaviors->loaded();
	}

/**
 * Check if a behavior with the given alias has been loaded.
 *
 * @param string $name The behavior alias to check.
 * @return array
 */
	public function hasBehavior($name) {
		return $this->_behaviors->loaded($name);
	}

/**
 * Returns a association objected configured for the specified alias if any
 *
 * @param string $name the alias used for the association
 * @return Cake\ORM\Association
 */
	public function association($name) {
		return $this->_associated->get($name);
	}

/**
 * Get the associations collection for this table.
 *
 * @return Cake\ORM\Associations
 */
	public function associations() {
		return $this->_associated;
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
		return $this->_associated->add($association->name(), $association);
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
		return $this->_associated->add($association->name(), $association);
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
		return $this->_associated->add($association->name(), $association);
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
 *   When true join/junction table records will be loaded and then deleted.
 * - conditions: array with a list of conditions to filter the join with
 * - sort: The order in which results for this association should be returned
 * - strategy: The strategy to be used for selecting results Either 'select'
 *   or 'subquery'. If subquery is selected the query used to return results
 *   in the source table will be used as conditions for getting rows in the
 *   target table.
 * - saveStrategy: Either 'append' or 'replace'. Indicates the mode to be used
 *   for saving associated entities. The former will only create new links
 *   between both side of the relation and the latter will do a wipe and
 *   replace to create the links between the passed entities when saving.
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
		return $this->_associated->add($association->name(), $association);
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
 * @param array $options An array that will be passed to Query::applyOptions
 * By default it allows the following keys:
 * - fields
 * - conditions
 * - order
 * - limit
 * - offset
 * - page
 * - order
 * - group
 * - having
 * - contain
 * - join
 * @return \Cake\ORM\Query
 */
	public function find($type = 'all', $options = []) {
		$query = $this->query();
		$query->select();
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
		$mapper = function($row, $key, $mapReduce) use ($options) {
			$rowKey = $options['idField'];
			$rowVal = $options['valueField'];
			if (!($options['groupField'])) {
				$mapReduce->emit($row[$rowVal], $row[$rowKey]);
				return;
			}

			$key = $row[$options['groupField']];
			$mapReduce->emitIntermediate([$row[$rowKey] => $row[$rowVal]], $key);
		};

		$reducer = function($values, $key, $mapReduce) {
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
		$hydrate = $query->hydrate();
		$mapper = function($row, $key, $mapReduce) use (&$parents) {
			$row['children'] = [];
			$parents[$row['id']] =& $row;
			$mapReduce->emitIntermediate($row['id'], $row['parent_id']);
		};

		$reducer = function($values, $key, $mapReduce) use (&$parents, $hydrate) {
			if (empty($key) || !isset($parents[$key])) {
				foreach ($values as $id) {
					$parents[$id] = $hydrate ? $parents[$id] : new \ArrayObject($parents[$id]);
					$mapReduce->emit($parents[$id]);
				}
				return;
			}

			foreach ($values as $id) {
				$parents[$key]['children'][] =& $parents[$id];
			}
		};

		$query->mapReduce($mapper, $reducer);
		if (!$hydrate) {
			$query->mapReduce(function($row, $key, $mapReduce) {
				$mapReduce->emit($row->getArrayCopy());
			});
		}

		return $query;
	}

/**
 * Returns a single record after finding it by its primary key, if no record is
 * found this method throws an exception.
 *
 * ###Example:
 *
 * {{{
 * $id = 10;
 * $article = $articles->get($id);
 *
 * $article = $articles->get($id, ['contain' => ['Comments]]);
 * }}}
 *
 * @param mixed primary key value to find
 * @param array $options options accepted by `Table::find()`
 * @throws Cake\ORM\Error\RecordNotFoundException if the record with such id
 * could not be found
 * @return \Cake\ORM\Entity
 * @see Table::find()
 */
	public function get($primaryKey, $options = []) {
		$key = (array)$this->primaryKey();
		$conditions = array_combine($key, (array)$primaryKey);
		$entity = $this->find('all', $options)->where($conditions)->first();

		if (!$entity) {
			throw new RecordNotFoundException(sprintf(
				'Record "%s" not found in table "%s"',
				implode(',', (array)$primaryKey),
				$this->table()
			));
		}

		return $entity;
	}

/**
 * Creates a new Query instance for this table
 *
 * @return \Cake\ORM\Query
 */
	public function query() {
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
		$query = $this->query();
		$query->update()
			->set($fields)
			->where($conditions);
		$statement = $query->execute();
		$success = $statement->rowCount() > 0;
		$statement->closeCursor();
		return $success;
	}

/**
 * Returns the validation rules tagged with $name. It is possible to have
 * multiple different named validation sets, this is useful when you need
 * to use varying rules when saving from different routines in your system.
 *
 * There are two different ways of creating and naming validation sets: by
 * creating a new method inside your own Table subclass, or by building
 * the validator object yourself and storing it using this method.
 *
 * For example, if you wish to create a validation set called 'forSubscription',
 * you will need to create a method in your Table subclass as follows:
 *
 * {{{
 * public function validationForSubscription($validator) {
 *	return $validator
 *	->add('email', 'valid-email', ['rule' => 'email'])
 *	->add('password', 'valid', ['rule' => 'notEmpty']);
 *	->validatePresence('username')
 * }
 * }}}
 *
 * Otherwise, you can build the object by yourself and store it in the Table object:
 *
 * {{{
 * $validator = new \Cake\Validation\Validator($table);
 * $validator
 *	->add('email', 'valid-email', ['rule' => 'email'])
 *	->add('password', 'valid', ['rule' => 'notEmpty'])
 *	->allowEmpty('bio');
 * $table->validator('forSubscription', $validator);
 * }}}
 *
 * You can implement the method in `validationDefault` in your Table subclass
 * should you wish to have a validation set that applies in cases where no other
 * set is specified.
 *
 * @param string $name the name of the validation set to return
 * @param \Cake\Validation\Validator $validator
 * @return \Cake\Validation\Validator
 */
	public function validator($name = 'default', Validator $instance = null) {
		if ($instance === null && isset($this->_validators[$name])) {
			return $this->_validators[$name];
		}

		if ($instance === null) {
			$instance = new Validator();
			$instance = $this->{'validation' . ucfirst($name)}($instance);
		}

		$instance->provider('table', $this);
		return $this->_validators[$name] = $instance;
	}

/**
 * Returns the default validator object. Subclasses can override this function
 * to add a default validation set to the validator object.
 *
 * @param \Cake\Validation\Validator $validator The validator that can be modified to
 * add some rules to it.
 * @return \Cake\Validation\Validator
 */
	public function validationDefault(Validator $validator) {
		return $validator;
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
		$query = $this->query();
		$query->delete()
			->where($conditions);
		$statement = $query->execute();
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
 * Persists an entity based on the fields that are marked as dirty and
 * returns the same entity after a successful save or false in case
 * of any error.
 *
 * ### Options
 *
 * The options array can receive the following keys:
 *
 * - atomic: Whether to execute the save and callbacks inside a database
 * transaction (default: true)
 * - validate: Whether or not validate the entity before saving, if validation
 * fails, it will abort the save operation. If this key is set to a string value,
 * the validator object registered in this table under the provided name will be
 * used instead of the default one. (default:true)
 * - associated: If true it will save all associated entities as they are found
 * in the passed `$entity` whenever the property defined for the association
 * is marked as dirty. Associated records are saved recursively unless told
 * otherwise. If an array, it will be interpreted as the list of associations
 * to be saved. It is possible to provide different options for saving on associated
 * table objects using this key by making the custom options the array value.
 * If false no associated records will be saved. (default: true)
 *
 * ### Events
 *
 * When saving, this method will trigger four events:
 *
 * - Model.beforeValidate: Will be triggered right before any validation is done
 * for the passed entity if the validate key in $options is not set to false.
 * Listeners will receive as arguments the entity, the options array and the
 * validation object to be used for validating the entity. If the event is
 * stopped the validation result will be set to the result of the event itself.
 * - Model.afterValidate: Will be triggered right after the `validate()` method is
 * called in the entity. Listeners will receive as arguments the entity, the
 * options array and the validation object to be used for validating the entity.
 * If the event is stopped the validation result will be set to the result of
 * the event itself.
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
 * ### Saving on associated tables
 *
 * This method will by default persist entities belonging to associated tables,
 * whenever a dirty property matching the name of the property name set for an
 * association in this table. It is possible to control what associations will
 * be saved and to pass additional option for saving them.
 *
 * {{{
 * // Only save the comments association
 * $articles->save($entity, ['associated' => ['Comments']);
 *
 * // Save the company, the employees and related addresses for each of them.
 * // For employees use the 'special' validation group
 * $companies->save($entity, [
 *   'associated' => [
 *     'Employees' => [
 *       'associated' => ['Addresses'],
 *       'validate' => 'special'
 *     ]
 *   ]
 * ]);
 *
 * // Save no associations
 * $articles->save($entity, ['associated' => false]);
 * }}}
 *
 * @param \Cake\ORM\Entity the entity to be saved
 * @param array $options
 * @return \Cake\ORM\Entity|boolean
 */
	public function save(Entity $entity, array $options = []) {
		$options = new \ArrayObject($options + [
			'atomic' => true,
			'validate' => true,
			'associated' => true
		]);

		if ($entity->isNew() === false && !$entity->dirty()) {
			return $entity;
		}

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

		if ($options['associated'] === true) {
			$options['associated'] = $this->_associated->keys();
		}
		$associated = array_filter((array)$options['associated']);
		$options['associated'] = [];

		if (!$this->validate($entity, $options)) {
			return false;
		}

		$options['associated'] = $associated;
		$event = new Event('Model.beforeSave', $this, compact('entity', 'options'));
		$this->getEventManager()->dispatch($event);

		if ($event->isStopped()) {
			return $event->result;
		}

		$saved = $this->_associated->saveParents(
			$this,
			$entity,
			$options['associated'],
			$options->getArrayCopy()
		);

		if (!$saved && $options['atomic']) {
			return false;
		}

		$data = $entity->extract($this->schema()->columns(), true);
		$keys = array_keys($data);
		$isNew = $entity->isNew();

		if ($isNew) {
			$success = $this->_insert($entity, $data);
		} else {
			$success = $this->_update($entity, $data);
		}

		if ($success) {
			$success = $this->_associated->saveChildren(
				$this,
				$entity,
				$options['associated'],
				$options->getArrayCopy()
			);
			if ($success || !$options['atomic']) {
				$entity->clean();
				$event = new Event('Model.afterSave', $this, compact('entity', 'options'));
				$this->getEventManager()->dispatch($event);
				$entity->isNew(false);
				$success = true;
			}
		}

		if (!$success && $isNew) {
			$entity->unsetProperty($this->primaryKey());
			$entity->isNew(true);
		}
		if ($success) {
			return $entity;
		}
		return false;
	}

/**
 * Auxiliary function to handle the insert of an entity's data in the table
 *
 * @param \Cake\ORM\Entity the subject entity from were $data was extracted
 * @param array $data The actual data that needs to be saved
 * @return \Cake\ORM\Entity|boolean
 */
	protected function _insert($entity, $data) {
		$query = $this->query();

		$primary = $this->primaryKey();
		$id = $this->_newId($primary);
		if ($id !== null) {
			$data[$primary] = $id;
		}

		$statement = $query->insert(array_keys($data))
			->values($data)
			->execute();

		$success = false;
		if ($statement->rowCount() > 0) {
			if ($primary && !isset($data[$primary])) {
				$id = $statement->lastInsertId($this->table(), $primary);
			}
			if ($primary && $id !== null) {
				$entity->set($primary, $id);
			}
			$success = $entity;
		}
		$statement->closeCursor();
		return $success;
	}

/**
 * Generate a primary key value for a new record.
 *
 * By default, this uses the type system to generate a new primary key
 * value if possible. You can override this method if you have specific requirements
 * for id generation.
 *
 * @param string $primary The primary key column to get a new ID for.
 * @return null|mixed Either null or the new primary key value.
 */
	protected function _newId($primary) {
		if (!$primary) {
			return null;
		}
		$typeName = $this->schema()->columnType($primary);
		$type = Type::build($typeName);
		return $type->newId();
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
			$message = 'A primary key value is needed for updating';
			throw new \InvalidArgumentException($message);
		}

		$query = $this->query();
		$statement = $query->update()
			->set($data)
			->where($primaryKey)
			->execute();

		$success = false;
		if ($statement->rowCount() > 0) {
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
 * @throws \InvalidArgumentException if there are no primary key values of the
 * passed entity
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

		if (!array_filter($conditions, 'strlen')) {
			$msg = 'Deleting requires a primary key value';
			throw new \InvalidArgumentException($msg);
		}

		$query = $this->query();
		$statement = $query->delete()
			->where($conditions)
			->execute();

		$success = $statement->rowCount() > 0;
		if (!$success) {
			return $success;
		}

		$this->_associated->cascadeDelete($entity, $options->getArrayCopy());

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
		$query->applyOptions($options);
		$options = $query->getOptions();
		$finder = 'find' . ucfirst($type);
		if (method_exists($this, $finder)) {
			return $this->{$finder}($query, $options);
		}

		if ($this->_behaviors && $this->_behaviors->hasFinder($type)) {
			return $this->_behaviors->callFinder($type, [$query, $options]);
		}

		throw new \BadMethodCallException(
			sprintf('Unknown finder method "%s"', $type)
		);
	}

/**
 * Provides the dynamic findBy and findByAll methods.
 *
 * @param string $method The method name that was fired.
 * @param array $args List of arguments passed to the function.
 * @return mixed.
 * @throws Cake\Error\Exception when there are missing arguments, or when
 *  and & or are combined.
 */
	protected function _dynamicFinder($method, $args) {
		$method = Inflector::underscore($method);
		preg_match('/^find_([\w]+)_by_/', $method, $matches);
		if (empty($matches)) {
			// find_by_ is 8 characters.
			$fields = substr($method, 8);
			$findType = 'all';
		} else {
			$fields = substr($method, strlen($matches[0]));
			$findType = Inflector::variable($matches[1]);
		}
		$conditions = [];
		$hasOr = strpos($fields, '_or_');
		$hasAnd = strpos($fields, '_and_');

		$makeConditions = function($fields, $args) {
			$conditions = [];
			if (count($args) < count($fields)) {
				throw new \Cake\Error\Exception(sprintf(
					'Not enough arguments to magic finder. Got %s required %s',
					count($args),
					count($fields)
				));
			}
			foreach ($fields as $field) {
				$conditions[$field] = array_shift($args);
			}
			return $conditions;
		};

		if ($hasOr !== false && $hasAnd !== false) {
			throw new \Cake\Error\Exception(
				'Cannot mix "and" & "or" in a magic finder. Use find() instead.'
			);
		}

		if ($hasOr === false && $hasAnd === false) {
			$conditions = $makeConditions([$fields], $args);
		} elseif ($hasOr !== false) {
			$fields = explode('_or_', $fields);
			$conditions = [
				'OR' => $makeConditions($fields, $args)
			];
		} elseif ($hasAnd !== false) {
			$fields = explode('_and_', $fields);
			$conditions = $makeConditions($fields, $args);
		}

		return $this->find($findType, [
			'conditions' => $conditions,
		]);
	}

/**
 * Handles behavior delegation + dynamic finders.
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
		if (preg_match('/^find(?:\w+)?By/', $method) > 0) {
			return $this->_dynamicFinder($method, $args);
		}

		throw new \BadMethodCallException(
			sprintf('Unknown method "%s"', $method)
		);
	}

/**
 * Get the object used to marshal/convert array data into objects.
 *
 * Override this method if you want a table object to use custom
 * marshalling logic.
 *
 * @param boolean $safe Whether or not this marshaller
 *   should be in safe mode.
 * @return Cake\ORM\Marhsaller;
 * @see Cake\ORM\Marshaller
 */
	public function marshaller($safe = false) {
		return new Marshaller($this, $safe);
	}

/**
 * Returns a new instance of an EntityValidator that is configured to be used
 * for entities generated by this table. An EntityValidator can be used to
 * process validation rules on a single or multiple entities and any of its
 * associated values.
 *
 * @return EntityValidator
 */
	public function entityValidator() {
		return new EntityValidator($this);
	}

/**
 * Create a new entity + associated entities from an array.
 *
 * This is most useful when hydrating request data back into entities.
 * For example, in your controller code:
 *
 * {{{
 * $article = $this->Articles->newEntity($this->request->data());
 * }}}
 *
 * The hydrated entity will correctly do an insert/update based
 * on the primary key data existing in the database when the entity
 * is saved. Until the entity is saved, it will be a detached record.
 *
 * By default all the associations on this table will be hydrated. You can
 * limit which associations are built, or include deeper associations
 * using the associations parameter:
 *
 * {{{
 * $articles = $this->Articles->newEntity(
 *   $this->request->data(),
 *   ['Tags', 'Comments' => ['associated' => ['Users']]]
 * );
 * }}}
 *
 * @param array $data The data to build an entity with.
 * @param array $associations A whitelist of associations
 *   to hydrate. Defaults to all associations
 */
	public function newEntity(array $data, $associations = null) {
		if ($associations === null) {
			$associations = $this->_associated->keys();
		}
		$marshaller = $this->marshaller();
		return $marshaller->one($data, $associations);
	}

/**
 * Create a list of entities + associated entities from an array.
 *
 * This is most useful when hydrating request data back into entities.
 * For example, in your controller code:
 *
 * {{{
 * $articles = $this->Articles->newEntities($this->request->data());
 * }}}
 *
 * The hydrated entities can then be iterated and saved. By default
 * all the associations on this table will be hydrated. You can
 * limit which associations are built, or include deeper associations
 * using the associations parameter:
 *
 * {{{
 * $articles = $this->Articles->newEntities(
 *   $this->request->data(),
 *   ['Tags', 'Comments' => ['associated' => ['Users']]]
 * );
 * }}}
 *
 * @param array $data The data to build an entity with.
 * @param array $associations A whitelist of associations
 *   to hydrate. Defaults to all associations
 */
	public function newEntities(array $data, $associations = null) {
		if ($associations === null) {
			$associations = $this->_associated->keys();
		}
		$marshaller = $this->marshaller();
		return $marshaller->many($data, $associations);
	}

/**
 * Validates a single entity based on the passed options and validates
 * any nested entity for this table associations as requested in the
 * options array.
 *
 * Calling this function directly is mostly useful when you need to get
 * validation errors for an entity and associated nested entities before
 * they are saved.
 *
 * {{{
 * $articles->validate($article);
 * }}}
 *
 * You can specify which validation set to use using the options array:
 *
 * {{{
 * $users->validate($user, ['validate' => 'forSignup']);
 * }}}
 *
 * By default all the associations on this table will be validated if they can
 * be found in the passed entity. You can limit which associations are built,
 * or include deeper associations using the options parameter
 *
 * {{{
 * $articles->validate($article, [
 *	'associated' => [
 *		'Tags',
 *		'Comments' => [
 *			'validate' => 'myCustomSet',
 *			'associated' => ['Users']
 *		]
 *	]
 * ]);
 * }}}
 *
 * @param \Cake\ORM\Entity $entity The entity to be validated
 * @param array $options A list of options to use while validating, the following
 * keys are accepted:
 * - validate: The name of the validation set to use
 * - associated: map of association names to validate as well
 * @return boolean true if the passed entity and its associations are valid
 */
	public function validate($entity, $options = []) {
		if (!isset($options['associated'])) {
			$options['associated'] = $this->_associated->keys();
		}

		$entityValidator = $this->entityValidator();
		return $entityValidator->one($entity, $options);
	}

/**
 * Validates a list of entities based on the passed options and validates
 * any nested entity for this table associations as requested in the
 * options array.
 *
 * Calling this function directly is mostly useful when you need to get
 * validation errors for a list of entities and associations before they are
 * saved.
 *
 * {{{
 * $articles->validateMany([$article1, $article2]);
 * }}}
 *
 * You can specify which validation set to use using the options array:
 *
 * {{{
 * $users->validateMany([$user1, $user2], ['validate' => 'forSignup']);
 * }}}
 *
 * By default all the associations on this table will be validated if they can
 * be found in the passed entities. You can limit which associations are built,
 * or include deeper associations using the options parameter
 *
 * {{{
 * $articles->validateMany([$article1, $article2], [
 *	'associated' => [
 *		'Tags',
 *		'Comments' => [
 *			'validate' => 'myCustomSet',
 *			'associated' => ['Users']
 *		]
 *	]
 * ]);
 * }}}
 *
 * @param array $entities The entities to be validated
 * @param array $options A list of options to use while validating, the following
 * keys are accepted:
 * - validate: The name of the validation set to use
 * - associated: map of association names to validate as well
 * @return boolean true if the passed entities and their associations are valid
 */
	public function validateMany($entities, $options = []) {
		if (!isset($options['associated'])) {
			$options['associated'] = $this->_associated->keys();
		}

		$entityValidator = $this->entityValidator();
		return $entityValidator->many($entities, $options);
	}

/**
 * Get the Model callbacks this table is interested in.
 *
 * By implementing the conventional methods a table class is assumed
 * to be interested in the related event.
 *
 * Override this method if you need to add non-conventional event listeners.
 * Or if you want you table to listen to non-standard events.
 *
 * @return array
 */
	public function implementedEvents() {
		$eventMap = [
			'Model.beforeFind' => 'beforeFind',
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
			'Model.beforeDelete' => 'beforeDelete',
			'Model.afterDelete' => 'afterDelete',
			'Model.beforeValidate' => 'beforeValidate',
			'Model.afterValidate' => 'afterValidate',
		];
		$events = [];

		foreach ($eventMap as $event => $method) {
			if (!method_exists($this, $method)) {
				continue;
			}
			$events[$event] = $method;
		}
		return $events;
	}

}
