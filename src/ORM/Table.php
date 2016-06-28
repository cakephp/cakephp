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
namespace Cake\ORM;

use ArrayObject;
use BadMethodCallException;
use Cake\Core\App;
use Cake\Database\Schema\Table as Schema;
use Cake\Database\Type;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\RulesAwareTrait;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Exception\MissingEntityException;
use Cake\ORM\Rule\IsUnique;
use Cake\Utility\Inflector;
use Cake\Validation\Validation;
use Cake\Validation\ValidatorAwareTrait;
use InvalidArgumentException;
use RuntimeException;

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
 * ```
 * $query = $users->findByUsername('mark');
 * ```
 *
 * You can also combine conditions on multiple fields using either `Or` or `And`:
 *
 * ```
 * $query = $users->findByUsernameOrEmail('mark', 'mark@example.org');
 * ```
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
 * - `beforeFind(Event $event, Query $query, ArrayObject $options, boolean $primary)`
 *   Fired before each find operation. By stopping the event and supplying a
 *   return value you can bypass the find operation entirely. Any changes done
 *   to the $query instance will be retained for the rest of the find. The
 *   $primary parameter indicates whether or not this is the root query,
 *   or an associated query.
 *
 * - `buildValidator(Event $event, Validator $validator, string $name)`
 *   Allows listeners to modify validation rules for the provided named validator.
 *
 * - `buildRules(Event $event, RulesChecker $rules)`
 *   Allows listeners to modify the rules checker by adding more rules.
 *
 * - `beforeRules(Event $event, EntityInterface $entity, ArrayObject $options, string $operation)`
 *   Fired before an entity is validated using the rules checker. By stopping this event,
 *   you can return the final value of the rules checking operation.
 *
 * - `afterRules(Event $event, EntityInterface $entity, ArrayObject $options, bool $result, string $operation)`
 *   Fired after the rules have been checked on the entity. By stopping this event,
 *   you can return the final value of the rules checking operation.
 *
 * - `beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired before each entity is saved. Stopping this event will abort the save
 *   operation. When the event is stopped the result of the event will be returned.
 *
 * - `afterSave(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after an entity is saved.
 *
 * - `afterSaveCommit(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after the transaction in which the save operation is wrapped has been committed.
 *   It’s also triggered for non atomic saves where database operations are implicitly committed.
 *   The event is triggered only for the primary table on which save() is directly called.
 *   The event is not triggered if a transaction is started before calling save.
 *
 * - `beforeDelete(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired before an entity is deleted. By stopping this event you will abort
 *   the delete operation.
 *
 * - `afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)`
 *   Fired after an entity has been deleted.
 *
 * @see \Cake\Event\EventManager for reference on the events system.
 */
class Table implements RepositoryInterface, EventListenerInterface, EventDispatcherInterface
{

    use EventDispatcherTrait;
    use RulesAwareTrait;
    use ValidatorAwareTrait;

    /**
     * Name of default validation set.
     *
     * @var string
     */
    const DEFAULT_VALIDATOR = 'default';

    /**
     * The alias this object is assigned to validators as.
     *
     * @var string
     */
    const VALIDATOR_PROVIDER_NAME = 'table';

    /**
     * The rules class name that is used.
     *
     * @var string
     */
    const RULES_CLASS = 'Cake\ORM\RulesChecker';

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
     * @var \Cake\Datasource\ConnectionInterface
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
     * @var string|array
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
     * @var \Cake\ORM\AssociationCollection
     */
    protected $_associations;

    /**
     * BehaviorRegistry for this table
     *
     * @var \Cake\ORM\BehaviorRegistry
     */
    protected $_behaviors;

    /**
     * The name of the class that represent a single row for this table
     *
     * @var string
     */
    protected $_entityClass;

    /**
     * Registry key used to create this table object
     *
     * @var string
     */
    protected $_registryAlias;

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
     * - associations: An AssociationCollection instance.
     * - validator: A Validator instance which is assigned as the "default"
     *   validation set, or an associative array, where key is the name of the
     *   validation set and value the Validator instance.
     *
     * @param array $config List of options for this table
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['registryAlias'])) {
            $this->registryAlias($config['registryAlias']);
        }
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
        $eventManager = $behaviors = $associations = null;
        if (!empty($config['eventManager'])) {
            $eventManager = $config['eventManager'];
        }
        if (!empty($config['behaviors'])) {
            $behaviors = $config['behaviors'];
        }
        if (!empty($config['associations'])) {
            $associations = $config['associations'];
        }
        if (!empty($config['validator'])) {
            if (!is_array($config['validator'])) {
                $this->validator(static::DEFAULT_VALIDATOR, $config['validator']);
            } else {
                foreach ($config['validator'] as $name => $validator) {
                    $this->validator($name, $validator);
                }
            }
        }
        $this->_eventManager = $eventManager ?: new EventManager();
        $this->_behaviors = $behaviors ?: new BehaviorRegistry();
        $this->_behaviors->setTable($this);
        $this->_associations = $associations ?: new AssociationCollection();

        $this->initialize($config);
        $this->_eventManager->on($this);
        $this->dispatchEvent('Model.initialize');
    }

    /**
     * Get the default connection name.
     *
     * This method is used to get the fallback connection name if an
     * instance is created through the TableRegistry without a connection.
     *
     * @return string
     * @see \Cake\ORM\TableRegistry::get()
     */
    public static function defaultConnectionName()
    {
        return 'default';
    }

    /**
     * Initialize a table instance. Called after the constructor.
     *
     * You can use this method to define associations, attach behaviors
     * define validation and do any other initialization logic you need.
     *
     * ```
     *  public function initialize(array $config)
     *  {
     *      $this->belongsTo('Users');
     *      $this->belongsToMany('Tagging.Tags');
     *      $this->primaryKey('something_else');
     *  }
     * ```
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config)
    {
    }

    /**
     * Returns the database table name or sets a new one
     *
     * @param string|null $table the new table name
     * @return string
     */
    public function table($table = null)
    {
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
     * {@inheritDoc}
     */
    public function alias($alias = null)
    {
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
     * Alias a field with the table's current alias.
     *
     * @param string $field The field to alias.
     * @return string The field prefixed with the table alias.
     */
    public function aliasField($field)
    {
        return $this->alias() . '.' . $field;
    }

    /**
     * Returns the table registry key used to create this table instance
     *
     * @param string|null $registryAlias the key used to access this object
     * @return string
     */
    public function registryAlias($registryAlias = null)
    {
        if ($registryAlias !== null) {
            $this->_registryAlias = $registryAlias;
        }
        if ($this->_registryAlias === null) {
            $this->_registryAlias = $this->alias();
        }
        return $this->_registryAlias;
    }

    /**
     * Returns the connection instance or sets a new one
     *
     * @param \Cake\Datasource\ConnectionInterface|null $conn The new connection instance
     * @return \Cake\Datasource\ConnectionInterface
     */
    public function connection(ConnectionInterface $conn = null)
    {
        if ($conn === null) {
            return $this->_connection;
        }

        return $this->_connection = $conn;
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
     * @param array|\Cake\Database\Schema\Table|null $schema New schema to be used for this table
     * @return \Cake\Database\Schema\Table
     */
    public function schema($schema = null)
    {
        if ($schema === null) {
            if ($this->_schema === null) {
                $this->_schema = $this->_initializeSchema(
                    $this->connection()
                        ->schemaCollection()
                        ->describe($this->table())
                );
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
     * Override this function in order to alter the schema used by this table.
     * This function is only called after fetching the schema out of the database.
     * If you wish to provide your own schema to this table without touching the
     * database, you can override schema() or inject the definitions though that
     * method.
     *
     * ### Example:
     *
     * ```
     * protected function _initializeSchema(\Cake\Database\Schema\Table $table) {
     *  $table->columnType('preferences', 'json');
     *  return $table;
     * }
     * ```
     *
     * @param \Cake\Database\Schema\Table $table The table definition fetched from database.
     * @return \Cake\Database\Schema\Table the altered schema
     * @api
     */
    protected function _initializeSchema(Schema $table)
    {
        return $table;
    }

    /**
     * Test to see if a Table has a specific field/column.
     *
     * Delegates to the schema object and checks for column presence
     * using the Schema\Table instance.
     *
     * @param string $field The field to check for.
     * @return bool True if the field exists, false if it does not.
     */
    public function hasField($field)
    {
        $schema = $this->schema();
        return $schema->column($field) !== null;
    }

    /**
     * Returns the primary key field name or sets a new one
     *
     * @param string|array|null $key sets a new name to be used as primary key
     * @return string|array
     */
    public function primaryKey($key = null)
    {
        if ($key !== null) {
            $this->_primaryKey = $key;
        }
        if ($this->_primaryKey === null) {
            $key = (array)$this->schema()->primaryKey();
            if (count($key) === 1) {
                $key = $key[0];
            }
            $this->_primaryKey = $key;
        }
        return $this->_primaryKey;
    }

    /**
     * Returns the display field or sets a new one
     *
     * @param string|null $key sets a new name to be used as display field
     * @return string
     */
    public function displayField($key = null)
    {
        if ($key !== null) {
            $this->_displayField = $key;
        }
        if ($this->_displayField === null) {
            $schema = $this->schema();
            $primary = (array)$this->primaryKey();
            $this->_displayField = array_shift($primary);
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
     * @param string|null $name the name of the class to use
     * @throws \Cake\ORM\Exception\MissingEntityException when the entity class cannot be found
     * @return string
     */
    public function entityClass($name = null)
    {
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
            $class = App::className($name, 'Model/Entity');
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
     * ```
     * $this->addBehavior('Tree', ['parent' => 'parentId']);
     * ```
     *
     * Behaviors are generally loaded during Table::initialize().
     *
     * @param string $name The name of the behavior. Can be a short class reference.
     * @param array $options The options for the behavior to use.
     * @return void
     * @throws \RuntimeException If a behavior is being reloaded.
     * @see \Cake\ORM\Behavior
     */
    public function addBehavior($name, array $options = [])
    {
        $this->_behaviors->load($name, $options);
    }

    /**
     * Removes a behavior from this table's behavior registry.
     *
     * Example:
     *
     * Remove a behavior from this table.
     *
     * ```
     * $this->removeBehavior('Tree');
     * ```
     *
     * @param string $name The alias that the behavior was added with.
     * @return void
     * @see \Cake\ORM\Behavior
     */
    public function removeBehavior($name)
    {
        $this->_behaviors->unload($name);
    }

    /**
     * Returns the behavior registry for this table.
     *
     * @return \Cake\ORM\BehaviorRegistry The BehaviorRegistry instance.
     */
    public function behaviors()
    {
        return $this->_behaviors;
    }

    /**
     * Check if a behavior with the given alias has been loaded.
     *
     * @param string $name The behavior alias to check.
     * @return bool Whether or not the behavior exists.
     */
    public function hasBehavior($name)
    {
        return $this->_behaviors->has($name);
    }

    /**
     * Returns an association object configured for the specified alias if any
     *
     * @param string $name the alias used for the association.
     * @return \Cake\ORM\Association|null Either the association or null.
     */
    public function association($name)
    {
        return $this->_associations->get($name);
    }

    /**
     * Get the associations collection for this table.
     *
     * @return \Cake\ORM\AssociationCollection The collection of association objects.
     */
    public function associations()
    {
        return $this->_associations;
    }

    /**
     * Setup multiple associations.
     *
     * It takes an array containing set of table names indexed by association type
     * as argument:
     *
     * ```
     * $this->Posts->addAssociations([
     *   'belongsTo' => [
     *     'Users' => ['className' => 'App\Model\Table\UsersTable']
     *   ],
     *   'hasMany' => ['Comments'],
     *   'belongsToMany' => ['Tags']
     * ]);
     * ```
     *
     * Each association type accepts multiple associations where the keys
     * are the aliases, and the values are association config data. If numeric
     * keys are used the values will be treated as association aliases.
     *
     * @param array $params Set of associations to bind (indexed by association type)
     * @return void
     * @see \Cake\ORM\Table::belongsTo()
     * @see \Cake\ORM\Table::hasOne()
     * @see \Cake\ORM\Table::hasMany()
     * @see \Cake\ORM\Table::belongsToMany()
     */
    public function addAssociations(array $params)
    {
        foreach ($params as $assocType => $tables) {
            foreach ($tables as $associated => $options) {
                if (is_numeric($associated)) {
                    $associated = $options;
                    $options = [];
                }
                $this->{$assocType}($associated, $options);
            }
        }
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
     * - strategy: The loading strategy to use. 'join' and 'select' are supported.
     * - finder: The finder method to use when loading records from this association.
     *   Defaults to 'all'. When the strategy is 'join', only the fields, containments,
     *   and where conditions will be used from the finder.
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return \Cake\ORM\Association\BelongsTo
     */
    public function belongsTo($associated, array $options = [])
    {
        $options += ['sourceTable' => $this];
        $association = new BelongsTo($associated, $options);
        return $this->_associations->add($association->name(), $association);
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
     *   associated table when an entity is removed on this table. The delete operation
     *   on the associated table will not cascade further. To get recursive cascades enable
     *   `cascadeCallbacks` as well. Set to false if you don't want CakePHP to remove
     *   associated data, or when you are using database constraints.
     * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
     *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
     *   When true records will be loaded and then deleted.
     * - conditions: array with a list of conditions to filter the join with
     * - joinType: The type of join to be used (e.g. LEFT)
     * - strategy: The loading strategy to use. 'join' and 'select' are supported.
     * - finder: The finder method to use when loading records from this association.
     *   Defaults to 'all'. When the strategy is 'join', only the fields, containments,
     *   and where conditions will be used from the finder.
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return \Cake\ORM\Association\HasOne
     */
    public function hasOne($associated, array $options = [])
    {
        $options += ['sourceTable' => $this];
        $association = new HasOne($associated, $options);
        return $this->_associations->add($association->name(), $association);
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
     *   associated table when an entity is removed on this table. The delete operation
     *   on the associated table will not cascade further. To get recursive cascades enable
     *   `cascadeCallbacks` as well. Set to false if you don't want CakePHP to remove
     *   associated data, or when you are using database constraints.
     * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
     *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
     *   When true records will be loaded and then deleted.
     * - conditions: array with a list of conditions to filter the join with
     * - sort: The order in which results for this association should be returned
     * - saveStrategy: Either 'append' or 'replace'. When 'append' the current records
     *   are appended to any records in the database. When 'replace' associated records
     *   not in the current set will be removed. If the foreign key is a null able column
     *   or if `dependent` is true records will be orphaned.
     * - strategy: The strategy to be used for selecting results Either 'select'
     *   or 'subquery'. If subquery is selected the query used to return results
     *   in the source table will be used as conditions for getting rows in the
     *   target table.
     * - finder: The finder method to use when loading records from this association.
     *   Defaults to 'all'.
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return \Cake\ORM\Association\HasMany
     */
    public function hasMany($associated, array $options = [])
    {
        $options += ['sourceTable' => $this];
        $association = new HasMany($associated, $options);
        return $this->_associations->add($association->name(), $association);
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
     * - className: The class name of the target table object.
     * - targetTable: An instance of a table object to be used as the target table.
     * - foreignKey: The name of the field to use as foreign key.
     * - targetForeignKey: The name of the field to use as the target foreign key.
     * - joinTable: The name of the table representing the link between the two
     * - through: If you choose to use an already instantiated link table, set this
     *   key to a configured Table instance containing associations to both the source
     *   and target tables in this association.
     * - dependent: Set to false, if you do not want junction table records removed
     *   when an owning record is removed.
     * - cascadeCallbacks: Set to true if you want CakePHP to fire callbacks on
     *   cascaded deletes. If false the ORM will use deleteAll() to remove data.
     *   When true join/junction table records will be loaded and then deleted.
     * - conditions: array with a list of conditions to filter the join with.
     * - sort: The order in which results for this association should be returned.
     * - strategy: The strategy to be used for selecting results Either 'select'
     *   or 'subquery'. If subquery is selected the query used to return results
     *   in the source table will be used as conditions for getting rows in the
     *   target table.
     * - saveStrategy: Either 'append' or 'replace'. Indicates the mode to be used
     *   for saving associated entities. The former will only create new links
     *   between both side of the relation and the latter will do a wipe and
     *   replace to create the links between the passed entities when saving.
     * - strategy: The loading strategy to use. 'select' and 'subquery' are supported.
     * - finder: The finder method to use when loading records from this association.
     *   Defaults to 'all'.
     *
     * This method will return the association object that was built.
     *
     * @param string $associated the alias for the target table. This is used to
     * uniquely identify the association
     * @param array $options list of options to configure the association definition
     * @return \Cake\ORM\Association\BelongsToMany
     */
    public function belongsToMany($associated, array $options = [])
    {
        $options += ['sourceTable' => $this];
        $association = new BelongsToMany($associated, $options);
        return $this->_associations->add($association->name(), $association);
    }

    /**
     * {@inheritDoc}
     *
     * ### Model.beforeFind event
     *
     * Each find() will trigger a `Model.beforeFind` event for all attached
     * listeners. Any listener can set a valid result set using $query
     *
     * By default, `$options` will recognize the following keys:
     *
     * - fields
     * - conditions
     * - order
     * - limit
     * - offset
     * - page
     * - group
     * - having
     * - contain
     * - join
     *
     * ### Usage
     *
     * Using the options array:
     *
     * ```
     * $query = $articles->find('all', [
     *   'conditions' => ['published' => 1],
     *   'limit' => 10,
     *   'contain' => ['Users', 'Comments']
     * ]);
     * ```
     *
     * Using the builder interface:
     *
     * ```
     * $query = $articles->find()
     *   ->where(['published' => 1])
     *   ->limit(10)
     *   ->contain(['Users', 'Comments']);
     * ```
     *
     * ### Calling finders
     *
     * The find() method is the entry point for custom finder methods.
     * You can invoke a finder by specifying the type:
     *
     * ```
     * $query = $articles->find('published');
     * ```
     *
     * Would invoke the `findPublished` method.
     *
     * @return \Cake\ORM\Query The query builder
     */
    public function find($type = 'all', $options = [])
    {
        $query = $this->query();
        $query->select();
        return $this->callFinder($type, $query, $options);
    }

    /**
     * Returns the query as passed.
     *
     * By default findAll() applies no conditions, you
     * can override this method in subclasses to modify how `find('all')` works.
     *
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options to use for the find
     * @return \Cake\ORM\Query The query builder
     */
    public function findAll(Query $query, array $options)
    {
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
     * ```
     * [
     *  1 => 'value for id 1',
     *  2 => 'value for id 2',
     *  4 => 'value for id 4'
     * ]
     * ```
     *
     * You can specify which property will be used as the key and which as value
     * by using the `$options` array, when not specified, it will use the results
     * of calling `primaryKey` and `displayField` respectively in this table:
     *
     * ```
     * $table->find('list', [
     *  'keyField' => 'name',
     *  'valueField' => 'age'
     * ]);
     * ```
     *
     * Results can be put together in bigger groups when they share a property, you
     * can customize the property to use for grouping by setting `groupField`:
     *
     * ```
     * $table->find('list', [
     *  'groupField' => 'category_id',
     * ]);
     * ```
     *
     * When using a `groupField` results will be returned in this format:
     *
     * ```
     * [
     *  'group_1' => [
     *      1 => 'value for id 1',
     *      2 => 'value for id 2',
     *  ]
     *  'group_2' => [
     *      4 => 'value for id 4'
     *  ]
     * ]
     * ```
     *
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options for the find
     * @return \Cake\ORM\Query The query builder
     */
    public function findList(Query $query, array $options)
    {
        $options += [
            'keyField' => $this->primaryKey(),
            'valueField' => $this->displayField(),
            'groupField' => null
        ];

        if (isset($options['idField'])) {
            $options['keyField'] = $options['idField'];
            unset($options['idField']);
            trigger_error('Option "idField" is deprecated, use "keyField" instead.', E_USER_DEPRECATED);
        }

        if (!$query->clause('select') &&
            !is_object($options['keyField']) &&
            !is_object($options['valueField']) &&
            !is_object($options['groupField'])
        ) {
            $fields = array_merge(
                (array)$options['keyField'],
                (array)$options['valueField'],
                (array)$options['groupField']
            );
            $columns = $this->schema()->columns();
            if (count($fields) === count(array_intersect($fields, $columns))) {
                $query->select($fields);
            }
        }

        $options = $this->_setFieldMatchers(
            $options,
            ['keyField', 'valueField', 'groupField']
        );

        return $query->formatResults(function ($results) use ($options) {
            return $results->combine(
                $options['keyField'],
                $options['valueField'],
                $options['groupField']
            );
        });
    }

    /**
     * Results for this finder will be a nested array, and is appropriate if you want
     * to use the parent_id field of your model data to build nested results.
     *
     * Values belonging to a parent row based on their parent_id value will be
     * recursively nested inside the parent row values using the `children` property
     *
     * You can customize what fields are used for nesting results, by default the
     * primary key and the `parent_id` fields are used. If you wish to change
     * these defaults you need to provide the keys `keyField` or `parentField` in
     * `$options`:
     *
     * ```
     * $table->find('threaded', [
     *  'keyField' => 'id',
     *  'parentField' => 'ancestor_id'
     * ]);
     * ```
     *
     * @param \Cake\ORM\Query $query The query to find with
     * @param array $options The options to find with
     * @return \Cake\ORM\Query The query builder
     */
    public function findThreaded(Query $query, array $options)
    {
        $options += [
            'keyField' => $this->primaryKey(),
            'parentField' => 'parent_id',
        ];

        if (isset($options['idField'])) {
            $options['keyField'] = $options['idField'];
            unset($options['idField']);
            trigger_error('Option "idField" is deprecated, use "keyField" instead.', E_USER_DEPRECATED);
        }

        $options = $this->_setFieldMatchers($options, ['keyField', 'parentField']);

        return $query->formatResults(function ($results) use ($options) {
            return $results->nest($options['keyField'], $options['parentField']);
        });
    }

    /**
     * Out of an options array, check if the keys described in `$keys` are arrays
     * and change the values for closures that will concatenate the each of the
     * properties in the value array when passed a row.
     *
     * This is an auxiliary function used for result formatters that can accept
     * composite keys when comparing values.
     *
     * @param array $options the original options passed to a finder
     * @param array $keys the keys to check in $options to build matchers from
     * the associated value
     * @return array
     */
    protected function _setFieldMatchers($options, $keys)
    {
        foreach ($keys as $field) {
            if (!is_array($options[$field])) {
                continue;
            }

            if (count($options[$field]) === 1) {
                $options[$field] = current($options[$field]);
                continue;
            }

            $fields = $options[$field];
            $options[$field] = function ($row) use ($fields) {
                $matches = [];
                foreach ($fields as $field) {
                    $matches[] = $row[$field];
                }
                return implode(';', $matches);
            };
        }

        return $options;
    }

    /**
     * {@inheritDoc}
     *
     * ### Usage
     *
     * Get an article and some relationships:
     *
     * ```
     * $article = $articles->get(1, ['contain' => ['Users', 'Comments']]);
     * ```
     *
     * @throws \Cake\Datasource\Exception\InvalidPrimaryKeyException When $primaryKey has an
     *      incorrect number of elements.
     */
    public function get($primaryKey, $options = [])
    {
        $key = (array)$this->primaryKey();
        $alias = $this->alias();
        foreach ($key as $index => $keyname) {
            $key[$index] = $alias . '.' . $keyname;
        }
        $primaryKey = (array)$primaryKey;
        if (count($key) !== count($primaryKey)) {
            $primaryKey = $primaryKey ?: [null];
            $primaryKey = array_map(function ($key) {
                return var_export($key, true);
            }, $primaryKey);

            throw new InvalidPrimaryKeyException(sprintf(
                'Record not found in table "%s" with primary key [%s]',
                $this->table(),
                implode($primaryKey, ', ')
            ));
        }
        $conditions = array_combine($key, $primaryKey);

        $cacheConfig = isset($options['cache']) ? $options['cache'] : false;
        $cacheKey = isset($options['key']) ? $options['key'] : false;
        $finder = isset($options['finder']) ? $options['finder'] : 'all';
        unset($options['key'], $options['cache'], $options['finder']);

        $query = $this->find($finder, $options)->where($conditions);

        if ($cacheConfig) {
            if (!$cacheKey) {
                $cacheKey = sprintf(
                    "get:%s.%s%s",
                    $this->connection()->configName(),
                    $this->table(),
                    json_encode($primaryKey)
                );
            }
            $query->cache($cacheKey, $cacheConfig);
        }
        return $query->firstOrFail();
    }

    /**
     * Finds an existing record or creates a new one.
     *
     * A find() will be done to locate an existing record using the attributes
     * defined in $search. If records matches the conditions, the first record
     * will be returned.
     *
     * If no record can be found, a new entity will be created
     * with the $search properties. If a callback is provided, it will be
     * called allowing you to define additional default values. The new
     * entity will be saved and returned.
     *
     * If your find conditions require custom order, associations or conditions, then the $search
     * parameter can be a callable that takes the Query as the argument, or a \Cake\ORM\Query object passed
     * as the $search parameter. Allowing you to customize the find results.
     *
     * ### Options
     *
     * The options array is passed to the save method with exception to the following keys:
     *
     * - atomic: Whether to execute the methods for find, save and callbacks inside a database
     *   transaction (default: true)
     * - defaults: Whether to use the search criteria as default values for the new entity (default: true)
     *
     * @param array|callable|\Cake\ORM\Query $search The criteria to find an existing record by, or a
     *   callable that will customize the find query.
     * @param callable|null $callback A callback that will be invoked for newly
     *   created entities. This callback will be called *before* the entity
     *   is persisted.
     * @param array $options The options to use when saving.
     * @return EntityInterface An entity.
     */
    public function findOrCreate($search, callable $callback = null, $options = [])
    {
        $options = $options + [
            'atomic' => true,
            'defaults' => true
        ];

        if ($options['atomic']) {
            return $this->connection()->transactional(function () use ($search, $callback, $options) {
                return $this->_processFindOrCreate($search, $callback, $options);
            });
        }
        return $this->_processFindOrCreate($search, $callback, $options);
    }

    /**
     * Performs the actual find and/or create of an entity based on the passed options.
     *
     * @param array|callable $search The criteria to find an existing record by, or a callable tha will
     *   customize the find query.
     * @param callable|null $callback A callback that will be invoked for newly
     *   created entities. This callback will be called *before* the entity
     *   is persisted.
     * @param array $options The options to use when saving.
     * @return EntityInterface An entity.
     */
    protected function _processFindOrCreate($search, callable $callback = null, $options = [])
    {
        if (is_callable($search)) {
            $query = $this->find();
            $search($query);
        } elseif (is_array($search)) {
            $query = $this->find()->where($search);
        } elseif ($search instanceof Query) {
            $query = $search;
        } else {
            throw new InvalidArgumentException('Search criteria must be an array, callable or Query');
        }
        $row = $query->first();
        if ($row !== null) {
            return $row;
        }
        $entity = $this->newEntity();
        if ($options['defaults'] && is_array($search)) {
            $entity->set($search, ['guard' => false]);
        }
        if ($callback !== null) {
            $entity = $callback($entity) ?: $entity;
        }
        unset($options['defaults']);
        return $this->save($entity, $options) ?: $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function query()
    {
        return new Query($this->connection(), $this);
    }

    /**
     * {@inheritDoc}
     */
    public function updateAll($fields, $conditions)
    {
        $query = $this->query();
        $query->update()
            ->set($fields)
            ->where($conditions);
        $statement = $query->execute();
        $statement->closeCursor();
        return $statement->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll($conditions)
    {
        $query = $this->query()
            ->delete()
            ->where($conditions);
        $statement = $query->execute();
        $statement->closeCursor();
        return $statement->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function exists($conditions)
    {
        return (bool)count(
            $this->find('all')
            ->select(['existing' => 1])
            ->where($conditions)
            ->limit(1)
            ->hydrate(false)
            ->toArray()
        );
    }

    /**
     * {@inheritDoc}
     *
     * ### Options
     *
     * The options array accepts the following keys:
     *
     * - atomic: Whether to execute the save and callbacks inside a database
     *   transaction (default: true)
     * - checkRules: Whether or not to check the rules on entity before saving, if the checking
     *   fails, it will abort the save operation. (default:true)
     * - associated: If true it will save all associated entities as they are found
     *   in the passed `$entity` whenever the property defined for the association
     *   is marked as dirty. Associated records are saved recursively unless told
     *   otherwise. If an array, it will be interpreted as the list of associations
     *   to be saved. It is possible to provide different options for saving on associated
     *   table objects using this key by making the custom options the array value.
     *   If false no associated records will be saved. (default: true)
     * - checkExisting: Whether or not to check if the entity already exists, assuming that the
     *   entity is marked as not new, and the primary key has been set.
     *
     * ### Events
     *
     * When saving, this method will trigger four events:
     *
     * - Model.beforeRules: Will be triggered right before any rule checking is done
     *   for the passed entity if the `checkRules` key in $options is not set to false.
     *   Listeners will receive as arguments the entity, options array and the operation type.
     *   If the event is stopped the rules check result will be set to the result of the event itself.
     * - Model.afterRules: Will be triggered right after the `checkRules()` method is
     *   called for the entity. Listeners will receive as arguments the entity,
     *   options array, the result of checking the rules and the operation type.
     *   If the event is stopped the checking result will be set to the result of
     *   the event itself.
     * - Model.beforeSave: Will be triggered just before the list of fields to be
     *   persisted is calculated. It receives both the entity and the options as
     *   arguments. The options array is passed as an ArrayObject, so any changes in
     *   it will be reflected in every listener and remembered at the end of the event
     *   so it can be used for the rest of the save operation. Returning false in any
     *   of the listeners will abort the saving process. If the event is stopped
     *   using the event API, the event object's `result` property will be returned.
     *   This can be useful when having your own saving strategy implemented inside a
     *   listener.
     * - Model.afterSave: Will be triggered after a successful insert or save,
     *   listeners will receive the entity and the options array as arguments. The type
     *   of operation performed (insert or update) can be determined by checking the
     *   entity's method `isNew`, true meaning an insert and false an update.
     * - Model.afterSaveCommit: Will be triggered after the transaction is commited
     *   for atomic save, listeners will receive the entity and the options array
     *   as arguments.
     *
     * This method will determine whether the passed entity needs to be
     * inserted or updated in the database. It does that by checking the `isNew`
     * method on the entity. If the entity to be saved returns a non-empty value from
     * its `errors()` method, it will not be saved.
     *
     * ### Saving on associated tables
     *
     * This method will by default persist entities belonging to associated tables,
     * whenever a dirty property matching the name of the property name set for an
     * association in this table. It is possible to control what associations will
     * be saved and to pass additional option for saving them.
     *
     * ```
     * // Only save the comments association
     * $articles->save($entity, ['associated' => ['Comments']);
     *
     * // Save the company, the employees and related addresses for each of them.
     * // For employees do not check the entity rules
     * $companies->save($entity, [
     *   'associated' => [
     *     'Employees' => [
     *       'associated' => ['Addresses'],
     *       'checkRules' => false
     *     ]
     *   ]
     * ]);
     *
     * // Save no associations
     * $articles->save($entity, ['associated' => false]);
     * ```
     *
     */
    public function save(EntityInterface $entity, $options = [])
    {
        $options = new ArrayObject($options + [
            'atomic' => true,
            'associated' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_primary' => true
        ]);

        if ($entity->errors()) {
            return false;
        }

        if ($entity->isNew() === false && !$entity->dirty()) {
            return $entity;
        }

        $connection = $this->connection();
        if ($options['atomic']) {
            $success = $connection->transactional(function () use ($entity, $options) {
                return $this->_processSave($entity, $options);
            });
        } else {
            $success = $this->_processSave($entity, $options);
        }

        if ($success) {
            if (!$connection->inTransaction() &&
                ($options['atomic'] || (!$options['atomic'] && $options['_primary']))
            ) {
                $this->dispatchEvent('Model.afterSaveCommit', compact('entity', 'options'));
            }
            if ($options['atomic'] || $options['_primary']) {
                $entity->isNew(false);
                $entity->source($this->registryAlias());
            }
        }

        return $success;
    }

    /**
     * Performs the actual saving of an entity based on the passed options.
     *
     * @param \Cake\Datasource\EntityInterface $entity the entity to be saved
     * @param \ArrayObject $options the options to use for the save operation
     * @return \Cake\Datasource\EntityInterface|bool
     * @throws \RuntimeException When an entity is missing some of the primary keys.
     */
    protected function _processSave($entity, $options)
    {
        $primaryColumns = (array)$this->primaryKey();

        if ($options['checkExisting'] && $primaryColumns && $entity->isNew() && $entity->has($primaryColumns)) {
            $alias = $this->alias();
            $conditions = [];
            foreach ($entity->extract($primaryColumns) as $k => $v) {
                $conditions["$alias.$k"] = $v;
            }
            $entity->isNew(!$this->exists($conditions));
        }

        $mode = $entity->isNew() ? RulesChecker::CREATE : RulesChecker::UPDATE;
        if ($options['checkRules'] && !$this->checkRules($entity, $mode, $options)) {
            return false;
        }

        $options['associated'] = $this->_associations->normalizeKeys($options['associated']);
        $event = $this->dispatchEvent('Model.beforeSave', compact('entity', 'options'));

        if ($event->isStopped()) {
            return $event->result;
        }

        $saved = $this->_associations->saveParents(
            $this,
            $entity,
            $options['associated'],
            ['_primary' => false] + $options->getArrayCopy()
        );

        if (!$saved && $options['atomic']) {
            return false;
        }

        $data = $entity->extract($this->schema()->columns(), true);
        $isNew = $entity->isNew();

        if ($isNew) {
            $success = $this->_insert($entity, $data);
        } else {
            $success = $this->_update($entity, $data);
        }

        if ($success) {
            $success = $this->_associations->saveChildren(
                $this,
                $entity,
                $options['associated'],
                ['_primary' => false] + $options->getArrayCopy()
            );
            if ($success || !$options['atomic']) {
                $this->dispatchEvent('Model.afterSave', compact('entity', 'options'));
                $entity->clean();
                if (!$options['atomic'] && !$options['_primary']) {
                    $entity->isNew(false);
                    $entity->source($this->registryAlias());
                }
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
     * @param \Cake\Datasource\EntityInterface $entity the subject entity from were $data was extracted
     * @param array $data The actual data that needs to be saved
     * @return \Cake\Datasource\EntityInterface|bool
     * @throws \RuntimeException if not all the primary keys where supplied or could
     * be generated when the table has composite primary keys. Or when the table has no primary key.
     */
    protected function _insert($entity, $data)
    {
        $primary = (array)$this->primaryKey();
        if (empty($primary)) {
            $msg = sprintf(
                'Cannot insert row in "%s" table, it has no primary key.',
                $this->table()
            );
            throw new RuntimeException($msg);
        }
        $keys = array_fill(0, count($primary), null);
        $id = (array)$this->_newId($primary) + $keys;

        // Generate primary keys preferring values in $data.
        $primary = array_combine($primary, $id);
        $primary = array_intersect_key($data, $primary) + $primary;

        $filteredKeys = array_filter($primary, 'strlen');
        $data = $data + $filteredKeys;

        if (count($primary) > 1) {
            $schema = $this->schema();
            foreach ($primary as $k => $v) {
                if (!isset($data[$k]) && empty($schema->column($k)['autoIncrement'])) {
                    $msg = 'Cannot insert row, some of the primary key values are missing. ';
                    $msg .= sprintf(
                        'Got (%s), expecting (%s)',
                        implode(', ', $filteredKeys + $entity->extract(array_keys($primary))),
                        implode(', ', array_keys($primary))
                    );
                    throw new RuntimeException($msg);
                }
            }
        }

        $success = false;
        if (empty($data)) {
            return $success;
        }

        $statement = $this->query()->insert(array_keys($data))
            ->values($data)
            ->execute();

        if ($statement->rowCount() !== 0) {
            $success = $entity;
            $entity->set($filteredKeys, ['guard' => false]);
            $schema = $this->schema();
            $driver = $this->connection()->driver();
            foreach ($primary as $key => $v) {
                if (!isset($data[$key])) {
                    $id = $statement->lastInsertId($this->table(), $key);
                    $type = $schema->columnType($key);
                    $entity->set($key, Type::build($type)->toPHP($id, $driver));
                    break;
                }
            }
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
     * @param array $primary The primary key columns to get a new ID for.
     * @return mixed Either null or the new primary key value.
     */
    protected function _newId($primary)
    {
        if (!$primary || count((array)$primary) > 1) {
            return null;
        }
        $typeName = $this->schema()->columnType($primary[0]);
        $type = Type::build($typeName);
        return $type->newId();
    }

    /**
     * Auxiliary function to handle the update of an entity's data in the table
     *
     * @param \Cake\Datasource\EntityInterface $entity the subject entity from were $data was extracted
     * @param array $data The actual data that needs to be saved
     * @return \Cake\Datasource\EntityInterface|bool
     * @throws \InvalidArgumentException When primary key data is missing.
     */
    protected function _update($entity, $data)
    {
        $primaryColumns = (array)$this->primaryKey();
        $primaryKey = $entity->extract($primaryColumns);

        $data = array_diff_key($data, $primaryKey);
        if (empty($data)) {
            return $entity;
        }

        if (!$entity->has($primaryColumns)) {
            $message = 'All primary key value(s) are needed for updating';
            throw new InvalidArgumentException($message);
        }

        $query = $this->query();
        $statement = $query->update()
            ->set($data)
            ->where($primaryKey)
            ->execute();

        $success = false;
        if ($statement->errorCode() === '00000') {
            $success = $entity;
        }
        $statement->closeCursor();
        return $success;
    }

    /**
     * Persists multiple entities of a table.
     *
     * The records will be saved in a transaction which will be rolled back if
     * any one of the records fails to save due to failed validation or database
     * error.
     *
     * @param array|\Cake\ORM\ResultSet $entities Entities to save.
     * @param array|\ArrayAccess $options Options used when calling Table::save() for each entity.
     * @return bool|array|\Cake\ORM\ResultSet False on failure, entities list on succcess.
     */
    public function saveMany($entities, $options = [])
    {
        $isNew = [];

        $return = $this->connection()->transactional(
            function () use ($entities, $options, &$isNew) {
                foreach ($entities as $key => $entity) {
                    $isNew[$key] = $entity->isNew();
                    if ($this->save($entity, $options) === false) {
                        return false;
                    }
                }
            }
        );

        if ($return === false) {
            foreach ($entities as $key => $entity) {
                if (isset($isNew[$key]) && $isNew[$key]) {
                    $entity->unsetProperty($this->primaryKey());
                    $entity->isNew(true);
                }
            }
            return false;
        }

        return $entities;
    }

    /**
     * {@inheritDoc}
     *
     * For HasMany and HasOne associations records will be removed based on
     * the dependent option. Join table records in BelongsToMany associations
     * will always be removed. You can use the `cascadeCallbacks` option
     * when defining associations to change how associated data is deleted.
     *
     * ### Options
     *
     * - `atomic` Defaults to true. When true the deletion happens within a transaction.
     * - `checkRules` Defaults to true. Check deletion rules before deleting the record.
     *
     * ### Events
     *
     * - `Model.beforeDelete` Fired before the delete occurs. If stopped the delete
     *   will be aborted. Receives the event, entity, and options.
     * - `Model.afterDelete` Fired after the delete has been successful. Receives
     *   the event, entity, and options.
     * - `Model.afterDeleteCommit` Fired after the transaction is committed for
     *   an atomic delete. Receives the event, entity, and options.
     *
     * The options argument will be converted into an \ArrayObject instance
     * for the duration of the callbacks, this allows listeners to modify
     * the options used in the delete operation.
     *
     */
    public function delete(EntityInterface $entity, $options = [])
    {
        $options = new ArrayObject($options + [
            'atomic' => true,
            'checkRules' => true,
            '_primary' => true,
        ]);

        $process = function () use ($entity, $options) {
            return $this->_processDelete($entity, $options);
        };

        $connection = $this->connection();
        if ($options['atomic']) {
            $success = $connection->transactional($process);
        } else {
            $success = $process();
        }

        if ($success &&
            !$connection->inTransaction() &&
            ($options['atomic'] || (!$options['atomic'] && $options['_primary']))
        ) {
            $this->dispatchEvent('Model.afterDeleteCommit', [
                'entity' => $entity,
                'options' => $options
            ]);
        }
        return $success;
    }

    /**
     * Perform the delete operation.
     *
     * Will delete the entity provided. Will remove rows from any
     * dependent associations, and clear out join tables for BelongsToMany associations.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to delete.
     * @param \ArrayObject $options The options for the delete.
     * @throws \InvalidArgumentException if there are no primary key values of the
     * passed entity
     * @return bool success
     */
    protected function _processDelete($entity, $options)
    {
        if ($entity->isNew()) {
            return false;
        }

        $primaryKey = (array)$this->primaryKey();
        if (!$entity->has($primaryKey)) {
            $msg = 'Deleting requires all primary key values.';
            throw new InvalidArgumentException($msg);
        }

        if ($options['checkRules'] && !$this->checkRules($entity, RulesChecker::DELETE, $options)) {
            return false;
        }

        $event = $this->dispatchEvent('Model.beforeDelete', [
            'entity' => $entity,
            'options' => $options
        ]);

        if ($event->isStopped()) {
            return $event->result;
        }

        $this->_associations->cascadeDelete(
            $entity,
            ['_primary' => false] + $options->getArrayCopy()
        );

        $query = $this->query();
        $conditions = (array)$entity->extract($primaryKey);
        $statement = $query->delete()
            ->where($conditions)
            ->execute();

        $success = $statement->rowCount() > 0;
        if (!$success) {
            return $success;
        }

        $this->dispatchEvent('Model.afterDelete', [
            'entity' => $entity,
            'options' => $options
        ]);

        return $success;
    }

    /**
     * Returns true if the finder exists for the table
     *
     * @param string $type name of finder to check
     *
     * @return bool
     */
    public function hasFinder($type)
    {
        $finder = 'find' . $type;

        return method_exists($this, $finder) || ($this->_behaviors && $this->_behaviors->hasFinder($type));
    }

    /**
     * Calls a finder method directly and applies it to the passed query,
     * if no query is passed a new one will be created and returned
     *
     * @param string $type name of the finder to be called
     * @param \Cake\ORM\Query $query The query object to apply the finder options to
     * @param array $options List of options to pass to the finder
     * @return \Cake\ORM\Query
     * @throws \BadMethodCallException
     */
    public function callFinder($type, Query $query, array $options = [])
    {
        $query->applyOptions($options);
        $options = $query->getOptions();
        $finder = 'find' . $type;
        if (method_exists($this, $finder)) {
            return $this->{$finder}($query, $options);
        }

        if ($this->_behaviors && $this->_behaviors->hasFinder($type)) {
            return $this->_behaviors->callFinder($type, [$query, $options]);
        }

        throw new BadMethodCallException(
            sprintf('Unknown finder method "%s"', $type)
        );
    }

    /**
     * Provides the dynamic findBy and findByAll methods.
     *
     * @param string $method The method name that was fired.
     * @param array $args List of arguments passed to the function.
     * @return mixed
     * @throws \BadMethodCallException when there are missing arguments, or when
     *  and & or are combined.
     */
    protected function _dynamicFinder($method, $args)
    {
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
        $hasOr = strpos($fields, '_or_');
        $hasAnd = strpos($fields, '_and_');

        $makeConditions = function ($fields, $args) {
            $conditions = [];
            if (count($args) < count($fields)) {
                throw new BadMethodCallException(sprintf(
                    'Not enough arguments for magic finder. Got %s required %s',
                    count($args),
                    count($fields)
                ));
            }
            foreach ($fields as $field) {
                $conditions[$this->aliasField($field)] = array_shift($args);
            }
            return $conditions;
        };

        if ($hasOr !== false && $hasAnd !== false) {
            throw new BadMethodCallException(
                'Cannot mix "and" & "or" in a magic finder. Use find() instead.'
            );
        }

        $conditions = [];
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
    public function __call($method, $args)
    {
        if ($this->_behaviors && $this->_behaviors->hasMethod($method)) {
            return $this->_behaviors->call($method, $args);
        }
        if (preg_match('/^find(?:\w+)?By/', $method) > 0) {
            return $this->_dynamicFinder($method, $args);
        }

        throw new BadMethodCallException(
            sprintf('Unknown method "%s"', $method)
        );
    }

    /**
     * Returns the association named after the passed value if exists, otherwise
     * throws an exception.
     *
     * @param string $property the association name
     * @return \Cake\ORM\Association
     * @throws \RuntimeException if no association with such name exists
     */
    public function __get($property)
    {
        $association = $this->_associations->get($property);
        if (!$association) {
            throw new RuntimeException(sprintf(
                'Table "%s" is not associated with "%s"',
                get_class($this),
                $property
            ));
        }
        return $association;
    }

    /**
     * Returns whether an association named after the passed value
     * exists for this table.
     *
     * @param string $property the association name
     * @return bool
     */
    public function __isset($property)
    {
        return $this->_associations->has($property);
    }

    /**
     * Get the object used to marshal/convert array data into objects.
     *
     * Override this method if you want a table object to use custom
     * marshalling logic.
     *
     * @return \Cake\ORM\Marshaller
     * @see \Cake\ORM\Marshaller
     */
    public function marshaller()
    {
        return new Marshaller($this);
    }

    /**
     * {@inheritDoc}
     *
     * By default all the associations on this table will be hydrated. You can
     * limit which associations are built, or include deeper associations
     * using the options parameter:
     *
     * ```
     * $article = $this->Articles->newEntity(
     *   $this->request->data(),
     *   ['associated' => ['Tags', 'Comments.Users']]
     * );
     * ```
     *
     * You can limit fields that will be present in the constructed entity by
     * passing the `fieldList` option, which is also accepted for associations:
     *
     * ```
     * $article = $this->Articles->newEntity($this->request->data(), [
     *  'fieldList' => ['title', 'body'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fieldList' => 'username']]
     * ]
     * );
     * ```
     *
     * The `fieldList` option lets remove or restrict input data from ending up in
     * the entity. If you'd like to relax the entity's default accessible fields,
     * you can use the `accessibleFields` option:
     *
     * ```
     * $article = $this->Articles->newEntity(
     *   $this->request->data(),
     *   ['accessibleFields' => ['protected_field' => true]]
     * );
     * ```
     *
     * By default, the data is validated before being passed to the new entity. In
     * the case of invalid fields, those will not be present in the resulting object.
     * The `validate` option can be used to disable validation on the passed data:
     *
     * ```
     * $article = $this->Articles->newEntity(
     *   $this->request->data(),
     *   ['validate' => false]
     * );
     * ```
     *
     * You can also pass the name of the validator to use in the `validate` option.
     * If `null` is passed to the first param of this function, no validation will
     * be performed.
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     */
    public function newEntity($data = null, array $options = [])
    {
        if ($data === null) {
            $class = $this->entityClass();
            $entity = new $class([], ['source' => $this->registryAlias()]);
            return $entity;
        }
        if (!isset($options['associated'])) {
            $options['associated'] = $this->_associations->keys();
        }
        $marshaller = $this->marshaller();
        return $marshaller->one($data, $options);
    }

    /**
     * {@inheritDoc}
     *
     * By default all the associations on this table will be hydrated. You can
     * limit which associations are built, or include deeper associations
     * using the options parameter:
     *
     * ```
     * $articles = $this->Articles->newEntities(
     *   $this->request->data(),
     *   ['associated' => ['Tags', 'Comments.Users']]
     * );
     * ```
     *
     * You can limit fields that will be present in the constructed entities by
     * passing the `fieldList` option, which is also accepted for associations:
     *
     * ```
     * $articles = $this->Articles->newEntities($this->request->data(), [
     *  'fieldList' => ['title', 'body'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fieldList' => 'username']]
     *  ]
     * );
     * ```
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     */
    public function newEntities(array $data, array $options = [])
    {
        if (!isset($options['associated'])) {
            $options['associated'] = $this->_associations->keys();
        }
        $marshaller = $this->marshaller();
        return $marshaller->many($data, $options);
    }

    /**
     * {@inheritDoc}
     *
     * When merging HasMany or BelongsToMany associations, all the entities in the
     * `$data` array will appear, those that can be matched by primary key will get
     * the data merged, but those that cannot, will be discarded.
     *
     * You can limit fields that will be present in the merged entity by
     * passing the `fieldList` option, which is also accepted for associations:
     *
     * ```
     * $article = $this->Articles->patchEntity($article, $this->request->data(), [
     *  'fieldList' => ['title', 'body'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fieldList' => 'username']]
     *  ]
     * );
     * ```
     *
     * By default, the data is validated before being passed to the entity. In
     * the case of invalid fields, those will not be assigned to the entity.
     * The `validate` option can be used to disable validation on the passed data:
     *
     * ```
     * $article = $this->patchEntity($article, $this->request->data(),[
     *  'validate' => false
     * ]);
     * ```
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     */
    public function patchEntity(EntityInterface $entity, array $data, array $options = [])
    {
        if (!isset($options['associated'])) {
            $options['associated'] = $this->_associations->keys();
        }
        $marshaller = $this->marshaller();
        return $marshaller->merge($entity, $data, $options);
    }

    /**
     * {@inheritDoc}
     *
     * Those entries in `$entities` that cannot be matched to any record in
     * `$data` will be discarded. Records in `$data` that could not be matched will
     * be marshalled as a new entity.
     *
     * When merging HasMany or BelongsToMany associations, all the entities in the
     * `$data` array will appear, those that can be matched by primary key will get
     * the data merged, but those that cannot, will be discarded.
     *
     * You can limit fields that will be present in the merged entities by
     * passing the `fieldList` option, which is also accepted for associations:
     *
     * ```
     * $articles = $this->Articles->patchEntities($articles, $this->request->data(), [
     *  'fieldList' => ['title', 'body'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fieldList' => 'username']]
     *  ]
     * );
     * ```
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     */
    public function patchEntities($entities, array $data, array $options = [])
    {
        if (!isset($options['associated'])) {
            $options['associated'] = $this->_associations->keys();
        }
        $marshaller = $this->marshaller();
        return $marshaller->mergeMany($entities, $data, $options);
    }

    /**
     * Validator method used to check the uniqueness of a value for a column.
     * This is meant to be used with the validation API and not to be called
     * directly.
     *
     * ### Example:
     *
     * ```
     * $validator->add('email', [
     *  'unique' => ['rule' => 'validateUnique', 'provider' => 'table']
     * ])
     * ```
     *
     * Unique validation can be scoped to the value of another column:
     *
     * ```
     * $validator->add('email', [
     *  'unique' => [
     *      'rule' => ['validateUnique', ['scope' => 'site_id']],
     *      'provider' => 'table'
     *  ]
     * ]);
     * ```
     *
     * In the above example, the email uniqueness will be scoped to only rows having
     * the same site_id. Scoping will only be used if the scoping field is present in
     * the data to be validated.
     *
     * @param mixed $value The value of column to be checked for uniqueness
     * @param array $options The options array, optionally containing the 'scope' key.
     *   May also be the validation context if there are no options.
     * @param array|null $context Either the validation context or null.
     * @return bool true if the value is unique
     */
    public function validateUnique($value, array $options, array $context = null)
    {
        if ($context === null) {
            $context = $options;
        }
        $entity = new Entity(
            $context['data'],
            [
                'useSetters' => false,
                'markNew' => $context['newRecord'],
                'source' => $this->registryAlias()
            ]
        );
        $fields = array_merge(
            [$context['field']],
            isset($options['scope']) ? (array)$options['scope'] : []
        );
        $rule = new IsUnique($fields);
        return $rule($entity, ['repository' => $this]);
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
     * The conventional method map is:
     *
     * - Model.beforeMarshal => beforeMarshal
     * - Model.beforeFind => beforeFind
     * - Model.beforeSave => beforeSave
     * - Model.afterSave => afterSave
     * - Model.afterSaveCommit => afterSaveCommit
     * - Model.beforeDelete => beforeDelete
     * - Model.afterDelete => afterDelete
     * - Model.afterDeleteCommit => afterDeleteCommit
     * - Model.beforeRules => beforeRules
     * - Model.afterRules => afterRules
     *
     * @return array
     */
    public function implementedEvents()
    {
        $eventMap = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.beforeFind' => 'beforeFind',
            'Model.beforeSave' => 'beforeSave',
            'Model.afterSave' => 'afterSave',
            'Model.afterSaveCommit' => 'afterSaveCommit',
            'Model.beforeDelete' => 'beforeDelete',
            'Model.afterDelete' => 'afterDelete',
            'Model.afterDeleteCommit' => 'afterDeleteCommit',
            'Model.beforeRules' => 'beforeRules',
            'Model.afterRules' => 'afterRules',
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

    /**
     * {@inheritDoc}
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        return $rules;
    }

    /**
     * Loads the specified associations in the passed entity or list of entities
     * by executing extra queries in the database and merging the results in the
     * appropriate properties.
     *
     * ### Example:
     *
     * ```
     * $user = $usersTable->get(1);
     * $user = $usersTable->loadInto($user, ['Articles.Tags', 'Articles.Comments']);
     * echo $user->articles[0]->title;
     * ```
     *
     * You can also load associations for multiple entities at once
     *
     * ### Example:
     *
     * ```
     * $users = $usersTable->find()->where([...])->toList();
     * $users = $usersTable->loadInto($users, ['Articles.Tags', 'Articles.Comments']);
     * echo $user[1]->articles[0]->title;
     * ```
     *
     * The properties for the associations to be loaded will be overwritten on each entity.
     *
     * @param \Cake\Datasource\EntityInterface|array $entities a single entity or list of entities
     * @param array $contain A `contain()` compatible array.
     * @see \Cake\ORM\Query::contain()
     * @return \Cake\Datasource\EntityInterface|array
     */
    public function loadInto($entities, array $contain)
    {
        return (new LazyEagerLoader)->loadInto($entities, $contain, $this);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $conn = $this->connection();
        $associations = $this->_associations ?: false;
        $behaviors = $this->_behaviors ?: false;
        return [
            'registryAlias' => $this->registryAlias(),
            'table' => $this->table(),
            'alias' => $this->alias(),
            'entityClass' => $this->entityClass(),
            'associations' => $associations ? $associations->keys() : false,
            'behaviors' => $behaviors ? $behaviors->loaded() : false,
            'defaultConnection' => $this->defaultConnectionName(),
            'connectionName' => $conn ? $conn->configName() : null
        ];
    }
}
