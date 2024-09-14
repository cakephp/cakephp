<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use ArrayObject;
use BadMethodCallException;
use Cake\Collection\CollectionInterface;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
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
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Exception\RolledbackTransactionException;
use Cake\ORM\Query\DeleteQuery;
use Cake\ORM\Query\InsertQuery;
use Cake\ORM\Query\QueryFactory;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Query\UpdateQuery;
use Cake\ORM\Rule\IsUnique;
use Cake\Utility\Inflector;
use Cake\Validation\ValidatorAwareInterface;
use Cake\Validation\ValidatorAwareTrait;
use Closure;
use Exception;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use ReflectionFunction;
use ReflectionNamedType;
use function Cake\Core\deprecationWarning;
use function Cake\Core\namespaceSplit;

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
 * ### Events
 *
 * Table objects emit several events during as life-cycle hooks during find, delete and save
 * operations. All events use the CakePHP event package:
 *
 * - `Model.beforeFind` Fired before each find operation. By stopping the event and
 *   supplying a return value you can bypass the find operation entirely. Any
 *   changes done to the $query instance will be retained for the rest of the find. The
 *   `$primary` parameter indicates whether this is the root query, or an
 *   associated query.
 *
 * - `Model.buildValidator` Allows listeners to modify validation rules
 *   for the provided named validator.
 *
 * - `Model.buildRules` Allows listeners to modify the rules checker by adding more rules.
 *
 * - `Model.beforeRules` Fired before an entity is validated using the rules checker.
 *   By stopping this event, you can return the final value of the rules checking operation.
 *
 * - `Model.afterRules` Fired after the rules have been checked on the entity. By
 *   stopping this event, you can return the final value of the rules checking operation.
 *
 * - `Model.beforeSave` Fired before each entity is saved. Stopping this event will
 *   abort the save operation. When the event is stopped the result of the event will be returned.
 *
 * - `Model.afterSave` Fired after an entity is saved.
 *
 * - `Model.afterSaveCommit` Fired after the transaction in which the save operation is
 *   wrapped has been committed. It’s also triggered for non atomic saves where database
 *   operations are implicitly committed. The event is triggered only for the primary
 *   table on which save() is directly called. The event is not triggered if a
 *   transaction is started before calling save.
 *
 * - `Model.beforeDelete` Fired before an entity is deleted. By stopping this
 *   event you will abort the delete operation.
 *
 * - `Model.afterDelete` Fired after an entity has been deleted.
 *
 * ### Callbacks
 *
 * You can subscribe to the events listed above in your table classes by implementing the
 * lifecycle methods below:
 *
 * - `beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, boolean $primary)`
 * - `beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)`
 * - `afterMarshal(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 * - `buildValidator(EventInterface $event, Validator $validator, string $name)`
 * - `buildRules(RulesChecker $rules)`
 * - `beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, string $operation)`
 * - `afterRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, bool $result, string $operation)`
 * - `beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 * - `afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 * - `afterSaveCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 * - `beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 * - `afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 * - `afterDeleteCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options)`
 *
 * @see \Cake\Event\EventManager for reference on the events system.
 * @link https://book.cakephp.org/5/en/orm/table-objects.html#event-list
 * @implements \Cake\Event\EventDispatcherInterface<\Cake\ORM\Table>
 */
