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

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\App;
use Cake\Core\ConventionsTrait;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;
use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * An Association is a relationship established between two tables and is used
 * to configure and customize the way interconnected records are retrieved.
 *
 * @mixin \Cake\ORM\Table
 */
abstract class Association
{
    use ConventionsTrait;
    use LocatorAwareTrait;

    /**
     * Strategy name to use joins for fetching associated records
     *
     * @var string
     */
    public const STRATEGY_JOIN = 'join';

    /**
     * Strategy name to use a subquery for fetching associated records
     *
     * @var string
     */
    public const STRATEGY_SUBQUERY = 'subquery';

    /**
     * Strategy name to use a select for fetching associated records
     *
     * @var string
     */
    public const STRATEGY_SELECT = 'select';

    /**
     * Association type for one to one associations.
     *
     * @var string
     */
    public const ONE_TO_ONE = 'oneToOne';

    /**
     * Association type for one to many associations.
     *
     * @var string
     */
    public const ONE_TO_MANY = 'oneToMany';

    /**
     * Association type for many to many associations.
     *
     * @var string
     */
    public const MANY_TO_MANY = 'manyToMany';

    /**
     * Association type for many to one associations.
     *
     * @var string
     */
    public const MANY_TO_ONE = 'manyToOne';

    /**
     * Name given to the association, it usually represents the alias
     * assigned to the target associated table
     *
     * @var string
     */
    protected $_name;

    /**
     * The class name of the target table object
     *
     * @var string
     */
    protected $_className;

    /**
     * The field name in the owning side table that is used to match with the foreignKey
     *
     * @var array<string>|string|null
     */
    protected $_bindingKey;

    /**
     * The name of the field representing the foreign key to the table to load
     *
     * @var array<string>|string
     */
    protected $_foreignKey;

    /**
     * A list of conditions to be always included when fetching records from
     * the target association
     *
     * @var \Closure|array
     */
    protected $_conditions = [];

    /**
     * Whether the records on the target table are dependent on the source table,
     * often used to indicate that records should be removed if the owning record in
     * the source table is deleted.
     *
     * @var bool
     */
    protected $_dependent = false;

    /**
     * Whether cascaded deletes should also fire callbacks.
     *
     * @var bool
     */
    protected $_cascadeCallbacks = false;

    /**
     * Source table instance
     *
     * @var \Cake\ORM\Table
     */
    protected $_sourceTable;

    /**
     * Target table instance
     *
     * @var \Cake\ORM\Table
     */
    protected $_targetTable;

    /**
     * The type of join to be used when adding the association to a query
     *
     * @var string
     */
    protected $_joinType = Query::JOIN_TYPE_LEFT;

    /**
     * The property name that should be filled with data from the target table
     * in the source table record.
     *
     * @var string
     */
    protected $_propertyName;

    /**
     * The strategy name to be used to fetch associated records. Some association
     * types might not implement but one strategy to fetch records.
     *
     * @var string
     */
    protected $_strategy = self::STRATEGY_JOIN;

    /**
     * The default finder name to use for fetching rows from the target table
     * With array value, finder name and default options are allowed.
     *
     * @var array|string
     */
    protected $_finder = 'all';

    /**
     * Valid strategies for this association. Subclasses can narrow this down.
     *
     * @var array<string>
     */
    protected $_validStrategies = [
        self::STRATEGY_JOIN,
        self::STRATEGY_SELECT,
        self::STRATEGY_SUBQUERY,
    ];

    /**
     * Constructor. Subclasses can override _options function to get the original
     * list of passed options if expecting any other special key
     *
     * @param string $alias The name given to the association
     * @param array<string, mixed> $options A list of properties to be set on this object
     */
    public function __construct(string $alias, array $options = [])
    {
        $defaults = [
            'cascadeCallbacks',
            'className',
            'conditions',
            'dependent',
            'finder',
            'bindingKey',
            'foreignKey',
            'joinType',
            'tableLocator',
            'propertyName',
            'sourceTable',
            'targetTable',
        ];
        foreach ($defaults as $property) {
            if (isset($options[$property])) {
                $this->{'_' . $property} = $options[$property];
            }
        }

        if (empty($this->_className)) {
            $this->_className = $alias;
        }

        [, $name] = pluginSplit($alias);
        $this->_name = $name;

        $this->_options($options);

        if (!empty($options['strategy'])) {
            $this->setStrategy($options['strategy']);
        }
    }