class Table implements RepositoryInterface, EventListenerInterface, EventDispatcherInterface, ValidatorAwareInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\ORM\Table>
     */
    use EventDispatcherTrait;
    use RulesAwareTrait;
    use ValidatorAwareTrait;

    /**
     * Name of default validation set.
     *
     * @var string
     */
    public const DEFAULT_VALIDATOR = 'default';

    /**
     * The alias this object is assigned to validators as.
     *
     * @var string
     */
    public const VALIDATOR_PROVIDER_NAME = 'table';

    /**
     * The name of the event dispatched when a validator has been built.
     *
     * @var string
     */
    public const BUILD_VALIDATOR_EVENT = 'Model.buildValidator';

    /**
     * The rules class name that is used.
     *
     * @var class-string<\Cake\ORM\RulesChecker>
     */
    public const RULES_CLASS = RulesChecker::class;

    /**
     * The IsUnique class name that is used.
     *
     * @var class-string<\Cake\ORM\Rule\IsUnique>
     */
    public const IS_UNIQUE_CLASS = IsUnique::class;

    /**
     * Name of the table as it can be found in the database
     *
     * @var string|null
     */
    protected ?string $_table = null;

    /**
     * Human name giving to this particular instance. Multiple objects representing
     * the same database table can exist by using different aliases.
     *
     * @var string|null
     */
    protected ?string $_alias = null;

    /**
     * Connection instance
     *
     * @var \Cake\Database\Connection|null
     */
    protected ?Connection $_connection = null;

    /**
     * The schema object containing a description of this table fields
     *
     * @var \Cake\Database\Schema\TableSchemaInterface|null
     */
    protected ?TableSchemaInterface $_schema = null;

    /**
     * The name of the field that represents the primary key in the table
     *
     * @var list<string>|string|null
     */
    protected array|string|null $_primaryKey = null;

    /**
     * The name of the field that represents a human-readable representation of a row
     *
     * @var list<string>|string|null
     */
    protected array|string|null $_displayField = null;

    /**
     * The associations container for this Table.
     *
     * @var \Cake\ORM\AssociationCollection
     */
    protected AssociationCollection $_associations;

    /**
     * BehaviorRegistry for this table
     *
     * @var \Cake\ORM\BehaviorRegistry
     */
    protected BehaviorRegistry $_behaviors;

    /**
     * The name of the class that represent a single row for this table
     *
     * @var string|null
     * @psalm-var class-string<\Cake\Datasource\EntityInterface>|null
     */
    protected ?string $_entityClass = null;

    /**
     * Registry key used to create this table object
     *
     * @var string|null
     */
    protected ?string $_registryAlias = null;

    protected QueryFactory $queryFactory;

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
     * - schema: A \Cake\Database\Schema\TableSchemaInterface object or an array that can be
     *   passed to it.
     * - eventManager: An instance of an event manager to use for internal events
     * - behaviors: A BehaviorRegistry. Generally not used outside of tests.
     * - associations: An AssociationCollection instance.
     * - validator: A Validator instance which is assigned as the "default"
     *   validation set, or an associative array, where key is the name of the
     *   validation set and value the Validator instance.
     *
     * @param array<string, mixed> $config List of options for this table.
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['registryAlias'])) {
            $this->setRegistryAlias($config['registryAlias']);
        }
        if (!empty($config['table'])) {
            $this->setTable($config['table']);
        }
        if (!empty($config['alias'])) {
            $this->setAlias($config['alias']);
        }
        if (!empty($config['connection'])) {
            $this->setConnection($config['connection']);
        }
        if (!empty($config['queryFactory'])) {
            $this->queryFactory = $config['queryFactory'];
        }
        if (!empty($config['schema'])) {
            $this->setSchema($config['schema']);
        }
        if (!empty($config['entityClass'])) {
            $this->setEntityClass($config['entityClass']);
        }
        $eventManager = null;
        $behaviors = null;
        $associations = null;
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
                $this->setValidator(static::DEFAULT_VALIDATOR, $config['validator']);
            } else {
                foreach ($config['validator'] as $name => $validator) {
                    $this->setValidator($name, $validator);
                }
            }
        }
        $this->_eventManager = $eventManager ?: new EventManager();
        /** @var \Cake\ORM\BehaviorRegistry $behaviors */
        $this->_behaviors = $behaviors ?: new BehaviorRegistry();
        $this->_behaviors->setTable($this);
        $this->_associations = $associations ?: new AssociationCollection();
        /** @psalm-suppress TypeDoesNotContainType */
        $this->queryFactory ??= new QueryFactory();

        $this->initialize($config);

        assert($this->_eventManager !== null, 'EventManager not available');

        $this->_eventManager->on($this);
        $this->dispatchEvent('Model.initialize');
    }

    /**
     * Get the default connection name.
     *
     * This method is used to get the fallback connection name if an
     * instance is created through the TableLocator without a connection.
     *
     * @return string
     * @see \Cake\ORM\Locator\TableLocator::get()
     */
    public static function defaultConnectionName(): string
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
     *      $this->setPrimaryKey('something_else');
     *  }
     * ```
     *
     * @param array<string, mixed> $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config): void
    {
    }

    /**
     * Sets the database table name.
     *
     * This can include the database schema name in the form 'schema.table'.
     * If the name must be quoted, enable automatic identifier quoting.
     *
     * @param string $table Table name.
     * @return $this
     */
    public function setTable(string $table)
    {
        $this->_table = $table;

        return $this;
    }

    /**
     * Returns the database table name.
     *
     * This can include the database schema name if set using `setTable()`.
     *
     * @return string
     */
    public function getTable(): string
    {
        if ($this->_table === null) {
            $table = namespaceSplit(static::class);
            $table = substr((string)end($table), 0, -5) ?: $this->_alias;
            if (!$table) {
                throw new CakeException(
                    'You must specify either the `alias` or the `table` option for the constructor.'
                );
            }
            $this->_table = Inflector::underscore($table);
        }

        return $this->_table;
    }

    /**
     * Sets the table alias.
     *
     * @param string $alias Table alias
     * @return $this
     */
    public function setAlias(string $alias)
    {
        $this->_alias = $alias;

        return $this;
    }

    /**
     * Returns the table alias.
     *
     * @return string
     */
    public function getAlias(): string
    {
        if ($this->_alias === null) {
            $alias = namespaceSplit(static::class);
            $alias = substr((string)end($alias), 0, -5) ?: $this->_table;
            if (!$alias) {
                throw new CakeException(
                    'You must specify either the `alias` or the `table` option for the constructor.'
                );
            }
            $this->_alias = $alias;
        }

        return $this->_alias;
    }

    /**
     * Alias a field with the table's current alias.
     *
     * If field is already aliased it will result in no-op.
     *
     * @param string $field The field to alias.
     * @return string The field prefixed with the table alias.
     */
    public function aliasField(string $field): string
    {
        if (str_contains($field, '.')) {
            return $field;
        }

        return $this->getAlias() . '.' . $field;
    }

    /**
     * Sets the table registry key used to create this table instance.
     *
     * @param string $registryAlias The key used to access this object.
     * @return $this
     */
    public function setRegistryAlias(string $registryAlias)
    {
        $this->_registryAlias = $registryAlias;

        return $this;
    }

    /**
     * Returns the table registry key used to create this table instance.
     *
     * @return string
     */
    public function getRegistryAlias(): string
    {
        return $this->_registryAlias ??= $this->getAlias();
    }

    /**
     * Sets the connection instance.
     *
     * @param \Cake\Database\Connection $connection The connection instance
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * Returns the connection instance.
     *
     * @return \Cake\Database\Connection
     */
    public function getConnection(): Connection
    {
        if (!$this->_connection) {
            $connection = ConnectionManager::get(static::defaultConnectionName());
            assert($connection instanceof Connection);
            $this->_connection = $connection;
        }

        return $this->_connection;
    }

    /**
     * Returns the schema table object describing this table's properties.
     *
     * @return \Cake\Database\Schema\TableSchemaInterface
     */
    public function getSchema(): TableSchemaInterface
    {
        if ($this->_schema === null) {
            $this->_schema = $this->getConnection()
                ->getSchemaCollection()
                ->describe($this->getTable());
            if (Configure::read('debug')) {
                $this->checkAliasLengths();
            }
        }

        /** @var \Cake\Database\Schema\TableSchemaInterface */
        return $this->_schema;
    }

    /**
     * Sets the schema table object describing this table's properties.
     *
     * If an array is passed, a new TableSchemaInterface will be constructed
     * out of it and used as the schema for this table.
     *
     * @param \Cake\Database\Schema\TableSchemaInterface|array $schema Schema to be used for this table
     * @return $this
     */
    public function setSchema(TableSchemaInterface|array $schema)
    {
        if (is_array($schema)) {
            $constraints = [];

            if (isset($schema['_constraints'])) {
                $constraints = $schema['_constraints'];
                unset($schema['_constraints']);
            }

            $schema = $this->getConnection()->getDriver()->newTableSchema($this->getTable(), $schema);

            foreach ($constraints as $name => $value) {
                $schema->addConstraint($name, $value);
            }
        }

        $this->_schema = $schema;
        if (Configure::read('debug')) {
            $this->checkAliasLengths();
        }

        return $this;
    }

    /**
     * Checks if all table name + column name combinations used for
     * queries fit into the max length allowed by database driver.
     *
     * @return void
     * @throws \Cake\Database\Exception\DatabaseException When an alias combination is too long
     */
    protected function checkAliasLengths(): void
    {
        if ($this->_schema === null) {
            throw new DatabaseException(sprintf(
                'Unable to check max alias lengths for `%s` without schema.',
                $this->getAlias()
            ));
        }

        $maxLength = $this->getConnection()->getDriver()->getMaxAliasLength();
        if ($maxLength === null) {
            return;
        }

        $table = $this->getAlias();
        foreach ($this->_schema->columns() as $name) {
            if (strlen($table . '__' . $name) > $maxLength) {
                $nameLength = $maxLength - 2;
                throw new DatabaseException(
                    'ORM queries generate field aliases using the table name/alias and column name. ' .
                    "The table alias `{$table}` and column `{$name}` create an alias longer than ({$nameLength}). " .
                    'You must change the table schema in the database and shorten either the table or column ' .
                    'identifier so they fit within the database alias limits.'
                );
            }
        }
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
    public function hasField(string $field): bool
    {
        return $this->getSchema()->getColumn($field) !== null;
    }

    /**
     * Sets the primary key field name.
     *
     * @param list<string>|string $key Sets a new name to be used as primary key
     * @return $this
     */
    public function setPrimaryKey(array|string $key)
    {
        $this->_primaryKey = $key;

        return $this;
    }

    /**
     * Returns the primary key field name.
     *
     * @return list<string>|string
     */
    public function getPrimaryKey(): array|string
    {
        if ($this->_primaryKey === null) {
            $key = $this->getSchema()->getPrimaryKey();
            if (count($key) === 1) {
                $key = $key[0];
            }
            $this->_primaryKey = $key;
        }

        return $this->_primaryKey;
    }

    /**
     * Sets the display field.
     *
     * @param list<string>|string $field Name to be used as display field.
     * @return $this
     */
    public function setDisplayField(array|string $field)
    {
        $this->_displayField = $field;

        return $this;
    }

    /**
     * Returns the display field.
     *
     * @return list<string>|string
     */
    public function getDisplayField(): array|string|null
    {
        if ($this->_displayField !== null) {
            return $this->_displayField;
        }

        $schema = $this->getSchema();
        foreach (['title', 'name', 'label'] as $field) {
            if ($schema->hasColumn($field)) {
                return $this->_displayField = $field;
            }
        }

        foreach ($schema->columns() as $column) {
            $columnSchema = $schema->getColumn($column);
            if (
                $columnSchema &&
                $columnSchema['null'] !== true &&
                $columnSchema['type'] === 'string' &&
                !preg_match('/pass|token|secret/i', $column)
            ) {
                return $this->_displayField = $column;
            }
        }

        return $this->_displayField = $this->getPrimaryKey();
    }

    /**
     * Returns the class used to hydrate rows for this table.
     *
     * @return class-string<\Cake\Datasource\EntityInterface>
     */
    public function getEntityClass(): string
    {
        if (!$this->_entityClass) {
            $default = Entity::class;
            $self = static::class;
            $parts = explode('\\', $self);

            if ($self === self::class || count($parts) < 3) {
                return $this->_entityClass = $default;
            }

            $alias = Inflector::classify(Inflector::underscore(substr(array_pop($parts), 0, -5)));
            $name = implode('\\', array_slice($parts, 0, -1)) . '\\Entity\\' . $alias;
            if (!class_exists($name)) {
                return $this->_entityClass = $default;
            }

            /** @var class-string<\Cake\Datasource\EntityInterface>|null $class */
            $class = App::className($name, 'Model/Entity');
            if (!$class) {
                throw new MissingEntityException([$name]);
            }

            $this->_entityClass = $class;
        }

        return $this->_entityClass;
    }

    /**
     * Sets the class used to hydrate rows for this table.
     *
     * @param string $name The name of the class to use
     * @throws \Cake\ORM\Exception\MissingEntityException when the entity class cannot be found
     * @return $this
     */
    public function setEntityClass(string $name)
    {
        /** @var class-string<\Cake\Datasource\EntityInterface>|null $class */
        $class = App::className($name, 'Model/Entity');
        if ($class === null) {
            throw new MissingEntityException([$name]);
        }

        $this->_entityClass = $class;

        return $this;
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
     * @param array<string, mixed> $options The options for the behavior to use.
     * @return $this
     * @throws \RuntimeException If a behavior is being reloaded.
     * @see \Cake\ORM\Behavior
     */
    public function addBehavior(string $name, array $options = [])
    {
        $this->_behaviors->load($name, $options);

        return $this;
    }

    /**
     * Adds an array of behaviors to the table's behavior collection.
     *
     * Example:
     *
     * ```
     * $this->addBehaviors([
     *      'Timestamp',
     *      'Tree' => ['level' => 'level'],
     * ]);
     * ```
     *
     * @param array $behaviors All the behaviors to load.
     * @return $this
     * @throws \RuntimeException If a behavior is being reloaded.
     */
    public function addBehaviors(array $behaviors)
    {
        foreach ($behaviors as $name => $options) {
            if (is_int($name)) {
                $name = $options;
                $options = [];
            }

            $this->addBehavior($name, $options);
        }

        return $this;
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
     * @return $this
     * @see \Cake\ORM\Behavior
     */
    public function removeBehavior(string $name)
    {
        $this->_behaviors->unload($name);

        return $this;
    }

    /**
     * Returns the behavior registry for this table.
     *
     * @return \Cake\ORM\BehaviorRegistry The BehaviorRegistry instance.
     */
    public function behaviors(): BehaviorRegistry
    {
        return $this->_behaviors;
    }

    /**
     * Get a behavior from the registry.
     *
     * @param string $name The behavior alias to get from the registry.
     * @return \Cake\ORM\Behavior
     * @throws \InvalidArgumentException If the behavior does not exist.
     */
    public function getBehavior(string $name): Behavior
    {
        if (!$this->_behaviors->has($name)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` behavior is not defined on `%s`.',
                $name,
                static::class
            ));
        }

        /** @var \Cake\ORM\Behavior */
        return $this->_behaviors->get($name);
    }

    /**
     * Check if a behavior with the given alias has been loaded.
     *
     * @param string $name The behavior alias to check.
     * @return bool Whether the behavior exists.
     */
    public function hasBehavior(string $name): bool
    {
        return $this->_behaviors->has($name);
    }

    /**
     * Returns an association object configured for the specified alias.
     *
     * The name argument also supports dot syntax to access deeper associations.
     *
     * ```
     * $users = $this->getAssociation('Articles.Comments.Users');
     * ```
     *
     * Note that this method requires the association to be present or otherwise
     * throws an exception.
     * If you are not sure, use hasAssociation() before calling this method.
     *
     * @param string $name The alias used for the association.
     * @return \Cake\ORM\Association The association.
     * @throws \InvalidArgumentException
     */
    public function getAssociation(string $name): Association
    {
        $association = $this->findAssociation($name);
        if (!$association) {
            $assocations = $this->associations()->keys();

            $message = "The `{$name}` association is not defined on `{$this->getAlias()}`.";
            if ($assocations) {
                $message .= "\nValid associations are: " . implode(', ', $assocations);
            }
            throw new InvalidArgumentException($message);
        }

        return $association;
    }

    /**
     * Checks whether a specific association exists on this Table instance.
     *
     * The name argument also supports dot syntax to access deeper associations.
     *
     * ```
     * $hasUsers = $this->hasAssociation('Articles.Comments.Users');
     * ```
     *
     * @param string $name The alias used for the association.
     * @return bool
     */
    public function hasAssociation(string $name): bool
    {
        return $this->findAssociation($name) !== null;
    }

    /**
     * Returns an association object configured for the specified alias if any.
     *
     * The name argument also supports dot syntax to access deeper associations.
     *
     * ```
     * $users = $this->getAssociation('Articles.Comments.Users');
     * ```
     *
     * @param string $name The alias used for the association.
     * @return \Cake\ORM\Association|null Either the association or null.
     */
    protected function findAssociation(string $name): ?Association
    {
        if (!str_contains($name, '.')) {
            return $this->_associations->get($name);
        }

        $result = null;
        [$name, $next] = array_pad(explode('.', $name, 2), 2, null);
        if ($name !== null) {
            $result = $this->_associations->get($name);
        }

        if ($result !== null && $next !== null) {
            return $result->getTarget()->getAssociation($next);
        }

        return $result;
    }

    /**
     * Get the associations collection for this table.
     *
     * @return \Cake\ORM\AssociationCollection The collection of association objects.
     */
    public function associations(): AssociationCollection
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
     * @return $this
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

        return $this;
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
     * @param array<string, mixed> $options list of options to configure the association definition
     * @return \Cake\ORM\Association\BelongsTo
     */
    public function belongsTo(string $associated, array $options = []): BelongsTo
    {
        $options += ['sourceTable' => $this];

        /** @var \Cake\ORM\Association\BelongsTo */
        return $this->_associations->load(BelongsTo::class, $associated, $options);
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
     * @param array<string, mixed> $options list of options to configure the association definition
     * @return \Cake\ORM\Association\HasOne
     */
    public function hasOne(string $associated, array $options = []): HasOne
    {
        $options += ['sourceTable' => $this];

        /** @var \Cake\ORM\Association\HasOne */
        return $this->_associations->load(HasOne::class, $associated, $options);
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
     * @param array<string, mixed> $options list of options to configure the association definition
     * @return \Cake\ORM\Association\HasMany
     */
    public function hasMany(string $associated, array $options = []): HasMany
    {
        $options += ['sourceTable' => $this];

        /** @var \Cake\ORM\Association\HasMany */
        return $this->_associations->load(HasMany::class, $associated, $options);
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
     * @param array<string, mixed> $options list of options to configure the association definition
     * @return \Cake\ORM\Association\BelongsToMany
     */
    public function belongsToMany(string $associated, array $options = []): BelongsToMany
    {
        $options += ['sourceTable' => $this];

        /** @var \Cake\ORM\Association\BelongsToMany */
        return $this->_associations->load(BelongsToMany::class, $associated, $options);
    }

    /**
     * Creates a new Query for this repository and applies some defaults based on the
     * type of search that was selected.
     *
     * ### Model.beforeFind event
     *
     * Each find() will trigger a `Model.beforeFind` event for all attached
     * listeners. Any listener can set a valid result set using $query
     *
     * By default, following special named arguments are recognized which are
     * used as select query options:
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
     * ```
     * $query = $articles->find('all',
     *   conditions: ['published' => 1],
     *   limit: 10,
     *   contain: ['Users', 'Comments']
     * );
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
     * You can invoke a finder by specifying the type.
     *
     * This will invoke the `findPublished` method:
     *
     * ```
     * $query = $articles->find('published');
     * ```
     *
     * ## Typed finder arguments
     *
     * Finders must have a `SelectQuery` instance as their 1st argument and any
     * additional parameters as needed.
     *
     * Here, the finder "findByCategory" has an integer `$category` parameter:
     *
     * ```
     * function findByCategory(SelectQuery $query, int $category): SelectQuery
     * {
     *     return $query;
     * }
     * ```
     *
     * This finder can be called as:
     *
     * ```
     * $query = $articles->find('byCategory', $category);
     * ```
     *
     * or using named arguments as:
     * ```
     * $query = $articles->find(type: 'byCategory', category: $category);
     * ```
     *
     * @param string $type the type of query to perform
     * @param mixed ...$args Arguments that match up to finder-specific parameters
     * @return \Cake\ORM\Query\SelectQuery The query builder
     */
    public function find(string $type = 'all', mixed ...$args): SelectQuery
    {
        return $this->callFinder($type, $this->selectQuery(), ...$args);
    }

    /**
     * Returns the query as passed.
     *
     * By default findAll() applies no query clauses, you can override this
     * method in subclasses to modify how `find('all')` works.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to find with
     * @return \Cake\ORM\Query\SelectQuery The query builder
     */
    public function findAll(SelectQuery $query): SelectQuery
    {
        return $query;
    }

    /**
     * Sets up a query object so results appear as an indexed array, useful for any
     * place where you would want a list such as for populating input select boxes.
     *
     * When calling this finder, the fields passed are used to determine what should
     * be used as the array key, value and optionally what to group the results by.
     * By default, the primary key for the model is used for the key, and the display
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
     * You can specify which property will be used as the key and which as value,
     * when not specified, it will use the results of calling `primaryKey` and
     * `displayField` respectively in this table:
     *
     * ```
     * $table->find('list', keyField: 'name', valueField: 'age');
     * ```
     *
     * The `valueField` can also be an array, in which case you can also specify
     * the `valueSeparator` option to control how the values will be concatenated:
     *
     * ```
     * $table->find('list',  valueField: ['first_name', 'last_name'], valueSeparator: ' | ');
     * ```
     *
     * The results of this finder will be in the following form:
     *
     * ```
     * [
     *  1 => 'John | Doe',
     *  2 => 'Steve | Smith'
     * ]
     * ```
     *
     * Results can be put together in bigger groups when they share a property, you
     * can customize the property to use for grouping by setting `groupField`:
     *
     * ```
     * $table->find('list', groupField: 'category_id');
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
     * @param \Cake\ORM\Query\SelectQuery $query The query to find with
     * @return \Cake\ORM\Query\SelectQuery The query builder
     */
    public function findList(
        SelectQuery $query,
        Closure|array|string|null $keyField = null,
        Closure|array|string|null $valueField = null,
        Closure|array|string|null $groupField = null,
        string $valueSeparator = ' '
    ): SelectQuery {
        $keyField ??= $this->getPrimaryKey();
        $valueField ??= $this->getDisplayField();

        if (
            !$query->clause('select') &&
            !is_object($keyField) &&
            !is_object($valueField) &&
            !is_object($groupField)
        ) {
            $fields = array_merge(
                (array)$keyField,
                (array)$valueField,
                (array)$groupField
            );
            $columns = $this->getSchema()->columns();
            if (count($fields) === count(array_intersect($fields, $columns))) {
                $query->select($fields);
            }
        }

        $options = $this->_setFieldMatchers(
            compact('keyField', 'valueField', 'groupField', 'valueSeparator'),
            ['keyField', 'valueField', 'groupField']
        );

        return $query->formatResults(fn (CollectionInterface $results) => $results->combine(
            $options['keyField'],
            $options['valueField'],
            $options['groupField']
        ));
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
     * these defaults you need to provide the `keyField`, `parentField` or `nestingKey`
     * arguments:
     *
     * ```
     * $table->find('threaded', keyField: 'id', parentField: 'ancestor_id', nestingKey: 'children');
     * ```
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to find with
     * @param \Closure|array|string|null $keyField The path to the key field.
     * @param \Closure|array|string $parentField The path to the parent field.
     * @param string $nestingKey The key to nest children under.
     * @return \Cake\ORM\Query\SelectQuery The query builder
     */
    public function findThreaded(
        SelectQuery $query,
        Closure|array|string|null $keyField = null,
        Closure|array|string $parentField = 'parent_id',
        string $nestingKey = 'children'
    ): SelectQuery {
        $keyField ??= $this->getPrimaryKey();

        $options = $this->_setFieldMatchers(compact('keyField', 'parentField'), ['keyField', 'parentField']);

        return $query->formatResults(fn (CollectionInterface $results) => $results->nest(
            $options['keyField'],
            $options['parentField'],
            $nestingKey
        ));
    }

    /**
     * Out of an options array, check if the keys described in `$keys` are arrays
     * and change the values for closures that will concatenate the each of the
     * properties in the value array when passed a row.
     *
     * This is an auxiliary function used for result formatters that can accept
     * composite keys when comparing values.
     *
     * @param array<string, mixed> $options the original options passed to a finder
     * @param list<string> $keys the keys to check in $options to build matchers from
     * the associated value
     * @return array<string, mixed>
     */
    protected function _setFieldMatchers(array $options, array $keys): array
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
            $glue = in_array($field, ['keyField', 'parentField'], true) ? ';' : $options['valueSeparator'];
            $options[$field] = function ($row) use ($fields, $glue): string {
                $matches = [];
                foreach ($fields as $field) {
                    $matches[] = $row[$field];
                }

                return implode($glue, $matches);
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
     * @param mixed $primaryKey primary key value to find
     * @param array|string $finder The finder to use. Passing an options array is deprecated.
     * @param \Psr\SimpleCache\CacheInterface|string|null $cache The cache config to use.
     *   Defaults to `null`, i.e. no caching.
     * @param \Closure|string|null $cacheKey The cache key to use. If not provided
     *   one will be autogenerated if `$cache` is not null.
     * @param mixed ...$args Arguments that query options or finder specific parameters.
     * @return \Cake\Datasource\EntityInterface
     * @throws \Cake\Datasource\Exception\RecordNotFoundException if the record with such id
     * could not be found
     * @throws \Cake\Datasource\Exception\InvalidPrimaryKeyException When $primaryKey has an
     *      incorrect number of elements.
     * @see \Cake\Datasource\RepositoryInterface::find()
     */
    public function get(
        mixed $primaryKey,
        array|string $finder = 'all',
        CacheInterface|string|null $cache = null,
        Closure|string|null $cacheKey = null,
        mixed ...$args
    ): EntityInterface {
        if ($primaryKey === null) {
            throw new InvalidPrimaryKeyException(sprintf(
                'Record not found in table `%s` with primary key `[NULL]`.',
                $this->getTable()
            ));
        }

        $key = (array)$this->getPrimaryKey();
        $alias = $this->getAlias();
        foreach ($key as $index => $keyname) {
            $key[$index] = $alias . '.' . $keyname;
        }
        if (!is_array($primaryKey)) {
            $primaryKey = [$primaryKey];
        }
        if (count($key) !== count($primaryKey)) {
            $primaryKey = $primaryKey ?: [null];
            $primaryKey = array_map(function ($key) {
                return var_export($key, true);
            }, $primaryKey);

            throw new InvalidPrimaryKeyException(sprintf(
                'Record not found in table `%s` with primary key `[%s]`.',
                $this->getTable(),
                implode(', ', $primaryKey)
            ));
        }
        $conditions = array_combine($key, $primaryKey);

        if (is_array($finder)) {
            deprecationWarning(
                '5.0.0',
                'Calling Table::get() with options array is deprecated.'
                    . ' Use named arguments instead.'
            );

            $args += $finder;
            $finder = $args['finder'] ?? 'all';
            if (isset($args['cache'])) {
                $cache = $args['cache'];
            }
            if (isset($args['key'])) {
                $cacheKey = $args['key'];
            }
            unset($args['key'], $args['cache'], $args['finder']);
        }

        $query = $this->find($finder, ...$args)->where($conditions);

        if ($cache) {
            if (!$cacheKey) {
                $cacheKey = sprintf(
                    'get-%s-%s-%s',
                    $this->getConnection()->configName(),
                    $this->getTable(),
                    json_encode($primaryKey, JSON_THROW_ON_ERROR)
                );
            }
            $query->cache($cacheKey, $cache);
        }

        return $query->firstOrFail();
    }

    /**
     * Handles the logic executing of a worker inside a transaction.
     *
     * @param callable $worker The worker that will run inside the transaction.
     * @param bool $atomic Whether to execute the worker inside a database transaction.
     * @return mixed
     */
    protected function _executeTransaction(callable $worker, bool $atomic = true): mixed
    {
        if ($atomic) {
            return $this->getConnection()->transactional(fn () => $worker());
        }

        return $worker();
    }

    /**
     * Checks if the caller would have executed a commit on a transaction.
     *
     * @param bool $atomic True if an atomic transaction was used.
     * @param bool $primary True if a primary was used.
     * @return bool Returns true if a transaction was committed.
     */
    protected function _transactionCommitted(bool $atomic, bool $primary): bool
    {
        return !$this->getConnection()->inTransaction() && ($atomic || $primary);
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
     * parameter can be a callable that takes the Query as the argument, or a \Cake\ORM\Query\SelectQuery object passed
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
     * @param \Cake\ORM\Query\SelectQuery|callable|array $search The criteria to find existing
     *   records by. Note that when you pass a query object you'll have to use
     *   the 2nd arg of the method to modify the entity data before saving.
     * @param callable|null $callback A callback that will be invoked for newly
     *   created entities. This callback will be called *before* the entity
     *   is persisted.
     * @param array<string, mixed> $options The options to use when saving.
     * @return \Cake\Datasource\EntityInterface An entity.
     * @throws \Cake\ORM\Exception\PersistenceFailedException When the entity couldn't be saved
     */
    public function findOrCreate(
        SelectQuery|callable|array $search,
        ?callable $callback = null,
        array $options = []
    ): EntityInterface {
        $options = new ArrayObject($options + [
            'atomic' => true,
            'defaults' => true,
        ]);

        $entity = $this->_executeTransaction(
            fn () => $this->_processFindOrCreate($search, $callback, $options->getArrayCopy()),
            $options['atomic']
        );

        if ($entity && $this->_transactionCommitted($options['atomic'], true)) {
            $this->dispatchEvent('Model.afterSaveCommit', compact('entity', 'options'));
        }

        return $entity;
    }

    /**
     * Performs the actual find and/or create of an entity based on the passed options.
     *
     * @param \Cake\ORM\Query\SelectQuery|callable|array $search The criteria to find an existing record by, or a callable tha will
     *   customize the find query.
     * @param callable|null $callback A callback that will be invoked for newly
     *   created entities. This callback will be called *before* the entity
     *   is persisted.
     * @param array<string, mixed> $options The options to use when saving.
     * @return \Cake\Datasource\EntityInterface|array An entity.
     * @throws \Cake\ORM\Exception\PersistenceFailedException When the entity couldn't be saved
     * @throws \InvalidArgumentException
     */
    protected function _processFindOrCreate(
        SelectQuery|callable|array $search,
        ?callable $callback = null,
        array $options = []
    ): EntityInterface|array {
        $query = $this->_getFindOrCreateQuery($search);

        $row = $query->first();
        if ($row !== null) {
            return $row;
        }

        $entity = $this->newEmptyEntity();
        if ($options['defaults'] && is_array($search)) {
            $accessibleFields = array_combine(array_keys($search), array_fill(0, count($search), true));
            $entity = $this->patchEntity($entity, $search, ['accessibleFields' => $accessibleFields]);
        }
        if ($callback !== null) {
            $entity = $callback($entity) ?: $entity;
        }
        unset($options['defaults']);

        $result = $this->save($entity, $options);

        if ($result === false) {
            throw new PersistenceFailedException($entity, ['findOrCreate']);
        }

        return $entity;
    }

    /**
     * Gets the query object for findOrCreate().
     *
     * @param \Cake\ORM\Query\SelectQuery|callable|array $search The criteria to find existing records by.
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function _getFindOrCreateQuery(SelectQuery|callable|array $search): SelectQuery
    {
        if (is_callable($search)) {
            $query = $this->find();
            $search($query);
        } elseif (is_array($search)) {
            $query = $this->find()->where($search);
        } else {
            $query = $search;
        }

        return $query;
    }

    /**
     * Creates a new SelectQuery instance for a table.
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function query(): SelectQuery
    {
        return $this->selectQuery();
    }

    /**
     * Creates a new select query
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function selectQuery(): SelectQuery
    {
        return $this->queryFactory->select($this);
    }

    /**
     * Creates a new insert query
     *
     * @return \Cake\ORM\Query\InsertQuery
     */
    public function insertQuery(): InsertQuery
    {
        return $this->queryFactory->insert($this);
    }

    /**
     * Creates a new update query
     *
     * @return \Cake\ORM\Query\UpdateQuery
     */
    public function updateQuery(): UpdateQuery
    {
        return $this->queryFactory->update($this);
    }

    /**
     * Creates a new delete query
     *
     * @return \Cake\ORM\Query\DeleteQuery
     */
    public function deleteQuery(): DeleteQuery
    {
        return $this->queryFactory->delete($this);
    }

    /**
     * Creates a new Query instance with field auto aliasing disabled.
     *
     * This is useful for subqueries.
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function subquery(): SelectQuery
    {
        return $this->queryFactory->select($this)->disableAutoAliasing();
    }

    /**
     * Update all matching records.
     *
     * Sets the $fields to the provided values based on $conditions.
     * This method will *not* trigger beforeSave/afterSave events. If you need those
     * first load a collection of records and update them.
     *
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string $fields A hash of field => new value.
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $conditions Conditions to be used, accepts anything Query::where()
     * @return int Count Returns the affected rows.
     */
    public function updateAll(
        QueryExpression|Closure|array|string $fields,
        QueryExpression|Closure|array|string|null $conditions
    ): int {
        $statement = $this->updateQuery()
            ->set($fields)
            ->where($conditions)
            ->execute();

        return $statement->rowCount();
    }

    /**
     * Deletes all records matching the provided conditions.
     *
     * This method will *not* trigger beforeDelete/afterDelete events. If you
     * need those first load a collection of records and delete them.
     *
     * This method will *not* execute on associations' `cascade` attribute. You should
     * use database foreign keys + ON CASCADE rules if you need cascading deletes combined
     * with this method.
     *
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @return int Returns the number of affected rows.
     */
    public function deleteAll(QueryExpression|Closure|array|string|null $conditions): int
    {
        $statement = $this->deleteQuery()
            ->where($conditions)
            ->execute();

        return $statement->rowCount();
    }

    /**
     * @inheritDoc
     */
    public function exists(QueryExpression|Closure|array|string|null $conditions): bool
    {
        return (bool)count(
            $this->find('all')
            ->select(['existing' => 1])
            ->where($conditions)
            ->limit(1)
            ->disableHydration()
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
     * - checkRules: Whether to check the rules on entity before saving, if the checking
     *   fails, it will abort the save operation. (default:true)
     * - associated: If `true` it will save 1st level associated entities as they are found
     *   in the passed `$entity` whenever the property defined for the association
     *   is marked as dirty. If an array, it will be interpreted as the list of associations
     *   to be saved. It is possible to provide different options for saving on associated
     *   table objects using this key by making the custom options the array value.
     *   If `false` no associated records will be saved. (default: `true`)
     * - checkExisting: Whether to check if the entity already exists, assuming that the
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
     * - Model.afterSaveCommit: Will be triggered after the transaction is committed
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
     * $articles->save($entity, ['associated' => ['Comments']]);
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
     * @param \Cake\Datasource\EntityInterface $entity the entity to be saved
     * @param array<string, mixed> $options The options to use when saving.
     * @return \Cake\Datasource\EntityInterface|false
     * @throws \Cake\ORM\Exception\RolledbackTransactionException If the transaction is aborted in the afterSave event.
     */
    public function save(
        EntityInterface $entity,
        array $options = []
    ): EntityInterface|false {
        $options = new ArrayObject($options + [
            'atomic' => true,
            'associated' => true,
            'checkRules' => true,
            'checkExisting' => true,
            '_primary' => true,
            '_cleanOnSuccess' => true,
        ]);

        if ($entity->hasErrors((bool)$options['associated'])) {
            return false;
        }

        if ($entity->isNew() === false && !$entity->isDirty()) {
            return $entity;
        }

        $success = $this->_executeTransaction(
            fn () => $this->_processSave($entity, $options),
            $options['atomic']
        );

        if ($success) {
            if ($this->_transactionCommitted($options['atomic'], $options['_primary'])) {
                $this->dispatchEvent('Model.afterSaveCommit', compact('entity', 'options'));
            }
            if ($options['atomic'] || $options['_primary']) {
                if ($options['_cleanOnSuccess']) {
                    $entity->clean();
                    $entity->setNew(false);
                }
                $entity->setSource($this->getRegistryAlias());
            }
        }

        return $success;
    }

    /**
     * Try to save an entity or throw a PersistenceFailedException if the application rules checks failed,
     * the entity contains errors or the save was aborted by a callback.
     *
     * @param \Cake\Datasource\EntityInterface $entity the entity to be saved
     * @param array<string, mixed> $options The options to use when saving.
     * @return \Cake\Datasource\EntityInterface
     * @throws \Cake\ORM\Exception\PersistenceFailedException When the entity couldn't be saved
     * @see \Cake\ORM\Table::save()
     */
    public function saveOrFail(EntityInterface $entity, array $options = []): EntityInterface
    {
        $saved = $this->save($entity, $options);
        if ($saved === false) {
            throw new PersistenceFailedException($entity, ['save']);
        }

        return $saved;
    }

    /**
     * Performs the actual saving of an entity based on the passed options.
     *
     * @param \Cake\Datasource\EntityInterface $entity the entity to be saved
     * @param \ArrayObject<string, mixed> $options the options to use for the save operation
     * @return \Cake\Datasource\EntityInterface|false
     * @throws \Cake\Database\Exception\DatabaseException When an entity is missing some of the primary keys.
     * @throws \Cake\ORM\Exception\RolledbackTransactionException If the transaction
     *   is aborted in the afterSave event.
     */
    protected function _processSave(EntityInterface $entity, ArrayObject $options): EntityInterface|false
    {
        $primaryColumns = (array)$this->getPrimaryKey();

        if ($options['checkExisting'] && $primaryColumns && $entity->isNew() && $entity->has($primaryColumns)) {
            $alias = $this->getAlias();
            $conditions = [];
            foreach ($entity->extract($primaryColumns) as $k => $v) {
                $conditions["{$alias}.{$k}"] = $v;
            }
            $entity->setNew(!$this->exists($conditions));
        }

        $mode = $entity->isNew() ? RulesChecker::CREATE : RulesChecker::UPDATE;
        if ($options['checkRules'] && !$this->checkRules($entity, $mode, $options)) {
            return false;
        }

        $options['associated'] = $this->_associations->normalizeKeys($options['associated']);
        $event = $this->dispatchEvent('Model.beforeSave', compact('entity', 'options'));

        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result === null) {
                return false;
            }

            if ($result !== false) {
                assert(
                    $result instanceof EntityInterface,
                    sprintf(
                        'The beforeSave callback must return `false` or `EntityInterface` instance. Got `%s` instead.',
                        get_debug_type($result)
                    )
                );
            }

            return $result;
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

        $data = $entity->extract($this->getSchema()->columns(), true);
        $isNew = $entity->isNew();

        if ($isNew) {
            $success = $this->_insert($entity, $data);
        } else {
            $success = $this->_update($entity, $data);
        }

        if ($success) {
            $success = $this->_onSaveSuccess($entity, $options);
        }

        if (!$success && $isNew) {
            $entity->unset($this->getPrimaryKey());
            $entity->setNew(true);
        }

        return $success ? $entity : false;
    }

    /**
     * Handles the saving of children associations and executing the afterSave logic
     * once the entity for this table has been saved successfully.
     *
     * @param \Cake\Datasource\EntityInterface $entity the entity to be saved
     * @param \ArrayObject<string, mixed> $options the options to use for the save operation
     * @return bool True on success
     * @throws \Cake\ORM\Exception\RolledbackTransactionException If the transaction
     *   is aborted in the afterSave event.
     */
    protected function _onSaveSuccess(EntityInterface $entity, ArrayObject $options): bool
    {
        $success = $this->_associations->saveChildren(
            $this,
            $entity,
            $options['associated'],
            ['_primary' => false] + $options->getArrayCopy()
        );

        if (!$success && $options['atomic']) {
            return false;
        }

        $this->dispatchEvent('Model.afterSave', compact('entity', 'options'));

        if ($options['atomic'] && !$this->getConnection()->inTransaction()) {
            throw new RolledbackTransactionException(['table' => static::class]);
        }

        if (!$options['atomic'] && !$options['_primary']) {
            $entity->clean();
            $entity->setNew(false);
            $entity->setSource($this->getRegistryAlias());
        }

        return true;
    }

    /**
     * Auxiliary function to handle the insert of an entity's data in the table
     *
     * @param \Cake\Datasource\EntityInterface $entity the subject entity from were $data was extracted
     * @param array $data The actual data that needs to be saved
     * @return \Cake\Datasource\EntityInterface|false
     * @throws \Cake\Database\Exception\DatabaseException if not all the primary keys where supplied or could
     * be generated when the table has composite primary keys. Or when the table has no primary key.
     */
    protected function _insert(EntityInterface $entity, array $data): EntityInterface|false
    {
        $primary = (array)$this->getPrimaryKey();
        if (!$primary) {
            $msg = sprintf(
                'Cannot insert row in `%s` table, it has no primary key.',
                $this->getTable()
            );
            throw new DatabaseException($msg);
        }
        $keys = array_fill(0, count($primary), null);
        $id = (array)$this->_newId($primary) + $keys;

        // Generate primary keys preferring values in $data.
        $primary = array_combine($primary, $id);
        $primary = array_intersect_key($data, $primary) + $primary;

        $filteredKeys = array_filter($primary, function ($v) {
            return $v !== null;
        });
        $data += $filteredKeys;

        if (count($primary) > 1) {
            $schema = $this->getSchema();
            foreach ($primary as $k => $v) {
                if (!isset($data[$k]) && empty($schema->getColumn($k)['autoIncrement'])) {
                    $msg = 'Cannot insert row, some of the primary key values are missing. ';
                    $msg .= sprintf(
                        'Got (%s), expecting (%s)',
                        implode(', ', $filteredKeys + $entity->extract(array_keys($primary))),
                        implode(', ', array_keys($primary))
                    );
                    throw new DatabaseException($msg);
                }
            }
        }

        if (!$data) {
            return false;
        }

        $statement = $this->insertQuery()->insert(array_keys($data))
            ->values($data)
            ->execute();

        $success = false;
        if ($statement->rowCount() !== 0) {
            $success = $entity;
            $entity->set($filteredKeys, ['guard' => false]);
            $schema = $this->getSchema();
            $driver = $this->getConnection()->getDriver();
            foreach ($primary as $key => $v) {
                if (!isset($data[$key])) {
                    $id = $statement->lastInsertId($this->getTable(), $key);
                    $type = $schema->getColumnType($key);
                    assert($type !== null);
                    $entity->set($key, TypeFactory::build($type)->toPHP($id, $driver));
                    break;
                }
            }
        }

        return $success;
    }

    /**
     * Generate a primary key value for a new record.
     *
     * By default, this uses the type system to generate a new primary key
     * value if possible. You can override this method if you have specific requirements
     * for id generation.
     *
     * Note: The ORM will not generate primary key values for composite primary keys.
     * You can overwrite _newId() in your table class.
     *
     * @param list<string> $primary The primary key columns to get a new ID for.
     * @return string|null Either null or the primary key value or a list of primary key values.
     */
    protected function _newId(array $primary): ?string
    {
        if (!$primary || count($primary) > 1) {
            return null;
        }
        $typeName = $this->getSchema()->getColumnType($primary[0]);
        assert($typeName !== null);
        $type = TypeFactory::build($typeName);

        return $type->newId();
    }

    /**
     * Auxiliary function to handle the update of an entity's data in the table
     *
     * @param \Cake\Datasource\EntityInterface $entity the subject entity from were $data was extracted
     * @param array $data The actual data that needs to be saved
     * @return \Cake\Datasource\EntityInterface|false
     * @throws \InvalidArgumentException When primary key data is missing.
     */
    protected function _update(EntityInterface $entity, array $data): EntityInterface|false
    {
        $primaryColumns = (array)$this->getPrimaryKey();
        $primaryKey = $entity->extract($primaryColumns);

        $data = array_diff_key($data, $primaryKey);
        if (!$data) {
            return $entity;
        }

        if ($primaryColumns === []) {
            $entityClass = $entity::class;
            $table = $this->getTable();
            $message = "Cannot update `{$entityClass}`. The `{$table}` has no primary key.";
            throw new InvalidArgumentException($message);
        }

        if (!$entity->has($primaryColumns)) {
            $message = 'All primary key value(s) are needed for updating, ';
            $message .= $entity::class . ' is missing ' . implode(', ', $primaryColumns);
            throw new InvalidArgumentException($message);
        }

        $statement = $this->updateQuery()
            ->set($data)
            ->where($primaryKey)
            ->execute();

        return $statement->errorCode() === '00000' ? $entity : false;
    }

    /**
     * Persists multiple entities of a table.
     *
     * The records will be saved in a transaction which will be rolled back if
     * any one of the records fails to save due to failed validation or database
     * error.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to save.
     * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
     * @return iterable<\Cake\Datasource\EntityInterface>|false False on failure, entities list on success.
     * @throws \Exception
     */
    public function saveMany(
        iterable $entities,
        array $options = []
    ): iterable|false {
        try {
            return $this->_saveMany($entities, $options);
        } catch (PersistenceFailedException) {
            return false;
        }
    }

    /**
     * Persists multiple entities of a table.
     *
     * The records will be saved in a transaction which will be rolled back if
     * any one of the records fails to save due to failed validation or database
     * error.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to save.
     * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
     * @return iterable<\Cake\Datasource\EntityInterface> Entities list.
     * @throws \Exception
     * @throws \Cake\ORM\Exception\PersistenceFailedException If an entity couldn't be saved.
     */
    public function saveManyOrFail(iterable $entities, array $options = []): iterable
    {
        return $this->_saveMany($entities, $options);
    }

    /**
     * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to save.
     * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
     * @throws \Cake\ORM\Exception\PersistenceFailedException If an entity couldn't be saved.
     * @throws \Exception If an entity couldn't be saved.
     * @return iterable<\Cake\Datasource\EntityInterface> Entities list.
     */
    protected function _saveMany(
        iterable $entities,
        array $options = []
    ): iterable {
        $options = new ArrayObject(
            $options + [
                'atomic' => true,
                'checkRules' => true,
                '_primary' => true,
            ]
        );
        $options['_cleanOnSuccess'] = false;

        /** @var array<bool> $isNew */
        $isNew = [];
        $cleanupOnFailure = function ($entities) use (&$isNew): void {
            /** @var iterable<\Cake\Datasource\EntityInterface> $entities */
            foreach ($entities as $key => $entity) {
                if (isset($isNew[$key]) && $isNew[$key]) {
                    $entity->unset($this->getPrimaryKey());
                    $entity->setNew(true);
                }
            }
        };

        /** @var \Cake\Datasource\EntityInterface|null $failed */
        $failed = null;
        try {
            $this->getConnection()
                ->transactional(function () use ($entities, $options, &$isNew, &$failed) {
                    // Cache array cast since options are the same for each entity
                    $options = (array)$options;
                    foreach ($entities as $key => $entity) {
                        $isNew[$key] = $entity->isNew();
                        if ($this->save($entity, $options) === false) {
                            $failed = $entity;

                            return false;
                        }
                    }
                });
        } catch (Exception $e) {
            $cleanupOnFailure($entities);

            throw $e;
        }

        if ($failed !== null) {
            $cleanupOnFailure($entities);

            throw new PersistenceFailedException($failed, ['saveMany']);
        }

        $cleanupOnSuccess = function (EntityInterface $entity) use (&$cleanupOnSuccess): void {
            $entity->clean();
            $entity->setNew(false);

            foreach (array_keys($entity->toArray()) as $field) {
                $value = $entity->get($field);

                if ($value instanceof EntityInterface) {
                    $cleanupOnSuccess($value);
                } elseif (is_array($value) && current($value) instanceof EntityInterface) {
                    foreach ($value as $associated) {
                        $cleanupOnSuccess($associated);
                    }
                }
            }
        };

        if ($this->_transactionCommitted($options['atomic'], $options['_primary'])) {
            foreach ($entities as $entity) {
                $this->dispatchEvent('Model.afterSaveCommit', compact('entity', 'options'));
                if ($options['atomic'] || $options['_primary']) {
                    $cleanupOnSuccess($entity);
                }
            }
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
     * @param \Cake\Datasource\EntityInterface $entity The entity to remove.
     * @param array<string, mixed> $options The options for the delete.
     * @return bool success
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        $options = new ArrayObject($options + [
            'atomic' => true,
            'checkRules' => true,
            '_primary' => true,
        ]);

        $success = $this->_executeTransaction(
            fn () => $this->_processDelete($entity, $options),
            $options['atomic']
        );

        if ($success && $this->_transactionCommitted($options['atomic'], $options['_primary'])) {
            $this->dispatchEvent('Model.afterDeleteCommit', [
                'entity' => $entity,
                'options' => $options,
            ]);
        }

        return $success;
    }

    /**
     * Deletes multiple entities of a table.
     *
     * The records will be deleted in a transaction which will be rolled back if
     * any one of the records fails to delete due to failed validation or database
     * error.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to delete.
     * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
     * @return iterable<\Cake\Datasource\EntityInterface>|false Entities list
     *   on success, false on failure.
     * @see \Cake\ORM\Table::delete() for options and events related to this method.
     */
    public function deleteMany(iterable $entities, array $options = []): iterable|false
    {
        $failed = $this->_deleteMany($entities, $options);

        if ($failed !== null) {
            return false;
        }

        return $entities;
    }

    /**
     * Deletes multiple entities of a table.
     *
     * The records will be deleted in a transaction which will be rolled back if
     * any one of the records fails to delete due to failed validation or database
     * error.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to delete.
     * @param array<string, mixed> $options Options used when calling Table::save() for each entity.
     * @return iterable<\Cake\Datasource\EntityInterface> Entities list.
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     * @see \Cake\ORM\Table::delete() for options and events related to this method.
     */
    public function deleteManyOrFail(iterable $entities, array $options = []): iterable
    {
        $failed = $this->_deleteMany($entities, $options);

        if ($failed !== null) {
            throw new PersistenceFailedException($failed, ['deleteMany']);
        }

        return $entities;
    }

    /**
     * @param iterable<\Cake\Datasource\EntityInterface> $entities Entities to delete.
     * @param array<string, mixed> $options Options used.
     * @return \Cake\Datasource\EntityInterface|null
     */
    protected function _deleteMany(iterable $entities, array $options = []): ?EntityInterface
    {
        $options = new ArrayObject($options + [
                'atomic' => true,
                'checkRules' => true,
                '_primary' => true,
            ]);

        $failed = $this->_executeTransaction(function () use ($entities, $options) {
            foreach ($entities as $entity) {
                if (!$this->_processDelete($entity, $options)) {
                    return $entity;
                }
            }

            return null;
        }, $options['atomic']);

        if ($failed === null && $this->_transactionCommitted($options['atomic'], $options['_primary'])) {
            foreach ($entities as $entity) {
                $this->dispatchEvent('Model.afterDeleteCommit', [
                    'entity' => $entity,
                    'options' => $options,
                ]);
            }
        }

        return $failed;
    }

    /**
     * Try to delete an entity or throw a PersistenceFailedException if the entity is new,
     * has no primary key value, application rules checks failed or the delete was aborted by a callback.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to remove.
     * @param array<string, mixed> $options The options for the delete.
     * @return true
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     * @see \Cake\ORM\Table::delete()
     */
    public function deleteOrFail(EntityInterface $entity, array $options = []): bool
    {
        $deleted = $this->delete($entity, $options);
        if ($deleted === false) {
            throw new PersistenceFailedException($entity, ['delete']);
        }

        return $deleted;
    }

    /**
     * Perform the delete operation.
     *
     * Will delete the entity provided. Will remove rows from any
     * dependent associations, and clear out join tables for BelongsToMany associations.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to delete.
     * @param \ArrayObject<string, mixed> $options The options for the delete.
     * @throws \InvalidArgumentException if there are no primary key values of the
     * passed entity
     * @return bool success
     */
    protected function _processDelete(EntityInterface $entity, ArrayObject $options): bool
    {
        if ($entity->isNew()) {
            return false;
        }

        $primaryKey = (array)$this->getPrimaryKey();
        if (!$entity->has($primaryKey)) {
            $msg = 'Deleting requires all primary key values.';
            throw new InvalidArgumentException($msg);
        }

        if ($options['checkRules'] && !$this->checkRules($entity, RulesChecker::DELETE, $options)) {
            return false;
        }

        $event = $this->dispatchEvent('Model.beforeDelete', [
            'entity' => $entity,
            'options' => $options,
        ]);

        if ($event->isStopped()) {
            return (bool)$event->getResult();
        }

        $success = $this->_associations->cascadeDelete(
            $entity,
            ['_primary' => false] + $options->getArrayCopy()
        );
        if (!$success) {
            return $success;
        }

        $statement = $this->deleteQuery()
            ->where($entity->extract($primaryKey))
            ->execute();

        if ($statement->rowCount() < 1) {
            return false;
        }

        $this->dispatchEvent('Model.afterDelete', [
            'entity' => $entity,
            'options' => $options,
        ]);

        return true;
    }

    /**
     * Returns true if the finder exists for the table
     *
     * @param string $type name of finder to check
     * @return bool
     */
    public function hasFinder(string $type): bool
    {
        $finder = 'find' . $type;

        return method_exists($this, $finder) || $this->_behaviors->hasFinder($type);
    }

    /**
     * Calls a finder method and applies it to the passed query.
     *
     * @internal
     * @template TSubject of \Cake\Datasource\EntityInterface|array
     * @param string $type Name of the finder to be called.
     * @param \Cake\ORM\Query\SelectQuery<TSubject> $query The query object to apply the finder options to.
     * @param mixed ...$args Arguments that match up to finder-specific parameters
     * @return \Cake\ORM\Query\SelectQuery<TSubject>
     * @throws \BadMethodCallException
     * @uses findAll()
     * @uses findList()
     * @uses findThreaded()
     */
    public function callFinder(string $type, SelectQuery $query, mixed ...$args): SelectQuery
    {
        $finder = 'find' . $type;
        if (method_exists($this, $finder)) {
            return $this->invokeFinder($this->{$finder}(...), $query, $args);
        }

        if ($this->_behaviors->hasFinder($type)) {
            return $this->_behaviors->callFinder($type, $query, ...$args);
        }

        throw new BadMethodCallException(sprintf(
            'Unknown finder method `%s` on `%s`.',
            $type,
            static::class
        ));
    }

    /**
     * @internal
     * @template TSubject of \Cake\Datasource\EntityInterface|array
     * @param \Closure $callable Callable.
     * @param \Cake\ORM\Query\SelectQuery<TSubject> $query The query object.
     * @param array $args Arguments for the callable.
     * @return \Cake\ORM\Query\SelectQuery<TSubject>
     */
    public function invokeFinder(Closure $callable, SelectQuery $query, array $args): SelectQuery
    {
        $reflected = new ReflectionFunction($callable);
        $params = $reflected->getParameters();
        $secondParam = $params[1] ?? null;

        $secondParamType = $secondParam?->getType();
        $secondParamTypeName = $secondParamType instanceof ReflectionNamedType ? $secondParamType->getName() : null;

        $secondParamIsOptions = (
            count($params) === 2 &&
            $secondParam?->name === 'options' &&
            !$secondParam->isVariadic() &&
            ($secondParamType === null || $secondParamTypeName === 'array')
        );

        if (($args === [] || isset($args[0])) && $secondParamIsOptions) {
            // Backwards compatibility of 4.x style finders
            // with signature `findFoo(SelectQuery $query, array $options)`
            // called as `find('foo')` or `find('foo', [..])`
            if (isset($args[0])) {
                deprecationWarning(
                    '5.0.0',
                    'Calling finders with options arrays is deprecated.'
                    . ' Update your finder methods to used named arguments instead.'
                );
                $args = $args[0];
            }
            $query->applyOptions($args);

            return $callable($query, $query->getOptions());
        }

        // Backwards compatibility for 4.x style finders with signatures like
        // `findFoo(SelectQuery $query, array $options)` called as
        // `find('foo', key: $value)`.
        if (!isset($args[0]) && $secondParamIsOptions) {
            $query->applyOptions($args);

            return $callable($query, $query->getOptions());
        }

        // Backwards compatibility for core finders like `findList()` called in 4.x
        // style with an array `find('list', ['valueField' => 'foo'])` instead of
        // `find('list', valueField: 'foo')`
        if (isset($args[0]) && is_array($args[0]) && $secondParamTypeName !== 'array') {
            deprecationWarning(
                '5.0.0',
                "Calling `{$reflected->getName()}` finder with options array is deprecated."
                 . ' Use named arguments instead.'
            );

            $args = $args[0];
        }

        if ($args) {
            $query->applyOptions($args);
            // Fetch custom args without the query options.
            $args = $query->getOptions();

            unset($params[0]);
            $lastParam = end($params);
            reset($params);

            if ($lastParam === false || !$lastParam->isVariadic()) {
                $paramNames = [];
                foreach ($params as $param) {
                    $paramNames[] = $param->getName();
                }

                foreach ($args as $key => $value) {
                    if (is_string($key) && !in_array($key, $paramNames, true)) {
                        unset($args[$key]);
                    }
                }
            }
        }

        return $callable($query, ...$args);
    }

    /**
     * Provides the dynamic findBy and findAllBy methods.
     *
     * @param string $method The method name that was fired.
     * @param array $args List of arguments passed to the function.
     * @return \Cake\ORM\Query\SelectQuery
     * @throws \BadMethodCallException when there are missing arguments, or when
     *  and & or are combined.
     */
    protected function _dynamicFinder(string $method, array $args): SelectQuery
    {
        $method = Inflector::underscore($method);
        preg_match('/^find_([\w]+)_by_/', $method, $matches);
        if (!$matches) {
            // find_by_ is 8 characters.
            $fields = substr($method, 8);
            $findType = 'all';
        } else {
            $fields = substr($method, strlen($matches[0]));
            $findType = Inflector::variable($matches[1]);
        }
        $hasOr = str_contains($fields, '_or_');
        $hasAnd = str_contains($fields, '_and_');

        $makeConditions = function ($fields, $args): array {
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

        if ($hasOr && $hasAnd) {
            throw new BadMethodCallException(
                'Cannot mix "and" & "or" in a magic finder. Use find() instead.'
            );
        }

        if ($hasOr === false && $hasAnd === false) {
            $conditions = $makeConditions([$fields], $args);
        } elseif ($hasOr) {
            $fields = explode('_or_', $fields);
            $conditions = [
                'OR' => $makeConditions($fields, $args),
            ];
        } else {
            $fields = explode('_and_', $fields);
            $conditions = $makeConditions($fields, $args);
        }

        return $this->find($findType, conditions: $conditions);
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
    public function __call(string $method, array $args): mixed
    {
        if ($this->_behaviors->hasMethod($method)) {
            return $this->_behaviors->call($method, $args);
        }
        if (preg_match('/^find(?:\w+)?By/', $method) > 0) {
            return $this->_dynamicFinder($method, $args);
        }

        throw new BadMethodCallException(
            sprintf('Unknown method `%s` called on `%s`', $method, static::class)
        );
    }

    /**
     * Returns the association named after the passed value if exists, otherwise
     * throws an exception.
     *
     * @param string $property the association name
     * @return \Cake\ORM\Association
     * @throws \Cake\Database\Exception\DatabaseException if no association with such name exists
     */
    public function __get(string $property): Association
    {
        $association = $this->_associations->get($property);
        if (!$association) {
            throw new DatabaseException(sprintf(
                'Undefined property `%s`. ' .
                'You have not defined the `%s` association on `%s`.',
                $property,
                $property,
                static::class
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
    public function __isset(string $property): bool
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
    public function marshaller(): Marshaller
    {
        return new Marshaller($this);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Datasource\EntityInterface
     */
    public function newEmptyEntity(): EntityInterface
    {
        $class = $this->getEntityClass();

        return new $class([], ['source' => $this->getRegistryAlias()]);
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
     *   $this->request->getData(),
     *   ['associated' => ['Tags', 'Comments.Users']]
     * );
     * ```
     *
     * You can limit fields that will be present in the constructed entity by
     * passing the `fields` option, which is also accepted for associations:
     *
     * ```
     * $article = $this->Articles->newEntity($this->request->getData(), [
     *  'fields' => ['title', 'body', 'tags', 'comments'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fields' => 'username']]
     * ]
     * );
     * ```
     *
     * The `fields` option lets remove or restrict input data from ending up in
     * the entity. If you'd like to relax the entity's default accessible fields,
     * you can use the `accessibleFields` option:
     *
     * ```
     * $article = $this->Articles->newEntity(
     *   $this->request->getData(),
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
     *   $this->request->getData(),
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
     *
     * @param array $data The data to build an entity with.
     * @param array<string, mixed> $options A list of options for the object hydration.
     * @return \Cake\Datasource\EntityInterface
     * @see \Cake\ORM\Marshaller::one()
     */
    public function newEntity(array $data, array $options = []): EntityInterface
    {
        $options['associated'] ??= $this->_associations->keys();

        return $this->marshaller()->one($data, $options);
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
     *   $this->request->getData(),
     *   ['associated' => ['Tags', 'Comments.Users']]
     * );
     * ```
     *
     * You can limit fields that will be present in the constructed entities by
     * passing the `fields` option, which is also accepted for associations:
     *
     * ```
     * $articles = $this->Articles->newEntities($this->request->getData(), [
     *  'fields' => ['title', 'body', 'tags', 'comments'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fields' => 'username']]
     *  ]
     * );
     * ```
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     *
     * @param array $data The data to build an entity with.
     * @param array<string, mixed> $options A list of options for the objects hydration.
     * @return array<\Cake\Datasource\EntityInterface> An array of hydrated records.
     */
    public function newEntities(array $data, array $options = []): array
    {
        $options['associated'] ??= $this->_associations->keys();

        return $this->marshaller()->many($data, $options);
    }

    /**
     * {@inheritDoc}
     *
     * When merging HasMany or BelongsToMany associations, all the entities in the
     * `$data` array will appear, those that can be matched by primary key will get
     * the data merged, but those that cannot, will be discarded.
     *
     * You can limit fields that will be present in the merged entity by
     * passing the `fields` option, which is also accepted for associations:
     *
     * ```
     * $article = $this->Articles->patchEntity($article, $this->request->getData(), [
     *  'fields' => ['title', 'body', 'tags', 'comments'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fields' => 'username']]
     *  ]
     * );
     * ```
     *
     * ```
     * $article = $this->Articles->patchEntity($article, $this->request->getData(), [
     *   'associated' => [
     *     'Tags' => ['accessibleFields' => ['*' => true]]
     *   ]
     * ]);
     * ```
     *
     * By default, the data is validated before being passed to the entity. In
     * the case of invalid fields, those will not be assigned to the entity.
     * The `validate` option can be used to disable validation on the passed data:
     *
     * ```
     * $article = $this->patchEntity($article, $this->request->getData(),[
     *  'validate' => false
     * ]);
     * ```
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     *
     * When patching scalar values (null/booleans/string/integer/float), if the property
     * presently has an identical value, the setter will not be called, and the
     * property will not be marked as dirty. This is an optimization to prevent unnecessary field
     * updates when persisting entities.
     *
     * @param \Cake\Datasource\EntityInterface $entity the entity that will get the
     * data merged in
     * @param array $data key value list of fields to be merged into the entity
     * @param array<string, mixed> $options A list of options for the object hydration.
     * @return \Cake\Datasource\EntityInterface
     * @see \Cake\ORM\Marshaller::merge()
     */
    public function patchEntity(EntityInterface $entity, array $data, array $options = []): EntityInterface
    {
        $options['associated'] ??= $this->_associations->keys();

        return $this->marshaller()->merge($entity, $data, $options);
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
     * passing the `fields` option, which is also accepted for associations:
     *
     * ```
     * $articles = $this->Articles->patchEntities($articles, $this->request->getData(), [
     *  'fields' => ['title', 'body', 'tags', 'comments'],
     *  'associated' => ['Tags', 'Comments.Users' => ['fields' => 'username']]
     *  ]
     * );
     * ```
     *
     * You can use the `Model.beforeMarshal` event to modify request data
     * before it is converted into entities.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities the entities that will get the
     * data merged in
     * @param array $data list of arrays to be merged into the entities
     * @param array<string, mixed> $options A list of options for the objects hydration.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    public function patchEntities(iterable $entities, array $data, array $options = []): array
    {
        $options['associated'] ??= $this->_associations->keys();

        return $this->marshaller()->mergeMany($entities, $data, $options);
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
     * @param mixed $value The value of column to be checked for uniqueness.
     * @param array<string, mixed> $options The options array, optionally containing the 'scope' key.
     *   May also be the validation context, if there are no options.
     * @param array|null $context Either the validation context or null.
     * @return bool True if the value is unique, or false if a non-scalar, non-unique value was given.
     */
    public function validateUnique(mixed $value, array $options, ?array $context = null): bool
    {
        if ($context === null) {
            $context = $options;
        }
        $entity = new Entity(
            $context['data'],
            [
                'useSetters' => false,
                'markNew' => $context['newRecord'],
                'source' => $this->getRegistryAlias(),
            ]
        );
        $fields = array_merge(
            [$context['field']],
            isset($options['scope']) ? (array)$options['scope'] : []
        );
        $values = $entity->extract($fields);
        foreach ($values as $field) {
            if ($field !== null && !is_scalar($field)) {
                return false;
            }
        }
        $class = static::IS_UNIQUE_CLASS;
        /** @var \Cake\ORM\Rule\IsUnique $rule */
        $rule = new $class($fields, $options);

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
     * - Model.afterMarshal => afterMarshal
     * - Model.buildValidator => buildValidator
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
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'Model.beforeMarshal' => 'beforeMarshal',
            'Model.afterMarshal' => 'afterMarshal',
            'Model.buildValidator' => 'buildValidator',
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
    public function buildRules(RulesChecker $rules): RulesChecker
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
     * @param \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface> $entities a single entity or list of entities
     * @param array $contain A `contain()` compatible array.
     * @see \Cake\ORM\Query::contain()
     * @return \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface>
     */
    public function loadInto(EntityInterface|array $entities, array $contain): EntityInterface|array
    {
        return (new LazyEagerLoader())->loadInto($entities, $contain, $this);
    }

    /**
     * @inheritDoc
     */
    protected function validationMethodExists(string $name): bool
    {
        return method_exists($this, $name) || $this->behaviors()->hasMethod($name);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $conn = $this->getConnection();

        return [
            'registryAlias' => $this->getRegistryAlias(),
            'table' => $this->getTable(),
            'alias' => $this->getAlias(),
            'entityClass' => $this->getEntityClass(),
            'associations' => $this->_associations->keys(),
            'behaviors' => $this->_behaviors->loaded(),
            'defaultConnection' => static::defaultConnectionName(),
            'connectionName' => $conn->configName(),
        ];
    }
}