    /**
     * Sets the name for this association, usually the alias
     * assigned to the target associated table
     *
     * @param string $name Name to be assigned
     * @return $this
     * @deprecated 4.3.0 Changing the association name after object creation is
     *   no longer supported. The name should only be set through the constructor.
     */
    public function setName(string $name)
    {
        deprecationWarning(
            'Changing the association name after object creation is no longer supported.'
            . ' The name should only be set through the constructor'
        );

        if ($this->_targetTable !== null) {
            $alias = $this->_targetTable->getAlias();
            if ($alias !== $name) {
                throw new InvalidArgumentException(sprintf(
                    'Association name "%s" does not match target table alias "%s".',
                    $name,
                    $alias
                ));
            }
        }

        $this->_name = $name;

        return $this;
    }

    /**
     * Gets the name for this association, usually the alias
     * assigned to the target associated table
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Sets whether cascaded deletes should also fire callbacks.
     *
     * @param bool $cascadeCallbacks cascade callbacks switch value
     * @return $this
     */
    public function setCascadeCallbacks(bool $cascadeCallbacks)
    {
        $this->_cascadeCallbacks = $cascadeCallbacks;

        return $this;
    }

    /**
     * Gets whether cascaded deletes should also fire callbacks.
     *
     * @return bool
     */
    public function getCascadeCallbacks(): bool
    {
        return $this->_cascadeCallbacks;
    }

    /**
     * Sets the class name of the target table object.
     *
     * @param string $className Class name to set.
     * @return $this
     * @throws \InvalidArgumentException In case the class name is set after the target table has been
     *  resolved, and it doesn't match the target table's class name.
     */
    public function setClassName(string $className)
    {
        if (
            $this->_targetTable !== null &&
            get_class($this->_targetTable) !== App::className($className, 'Model/Table', 'Table')
        ) {
            throw new InvalidArgumentException(sprintf(
                'The class name "%s" doesn\'t match the target table class name of "%s".',
                $className,
                get_class($this->_targetTable)
            ));
        }

        $this->_className = $className;

        return $this;
    }

    /**
     * Gets the class name of the target table object.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->_className;
    }

    /**
     * Sets the table instance for the source side of the association.
     *
     * @param \Cake\ORM\Table $table the instance to be assigned as source side
     * @return $this
     */
    public function setSource(Table $table)
    {
        $this->_sourceTable = $table;

        return $this;
    }

    /**
     * Gets the table instance for the source side of the association.
     *
     * @return \Cake\ORM\Table
     */
    public function getSource(): Table
    {
        return $this->_sourceTable;
    }

    /**
     * Sets the table instance for the target side of the association.
     *
     * @param \Cake\ORM\Table $table the instance to be assigned as target side
     * @return $this
     */
    public function setTarget(Table $table)
    {
        $this->_targetTable = $table;

        return $this;
    }

    /**
     * Gets the table instance for the target side of the association.
     *
     * @return \Cake\ORM\Table
     */
    public function getTarget(): Table
    {
        if ($this->_targetTable === null) {
            if (strpos($this->_className, '.')) {
                [$plugin] = pluginSplit($this->_className, true);
                $registryAlias = (string)$plugin . $this->_name;
            } else {
                $registryAlias = $this->_name;
            }

            $tableLocator = $this->getTableLocator();

            $config = [];
            $exists = $tableLocator->exists($registryAlias);
            if (!$exists) {
                $config = ['className' => $this->_className];
            }
            $this->_targetTable = $tableLocator->get($registryAlias, $config);

            if ($exists) {
                $className = App::className($this->_className, 'Model/Table', 'Table') ?: Table::class;

                if (!$this->_targetTable instanceof $className) {
                    $errorMessage = '%s association "%s" of type "%s" to "%s" doesn\'t match the expected class "%s". ';
                    $errorMessage .= 'You can\'t have an association of the same name with a different target ';
                    $errorMessage .= '"className" option anywhere in your app.';

                    throw new RuntimeException(sprintf(
                        $errorMessage,
                        $this->_sourceTable === null ? 'null' : get_class($this->_sourceTable),
                        $this->getName(),
                        $this->type(),
                        get_class($this->_targetTable),
                        $className
                    ));
                }
            }
        }

        return $this->_targetTable;
    }

    /**
     * Sets a list of conditions to be always included when fetching records from
     * the target association.
     *
     * @param \Closure|array $conditions list of conditions to be used
     * @see \Cake\Database\Query::where() for examples on the format of the array
     * @return $this
     */
    public function setConditions($conditions)
    {
        $this->_conditions = $conditions;

        return $this;
    }

    /**
     * Gets a list of conditions to be always included when fetching records from
     * the target association.
     *
     * @see \Cake\Database\Query::where() for examples on the format of the array
     * @return \Closure|array
     */
    public function getConditions()
    {
        return $this->_conditions;
    }

    /**
     * Sets the name of the field representing the binding field with the target table.
     * When not manually specified the primary key of the owning side table is used.
     *
     * @param array<string>|string $key the table field or fields to be used to link both tables together
     * @return $this
     */
    public function setBindingKey($key)
    {
        $this->_bindingKey = $key;

        return $this;
    }

    /**
     * Gets the name of the field representing the binding field with the target table.
     * When not manually specified the primary key of the owning side table is used.
     *
     * @return array<string>|string
     */
    public function getBindingKey()
    {
        if ($this->_bindingKey === null) {
            $this->_bindingKey = $this->isOwningSide($this->getSource()) ?
                $this->getSource()->getPrimaryKey() :
                $this->getTarget()->getPrimaryKey();
        }

        return $this->_bindingKey;
    }

    /**
     * Gets the name of the field representing the foreign key to the target table.
     *
     * @return array<string>|string
     */
    public function getForeignKey()
    {
        return $this->_foreignKey;
    }

    /**
     * Sets the name of the field representing the foreign key to the target table.
     *
     * @param array<string>|string $key the key or keys to be used to link both tables together
     * @return $this
     */
    public function setForeignKey($key)
    {
        $this->_foreignKey = $key;

        return $this;
    }

    /**
     * Sets whether the records on the target table are dependent on the source table.
     *
     * This is primarily used to indicate that records should be removed if the owning record in
     * the source table is deleted.
     *
     * If no parameters are passed the current setting is returned.
     *
     * @param bool $dependent Set the dependent mode. Use null to read the current state.
     * @return $this
     */
    public function setDependent(bool $dependent)
    {
        $this->_dependent = $dependent;

        return $this;
    }

    /**
     * Sets whether the records on the target table are dependent on the source table.
     *
     * This is primarily used to indicate that records should be removed if the owning record in
     * the source table is deleted.
     *
     * @return bool
     */
    public function getDependent(): bool
    {
        return $this->_dependent;
    }

    /**
     * Whether this association can be expressed directly in a query join
     *
     * @param array<string, mixed> $options custom options key that could alter the return value
     * @return bool
     */
    public function canBeJoined(array $options = []): bool
    {
        $strategy = $options['strategy'] ?? $this->getStrategy();

        return $strategy === $this::STRATEGY_JOIN;
    }

    /**
     * Sets the type of join to be used when adding the association to a query.
     *
     * @param string $type the join type to be used (e.g. INNER)
     * @return $this
     */
    public function setJoinType(string $type)
    {
        $this->_joinType = $type;

        return $this;
    }

    /**
     * Gets the type of join to be used when adding the association to a query.
     *
     * @return string
     */
    public function getJoinType(): string
    {
        return $this->_joinType;
    }

    /**
     * Sets the property name that should be filled with data from the target table
     * in the source table record.
     *
     * @param string $name The name of the association property. Use null to read the current value.
     * @return $this
     */
    public function setProperty(string $name)
    {
        $this->_propertyName = $name;

        return $this;
    }

    /**
     * Gets the property name that should be filled with data from the target table
     * in the source table record.
     *
     * @return string
     */
    public function getProperty(): string
    {
        if (!$this->_propertyName) {
            $this->_propertyName = $this->_propertyName();
            if (in_array($this->_propertyName, $this->_sourceTable->getSchema()->columns(), true)) {
                $msg = 'Association property name "%s" clashes with field of same name of table "%s".' .
                    ' You should explicitly specify the "propertyName" option.';
                trigger_error(
                    sprintf($msg, $this->_propertyName, $this->_sourceTable->getTable()),
                    E_USER_WARNING
                );
            }
        }

        return $this->_propertyName;
    }

    /**
     * Returns default property name based on association name.
     *
     * @return string
     */
    protected function _propertyName(): string
    {
        [, $name] = pluginSplit($this->_name);

        return Inflector::underscore($name);
    }

    /**
     * Sets the strategy name to be used to fetch associated records. Keep in mind
     * that some association types might not implement but a default strategy,
     * rendering any changes to this setting void.
     *
     * @param string $name The strategy type. Use null to read the current value.
     * @return $this
     * @throws \InvalidArgumentException When an invalid strategy is provided.
     */
    public function setStrategy(string $name)
    {
        if (!in_array($name, $this->_validStrategies, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid strategy "%s" was provided. Valid options are (%s).',
                $name,
                implode(', ', $this->_validStrategies)
            ));
        }
        $this->_strategy = $name;

        return $this;
    }

    /**
     * Gets the strategy name to be used to fetch associated records. Keep in mind
     * that some association types might not implement but a default strategy,
     * rendering any changes to this setting void.
     *
     * @return string
     */
    public function getStrategy(): string
    {
        return $this->_strategy;
    }

    /**
     * Gets the default finder to use for fetching rows from the target table.
     *
     * @return array|string
     */
    public function getFinder()
    {
        return $this->_finder;
    }

    /**
     * Sets the default finder to use for fetching rows from the target table.
     *
     * @param array|string $finder the finder name to use or array of finder name and option.
     * @return $this
     */
    public function setFinder($finder)
    {
        $this->_finder = $finder;

        return $this;
    }

    /**
     * Override this function to initialize any concrete association class, it will
     * get passed the original list of options used in the constructor
     *
     * @param array<string, mixed> $options List of options used for initialization
     * @return void
     */
    protected function _options(array $options): void
    {
    }

    /**
     * Alters a Query object to include the associated target table data in the final
     * result
     *
     * The options array accept the following keys:
     *
     * - includeFields: Whether to include target model fields in the result or not
     * - foreignKey: The name of the field to use as foreign key, if false none
     *   will be used
     * - conditions: array with a list of conditions to filter the join with, this
     *   will be merged with any conditions originally configured for this association
     * - fields: a list of fields in the target table to include in the result
     * - aliasPath: A dot separated string representing the path of association names
     *   followed from the passed query main table to this association.
     * - propertyPath: A dot separated string representing the path of association
     *   properties to be followed from the passed query main entity to this
     *   association
     * - joinType: The SQL join type to use in the query.
     * - negateMatch: Will append a condition to the passed query for excluding matches.
     *   with this association.
     *
     * @param \Cake\ORM\Query $query the query to be altered to include the target table data
     * @param array<string, mixed> $options Any extra options or overrides to be taken in account
     * @return void
     * @throws \RuntimeException Unable to build the query or associations.
     */
    public function attachTo(Query $query, array $options = []): void
    {
        $target = $this->getTarget();
        $table = $target->getTable();

        $options += [
            'includeFields' => true,
            'foreignKey' => $this->getForeignKey(),
            'conditions' => [],
            'joinType' => $this->getJoinType(),
            'fields' => [],
            'table' => $table,
            'finder' => $this->getFinder(),
        ];

        // This is set by joinWith to disable matching results
        if ($options['fields'] === false) {
            $options['fields'] = [];
            $options['includeFields'] = false;
        }

        if (!empty($options['foreignKey'])) {
            $joinCondition = $this->_joinCondition($options);
            if ($joinCondition) {
                $options['conditions'][] = $joinCondition;
            }
        }

        [$finder, $opts] = $this->_extractFinder($options['finder']);
        $dummy = $this
            ->find($finder, $opts)
            ->eagerLoaded(true);

        if (!empty($options['queryBuilder'])) {
            $dummy = $options['queryBuilder']($dummy);
            if (!($dummy instanceof Query)) {
                throw new RuntimeException(sprintf(
                    'Query builder for association "%s" did not return a query',
                    $this->getName()
                ));
            }
        }

        if (
            !empty($options['matching']) &&
            $this->_strategy === static::STRATEGY_JOIN &&
            $dummy->getContain()
        ) {
            throw new RuntimeException(
                "`{$this->getName()}` association cannot contain() associations when using JOIN strategy."
            );
        }

        $dummy->where($options['conditions']);
        $this->_dispatchBeforeFind($dummy);

        $query->join([$this->_name => [
            'table' => $options['table'],
            'conditions' => $dummy->clause('where'),
            'type' => $options['joinType'],
        ]]);

        $this->_appendFields($query, $dummy, $options);
        $this->_formatAssociationResults($query, $dummy, $options);
        $this->_bindNewAssociations($query, $dummy, $options);
        $this->_appendNotMatching($query, $options);
    }

    /**
     * Conditionally adds a condition to the passed Query that will make it find
     * records where there is no match with this association.
     *
     * @param \Cake\ORM\Query $query The query to modify
     * @param array<string, mixed> $options Options array containing the `negateMatch` key.
     * @return void
     */
    protected function _appendNotMatching(Query $query, array $options): void
    {
        $target = $this->_targetTable;
        if (!empty($options['negateMatch'])) {
            $primaryKey = $query->aliasFields((array)$target->getPrimaryKey(), $this->_name);
            $query->andWhere(function ($exp) use ($primaryKey) {
                array_map([$exp, 'isNull'], $primaryKey);

                return $exp;
            });
        }
    }

    /**
     * Correctly nests a result row associated values into the correct array keys inside the
     * source results.
     *
     * @param array $row The row to transform
     * @param string $nestKey The array key under which the results for this association
     *   should be found
     * @param bool $joined Whether the row is a result of a direct join
     *   with this association
     * @param string|null $targetProperty The property name in the source results where the association
     * data shuld be nested in. Will use the default one if not provided.
     * @return array
     */
    public function transformRow(array $row, string $nestKey, bool $joined, ?string $targetProperty = null): array
    {
        $sourceAlias = $this->getSource()->getAlias();
        $nestKey = $nestKey ?: $this->_name;
        $targetProperty = $targetProperty ?: $this->getProperty();
        if (isset($row[$sourceAlias])) {
            $row[$sourceAlias][$targetProperty] = $row[$nestKey];
            unset($row[$nestKey]);
        }

        return $row;
    }

    /**
     * Returns a modified row after appending a property for this association
     * with the default empty value according to whether the association was
     * joined or fetched externally.
     *
     * @param array<string, mixed> $row The row to set a default on.
     * @param bool $joined Whether the row is a result of a direct join
     *   with this association
     * @return array<string, mixed>
     */
    public function defaultRowValue(array $row, bool $joined): array
    {
        $sourceAlias = $this->getSource()->getAlias();
        if (isset($row[$sourceAlias])) {
            $row[$sourceAlias][$this->getProperty()] = null;
        }

        return $row;
    }

    /**
     * Proxies the finding operation to the target table's find method
     * and modifies the query accordingly based of this association
     * configuration
     *
     * @param array<string, mixed>|string|null $type the type of query to perform, if an array is passed,
     *   it will be interpreted as the `$options` parameter
     * @param array<string, mixed> $options The options to for the find
     * @see \Cake\ORM\Table::find()
     * @return \Cake\ORM\Query
     */
    public function find($type = null, array $options = []): Query
    {
        $type = $type ?: $this->getFinder();
        [$type, $opts] = $this->_extractFinder($type);

        return $this->getTarget()
            ->find($type, $options + $opts)
            ->where($this->getConditions());
    }

    /**
     * Proxies the operation to the target table's exists method after
     * appending the default conditions for this association
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|null $conditions The conditions to use
     * for checking if any record matches.
     * @see \Cake\ORM\Table::exists()
     * @return bool
     */
    public function exists($conditions): bool
    {
        $conditions = $this->find()
            ->where($conditions)
            ->clause('where');

        return $this->getTarget()->exists($conditions);
    }

    /**
     * Proxies the update operation to the target table's updateAll method
     *
     * @param array $fields A hash of field => new value.
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|null $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @see \Cake\ORM\Table::updateAll()
     * @return int Count Returns the affected rows.
     */
    public function updateAll(array $fields, $conditions): int
    {
        $expression = $this->find()
            ->where($conditions)
            ->clause('where');

        return $this->getTarget()->updateAll($fields, $expression);
    }

    /**
     * Proxies the delete operation to the target table's deleteAll method
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string|null $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @return int Returns the number of affected rows.
     * @see \Cake\ORM\Table::deleteAll()
     */
    public function deleteAll($conditions): int
    {
        $expression = $this->find()
            ->where($conditions)
            ->clause('where');

        return $this->getTarget()->deleteAll($expression);
    }

    /**
     * Returns true if the eager loading process will require a set of the owning table's
     * binding keys in order to use them as a filter in the finder query.
     *
     * @param array<string, mixed> $options The options containing the strategy to be used.
     * @return bool true if a list of keys will be required
     */
    public function requiresKeys(array $options = []): bool
    {
        $strategy = $options['strategy'] ?? $this->getStrategy();

        return $strategy === static::STRATEGY_SELECT;
    }

    /**
     * Triggers beforeFind on the target table for the query this association is
     * attaching to
     *
     * @param \Cake\ORM\Query $query the query this association is attaching itself to
     * @return void
     */
    protected function _dispatchBeforeFind(Query $query): void
    {
        $query->triggerBeforeFind();
    }

    /**
     * Helper function used to conditionally append fields to the select clause of
     * a query from the fields found in another query object.
     *
     * @param \Cake\ORM\Query $query the query that will get the fields appended to
     * @param \Cake\ORM\Query $surrogate the query having the fields to be copied from
     * @param array<string, mixed> $options options passed to the method `attachTo`
     * @return void
     */
    protected function _appendFields(Query $query, Query $surrogate, array $options): void
    {
        if ($query->getEagerLoader()->isAutoFieldsEnabled() === false) {
            return;
        }

        $fields = array_merge($surrogate->clause('select'), $options['fields']);

        if (
            (empty($fields) && $options['includeFields']) ||
            $surrogate->isAutoFieldsEnabled()
        ) {
            $fields = array_merge($fields, $this->_targetTable->getSchema()->columns());
        }

        $query->select($query->aliasFields($fields, $this->_name));
        $query->addDefaultTypes($this->_targetTable);
    }

    /**
     * Adds a formatter function to the passed `$query` if the `$surrogate` query
     * declares any other formatter. Since the `$surrogate` query correspond to
     * the associated target table, the resulting formatter will be the result of
     * applying the surrogate formatters to only the property corresponding to
     * such table.
     *
     * @param \Cake\ORM\Query $query the query that will get the formatter applied to
     * @param \Cake\ORM\Query $surrogate the query having formatters for the associated
     * target table.
     * @param array<string, mixed> $options options passed to the method `attachTo`
     * @return void
     */
    protected function _formatAssociationResults(Query $query, Query $surrogate, array $options): void
    {
        $formatters = $surrogate->getResultFormatters();

        if (!$formatters || empty($options['propertyPath'])) {
            return;
        }

        $property = $options['propertyPath'];
        $propertyPath = explode('.', $property);
        $query->formatResults(
            function (CollectionInterface $results, $query) use ($formatters, $property, $propertyPath) {
                $extracted = [];
                foreach ($results as $result) {
                    foreach ($propertyPath as $propertyPathItem) {
                        if (!isset($result[$propertyPathItem])) {
                            $result = null;
                            break;
                        }
                        $result = $result[$propertyPathItem];
                    }
                    $extracted[] = $result;
                }
                $extracted = new Collection($extracted);
                foreach ($formatters as $callable) {
                    $extracted = $callable($extracted, $query);
                    if (!$extracted instanceof ResultSetInterface) {
                        $extracted = new ResultSetDecorator($extracted);
                    }
                }

                $results = $results->insert($property, $extracted);
                if ($query->isHydrationEnabled()) {
                    $results = $results->map(function ($result) {
                        $result->clean();

                        return $result;
                    });
                }

                return $results;
            },
            Query::PREPEND
        );
    }

    /**
     * Applies all attachable associations to `$query` out of the containments found
     * in the `$surrogate` query.
     *
     * Copies all contained associations from the `$surrogate` query into the
     * passed `$query`. Containments are altered so that they respect the associations
     * chain from which they originated.
     *
     * @param \Cake\ORM\Query $query the query that will get the associations attached to
     * @param \Cake\ORM\Query $surrogate the query having the containments to be attached
     * @param array<string, mixed> $options options passed to the method `attachTo`
     * @return void
     */
    protected function _bindNewAssociations(Query $query, Query $surrogate, array $options): void
    {
        $loader = $surrogate->getEagerLoader();
        $contain = $loader->getContain();
        $matching = $loader->getMatching();

        if (!$contain && !$matching) {
            return;
        }

        $newContain = [];
        foreach ($contain as $alias => $value) {
            $newContain[$options['aliasPath'] . '.' . $alias] = $value;
        }

        $eagerLoader = $query->getEagerLoader();
        if ($newContain) {
            $eagerLoader->contain($newContain);
        }

        foreach ($matching as $alias => $value) {
            $eagerLoader->setMatching(
                $options['aliasPath'] . '.' . $alias,
                $value['queryBuilder'],
                $value
            );
        }
    }

    /**
     * Returns a single or multiple conditions to be appended to the generated join
     * clause for getting the results on the target table.
     *
     * @param array<string, mixed> $options list of options passed to attachTo method
     * @return array
     * @throws \RuntimeException if the number of columns in the foreignKey do not
     * match the number of columns in the source table primaryKey
     */
    protected function _joinCondition(array $options): array
    {
        $conditions = [];
        $tAlias = $this->_name;
        $sAlias = $this->getSource()->getAlias();
        $foreignKey = (array)$options['foreignKey'];
        $bindingKey = (array)$this->getBindingKey();

        if (count($foreignKey) !== count($bindingKey)) {
            if (empty($bindingKey)) {
                $table = $this->getTarget()->getTable();
                if ($this->isOwningSide($this->getSource())) {
                    $table = $this->getSource()->getTable();
                }
                $msg = 'The "%s" table does not define a primary key, and cannot have join conditions generated.';
                throw new RuntimeException(sprintf($msg, $table));
            }

            $msg = 'Cannot match provided foreignKey for "%s", got "(%s)" but expected foreign key for "(%s)"';
            throw new RuntimeException(sprintf(
                $msg,
                $this->_name,
                implode(', ', $foreignKey),
                implode(', ', $bindingKey)
            ));
        }

        foreach ($foreignKey as $k => $f) {
            $field = sprintf('%s.%s', $sAlias, $bindingKey[$k]);
            $value = new IdentifierExpression(sprintf('%s.%s', $tAlias, $f));
            $conditions[$field] = $value;
        }

        return $conditions;
    }

    /**
     * Helper method to infer the requested finder and its options.
     *
     * Returns the inferred options from the finder $type.
     *
     * ### Examples:
     *
     * The following will call the finder 'translations' with the value of the finder as its options:
     * $query->contain(['Comments' => ['finder' => ['translations']]]);
     * $query->contain(['Comments' => ['finder' => ['translations' => []]]]);
     * $query->contain(['Comments' => ['finder' => ['translations' => ['locales' => ['en_US']]]]]);
     *
     * @param array|string $finderData The finder name or an array having the name as key
     * and options as value.
     * @return array
     */
    protected function _extractFinder($finderData): array
    {
        $finderData = (array)$finderData;

        if (is_numeric(key($finderData))) {
            return [current($finderData), []];
        }

        return [key($finderData), current($finderData)];
    }

    /**
     * Proxies property retrieval to the target table. This is handy for getting this
     * association's associations
     *
     * @param string $property the property name
     * @return \Cake\ORM\Association
     * @throws \RuntimeException if no association with such name exists
     */
    public function __get($property)
    {
        return $this->getTarget()->{$property};
    }

    /**
     * Proxies the isset call to the target table. This is handy to check if the
     * target table has another association with the passed name
     *
     * @param string $property the property name
     * @return bool true if the property exists
     */
    public function __isset($property)
    {
        return isset($this->getTarget()->{$property});
    }

    /**
     * Proxies method calls to the target table.
     *
     * @param string $method name of the method to be invoked
     * @param array $argument List of arguments passed to the function
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $argument)
    {
        return $this->getTarget()->$method(...$argument);
    }

    /**
     * Get the relationship type.
     *
     * @return string Constant of either ONE_TO_ONE, MANY_TO_ONE, ONE_TO_MANY or MANY_TO_MANY.
     */
    abstract public function type(): string;

    /**
     * Eager loads a list of records in the target table that are related to another
     * set of records in the source table. Source records can be specified in two ways:
     * first one is by passing a Query object setup to find on the source table and
     * the other way is by explicitly passing an array of primary key values from
     * the source table.
     *
     * The required way of passing related source records is controlled by "strategy"
     * When the subquery strategy is used it will require a query on the source table.
     * When using the select strategy, the list of primary keys will be used.
     *
     * Returns a closure that should be run for each record returned in a specific
     * Query. This callable will be responsible for injecting the fields that are
     * related to each specific passed row.
     *
     * Options array accepts the following keys:
     *
     * - query: Query object setup to find the source table records
     * - keys: List of primary key values from the source table
     * - foreignKey: The name of the field used to relate both tables
     * - conditions: List of conditions to be passed to the query where() method
     * - sort: The direction in which the records should be returned
     * - fields: List of fields to select from the target table
     * - contain: List of related tables to eager load associated to the target table
     * - strategy: The name of strategy to use for finding target table records
     * - nestKey: The array key under which results will be found when transforming the row
     *
     * @param array<string, mixed> $options The options for eager loading.
     * @return \Closure
     */
    abstract public function eagerLoader(array $options): Closure;

    /**
     * Handles cascading a delete from an associated model.
     *
     * Each implementing class should handle the cascaded delete as
     * required.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascaded delete.
     * @param array<string, mixed> $options The options for the original delete.
     * @return bool Success
     */
    abstract public function cascadeDelete(EntityInterface $entity, array $options = []): bool;

    /**
     * Returns whether the passed table is the owning side for this
     * association. This means that rows in the 'target' table would miss important
     * or required information if the row in 'source' did not exist.
     *
     * @param \Cake\ORM\Table $side The potential Table with ownership
     * @return bool
     */
    abstract public function isOwningSide(Table $side): bool;

    /**
     * Extract the target's association data our from the passed entity and proxies
     * the saving operation to the target table.
     *
     * @param \Cake\Datasource\EntityInterface $entity the data to be saved
     * @param array<string, mixed> $options The options for saving associated data.
     * @return \Cake\Datasource\EntityInterface|false false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\ORM\Table::save()
     */
    abstract public function saveAssociated(EntityInterface $entity, array $options = []);
}
