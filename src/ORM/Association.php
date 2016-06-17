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

use Cake\Collection\Collection;
use Cake\Core\ConventionsTrait;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\AssociationInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetDecorator;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use RuntimeException;

/**
 * An Association is a relationship established between two tables and is used
 * to configure and customize the way interconnected records are retrieved.
 *
 * @mixin \Cake\ORM\Table
 */
abstract class Association implements AssociationInterface
{

    use ConventionsTrait;
    use LocatorAwareTrait;

    /**
     * Strategy name to use joins for fetching associated records
     *
     * @var string
     */
    const STRATEGY_JOIN = 'join';

    /**
     * Strategy name to use a subquery for fetching associated records
     *
     * @var string
     */
    const STRATEGY_SUBQUERY = 'subquery';

    /**
     * Strategy name to use a select for fetching associated records
     *
     * @var string
     */
    const STRATEGY_SELECT = 'select';

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
     * @var string|array
     */
    protected $_bindingKey;

    /**
     * The name of the field representing the foreign key to the table to load
     *
     * @var string|array
     */
    protected $_foreignKey;

    /**
     * A list of conditions to be always included when fetching records from
     * the target association
     *
     * @var array
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
     * Whether or not cascaded deletes should also fire callbacks.
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
    protected $_joinType = 'LEFT';

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
     *
     * @var string
     */
    protected $_finder = 'all';

    /**
     * Valid strategies for this association. Subclasses can narrow this down.
     *
     * @var array
     */
    protected $_validStrategies = [
        self::STRATEGY_JOIN,
        self::STRATEGY_SELECT,
        self::STRATEGY_SUBQUERY
    ];

    /**
     * Constructor. Subclasses can override _options function to get the original
     * list of passed options if expecting any other special key
     *
     * @param string $alias The name given to the association
     * @param array $options A list of properties to be set on this object
     */
    public function __construct($alias, array $options = [])
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
            'targetTable'
        ];
        foreach ($defaults as $property) {
            if (isset($options[$property])) {
                $this->{'_' . $property} = $options[$property];
            }
        }

        if (empty($this->_className) && strpos($alias, '.')) {
            $this->_className = $alias;
        }

        list(, $name) = pluginSplit($alias);
        $this->_name = $name;

        $this->_options($options);

        if (!empty($options['strategy'])) {
            $this->setStrategy($options['strategy']);
        }
    }

    /**
     * Sets the name for this association.
     *
     * @param string $name Name to be assigned
     * @return self
     */
    public function setName($name)
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Gets the name for this association.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the name for this association.
     *
     * @deprecated 3.4.0 Use setName()/getName() instead.
     * @param string|null $name Name to be assigned
     * @return string
     */
    public function name($name = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }

        return $this->getName();
    }

    /**
     * Sets whether or not cascaded deletes should also fire callbacks.
     *
     * @param bool $cascadeCallbacks cascade callbacks switch value
     * @return self
     */
    public function setCascadeCallbacks($cascadeCallbacks)
    {
        $this->_cascadeCallbacks = $cascadeCallbacks;

        return $this;
    }

    /**
     * Gets whether or not cascaded deletes should also fire callbacks.
     *
     * @return bool
     */
    public function getCascadeCallbacks()
    {
        return $this->_cascadeCallbacks;
    }

    /**
     * Sets whether or not cascaded deletes should also fire callbacks. If no
     * arguments are passed, the current configured value is returned
     *
     * @deprecated 3.4.0 Use setCascadeCallbacks()/getCascadeCallbacks() instead.
     * @param bool|null $cascadeCallbacks cascade callbacks switch value
     * @return bool
     */
    public function cascadeCallbacks($cascadeCallbacks = null)
    {
        if ($cascadeCallbacks !== null) {
            $this->setCascadeCallbacks($cascadeCallbacks);
        }

        return $this->getCascadeCallbacks();
    }

    /**
     * The class name of the target table object
     *
     * @return string
     */
    public function className()
    {
        return $this->_className;
    }

    /**
     * Sets the table instance for the source side of the association.
     *
     * @param \Cake\ORM\Table $table the instance to be assigned as source side
     * @return self
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
    public function getSource()
    {
        return $this->_sourceTable;
    }

    /**
     * Sets the table instance for the source side of the association. If no arguments
     * are passed, the current configured table instance is returned
     *
     * @deprecated 3.4.0 Use setSource()/getSource() instead.
     * @param \Cake\ORM\Table|null $table the instance to be assigned as source side
     * @return \Cake\ORM\Table
     */
    public function source(RepositoryInterface $table = null)
    {
        if ($table === null) {
            return $this->_sourceTable;
        }

        return $this->_sourceTable = $table;
    }

    /**
     * Sets the table instance for the target side of the association.
     *
     * @param \Cake\ORM\Table $table the instance to be assigned as target side
     * @return self
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
    public function getTarget()
    {
        if (!$this->_targetTable) {
            if (strpos($this->_className, '.')) {
                list($plugin) = pluginSplit($this->_className, true);
                $registryAlias = $plugin . $this->_name;
            } else {
                $registryAlias = $this->_name;
            }

            $tableLocator = $this->tableLocator();

            $config = [];
            if (!$tableLocator->exists($registryAlias)) {
                $config = ['className' => $this->_className];
            }
            $this->_targetTable = $tableLocator->get($registryAlias, $config);
        }

        return $this->_targetTable;
    }

    /**
     * Sets the table instance for the target side of the association. If no arguments
     * are passed, the current configured table instance is returned
     *
     * @deprecated 3.4.0 Use setTable()/getTable() instead.
     * @param \Cake\ORM\Table|null $table the instance to be assigned as target side
     * @return \Cake\ORM\Table
     */
    public function target(RepositoryInterface $table = null)
    {
        if ($table !== null) {
            $this->setTarget($table);
        }

        return $this->getTarget();
    }

    /**
     * Sets a list of conditions to be always included when fetching records from
     * the target association.
     *
     * @param array $conditions list of conditions to be used
     * @see \Cake\Database\Query::where() for examples on the format of the array
     * @return self
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
     * @return array
     */
    public function getConditions()
    {
        return $this->_conditions;
    }

    /**
     * Sets a list of conditions to be always included when fetching records from
     * the target association. If no parameters are passed the current list is returned
     *
     * @deprecated 3.4.0 Use setConditions()/getConditions() instead.
     * @param array|null $conditions list of conditions to be used
     * @see \Cake\Database\Query::where() for examples on the format of the array
     * @return array
     */
    public function conditions($conditions = null)
    {
        if ($conditions !== null) {
            $this->setConditions($conditions);
        }

        return $this->getConditions();
    }

    /**
     * Sets the name of the field representing the binding field with the target table.
     * When not manually specified the primary key of the owning side table is used.
     *
     * @param string $key the table field to be used to link both tables together
     * @return self
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
     * @return string|array
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
     * Sets the name of the field representing the binding field with the target table.
     * When not manually specified the primary key of the owning side table is used.
     *
     * If no parameters are passed the current field is returned
     *
     * @deprecated 3.4.0 Use setBindingKey()/getBindingKey() instead.
     * @param string|null $key the table field to be used to link both tables together
     * @return string|array
     */
    public function bindingKey($key = null)
    {
        if ($key !== null) {
            $this->setBindingKey($key);
        }

        return $this->getBindingKey();
    }

    /**
     * Gets the name of the field representing the foreign key to the target table.
     *
     * @return string|array
     */
    public function getForeignKey()
    {
        return $this->_foreignKey;
    }

    /**
     * Sets the name of the field representing the foreign key to the target table.
     *
     * @param string $key the key to be used to link both tables together
     * @return self
     */
    public function setForeignKey($key)
    {
        $this->_foreignKey = $key;

        return $this;
    }

    /**
     * Sets the name of the field representing the foreign key to the target table.
     * If no parameters are passed the current field is returned
     *
     * @deprecated 3.4.0 Use setForeignKey()/getForeignKey() instead.
     * @param string|null $key the key to be used to link both tables together
     * @return string|array
     */
    public function foreignKey($key = null)
    {
        if ($key !== null) {
            $this->setForeignKey($key);
        }

        return $this->getForeignKey();
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
     * @return self
     */
    public function setDependent($dependent)
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
    public function getDependent()
    {
        return $this->_dependent;
    }

    /**
     * Sets whether the records on the target table are dependent on the source table.
     *
     * This is primarily used to indicate that records should be removed if the owning record in
     * the source table is deleted.
     *
     * If no parameters are passed the current setting is returned.
     *
     * @deprecated 3.4.0 Use setDependent()/getDependent() instead.
     * @param bool|null $dependent Set the dependent mode. Use null to read the current state.
     * @return bool
     */
    public function dependent($dependent = null)
    {
        if ($dependent !== null) {
            $this->setDependent($dependent);
        }

        return $this->getDependent();
    }

    /**
     * Whether this association can be expressed directly in a query join
     *
     * @param array $options custom options key that could alter the return value
     * @return bool
     */
    public function canBeJoined(array $options = [])
    {
        $strategy = isset($options['strategy']) ? $options['strategy'] : $this->getStrategy();

        return $strategy == $this::STRATEGY_JOIN;
    }

    /**
     * Sets the type of join to be used when adding the association to a query.
     *
     * @param string $type the join type to be used (e.g. INNER)
     * @return self
     */
    public function setJoinType($type)
    {
        $this->_joinType = $type;

        return $this;
    }

    /**
     * Gets the type of join to be used when adding the association to a query.
     *
     * @return string
     */
    public function getJoinType()
    {
        return $this->_joinType;
    }

    /**
     * Sets the type of join to be used when adding the association to a query.
     * If no arguments are passed, the currently configured type is returned.
     *
     * @deprecated 3.4.0 Use setJoinType()/getJoinType() instead.
     * @param string|null $type the join type to be used (e.g. INNER)
     * @return string
     */
    public function joinType($type = null)
    {
        if ($type !== null) {
            $this->setJoinType($type);
        }

        return $this->getJoinType();
    }

    /**
     * Sets the property name that should be filled with data from the target table
     * in the source table record.
     *
     * @param string $name The name of the association property. Use null to read the current value.
     * @return self
     */
    public function setProperty($name)
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
    public function getProperty()
    {
        if (!$this->_propertyName) {
            $this->_propertyName = $this->_propertyName();
            if (in_array($this->_propertyName, $this->_sourceTable->getSchema()->columns())) {
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
     * Sets the property name that should be filled with data from the target table
     * in the source table record.
     * If no arguments are passed, the currently configured type is returned.
     *
     * @deprecated 3.4.0 Use setProperty()/getProperty() instead.
     * @param string|null $name The name of the association property. Use null to read the current value.
     * @return string
     */
    public function property($name = null)
    {
        if ($name !== null) {
            $this->setProperty($name);
        }

        return $this->getProperty();
    }

    /**
     * Returns default property name based on association name.
     *
     * @return string
     */
    protected function _propertyName()
    {
        list(, $name) = pluginSplit($this->_name);

        return Inflector::underscore($name);
    }

    /**
     * Sets the strategy name to be used to fetch associated records. Keep in mind
     * that some association types might not implement but a default strategy,
     * rendering any changes to this setting void.
     *
     * @param string $name The strategy type. Use null to read the current value.
     * @return self
     * @throws \InvalidArgumentException When an invalid strategy is provided.
     */
    public function setStrategy($name)
    {
        if (!in_array($name, $this->_validStrategies)) {
            throw new InvalidArgumentException(
                sprintf('Invalid strategy "%s" was provided', $name)
            );
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
    public function getStrategy()
    {
        return $this->_strategy;
    }

    /**
     * Sets the strategy name to be used to fetch associated records. Keep in mind
     * that some association types might not implement but a default strategy,
     * rendering any changes to this setting void.
     * If no arguments are passed, the currently configured strategy is returned.
     *
     * @deprecated 3.4.0 Use setStrategy()/getStrategy() instead.
     * @param string|null $name The strategy type. Use null to read the current value.
     * @return string
     * @throws \InvalidArgumentException When an invalid strategy is provided.
     */
    public function strategy($name = null)
    {
        if ($name !== null) {
            $this->setStrategy($name);
        }

        return $this->getStrategy();
    }

    /**
     * Gets the default finder to use for fetching rows from the target table.
     *
     * @return string
     */
    public function getFinder()
    {
        return $this->_finder;
    }

    /**
     * Sets the default finder to use for fetching rows from the target table.
     *
     * @param string $finder the finder name to use
     * @return self
     */
    public function setFinder($finder)
    {
        $this->_finder = $finder;

        return $this;
    }

    /**
     * Sets the default finder to use for fetching rows from the target table.
     * If no parameters are passed, it will return the currently configured
     * finder name.
     *
     * @deprecated 3.4.0 Use setFinder()/getFinder() instead.
     * @param string|null $finder the finder name to use
     * @return string
     */
    public function finder($finder = null)
    {
        if ($finder !== null) {
            $this->setFinder($finder);
        }

        return $this->getFinder();
    }

    /**
     * Override this function to initialize any concrete association class, it will
     * get passed the original list of options used in the constructor
     *
     * @param array $options List of options used for initialization
     * @return void
     */
    protected function _options(array $options)
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
     * - type: The type of join to be used (e.g. INNER)
     *   the records found on this association
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
     * @param array $options Any extra options or overrides to be taken in account
     * @return void
     * @throws \RuntimeException if the query builder passed does not return a query
     * object
     */
    public function attachTo(Query $query, array $options = [])
    {
        $target = $this->getTarget();
        $joinType = empty($options['joinType']) ? $this->getJoinType() : $options['joinType'];
        $table = $target->getTable();

        $options += [
            'includeFields' => true,
            'foreignKey' => $this->getForeignKey(),
            'conditions' => [],
            'fields' => [],
            'type' => $joinType,
            'table' => $table,
            'finder' => $this->getFinder()
        ];

        if (!empty($options['foreignKey'])) {
            $joinCondition = $this->_joinCondition($options);
            if ($joinCondition) {
                $options['conditions'][] = $joinCondition;
            }
        }

        list($finder, $opts) = $this->_extractFinder($options['finder']);
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

        $dummy->where($options['conditions']);
        $this->_dispatchBeforeFind($dummy);

        $joinOptions = ['table' => 1, 'conditions' => 1, 'type' => 1];
        $options['conditions'] = $dummy->clause('where');
        $query->join([$this->_name => array_intersect_key($options, $joinOptions)]);

        $this->_appendFields($query, $dummy, $options);
        $this->_formatAssociationResults($query, $dummy, $options);
        $this->_bindNewAssociations($query, $dummy, $options);
        $this->_appendNotMatching($query, $options);
    }

    /**
     * Conditionally adds a condition to the passed Query that will make it find
     * records where there is no match with this association.
     *
     * @param \Cake\Datasource\QueryInterface $query The query to modify
     * @param array $options Options array containing the `negateMatch` key.
     * @return void
     */
    protected function _appendNotMatching($query, $options)
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
     * @param bool $joined Whether or not the row is a result of a direct join
     *   with this association
     * @param string|null $targetProperty The property name in the source results where the association
     * data shuld be nested in. Will use the default one if not provided.
     * @return array
     */
    public function transformRow($row, $nestKey, $joined, $targetProperty = null)
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
     * @param array $row The row to set a default on.
     * @param bool $joined Whether or not the row is a result of a direct join
     *   with this association
     * @return array
     */
    public function defaultRowValue($row, $joined)
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
     * @param string|array|null $type the type of query to perform, if an array is passed,
     *   it will be interpreted as the `$options` parameter
     * @param array $options The options to for the find
     * @see \Cake\ORM\Table::find()
     * @return \Cake\ORM\Query
     */
    public function find($type = null, array $options = [])
    {
        $type = $type ?: $this->getFinder();
        list($type, $opts) = $this->_extractFinder($type);

        return $this->getTarget()
            ->find($type, $options + $opts)
            ->where($this->getConditions());
    }

    /**
     * Proxies the operation to the target table's exists method after
     * appending the default conditions for this association
     *
     * @param array|callable|\Cake\Database\ExpressionInterface $conditions The conditions to use
     * for checking if any record matches.
     * @see \Cake\ORM\Table::exists()
     * @return bool
     */
    public function exists($conditions)
    {
        if ($this->_conditions) {
            $conditions = $this
                ->find('all', ['conditions' => $conditions])
                ->clause('where');
        }

        return $this->getTarget()->exists($conditions);
    }

    /**
     * Proxies the update operation to the target table's updateAll method
     *
     * @param array $fields A hash of field => new value.
     * @param mixed $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @see \Cake\ORM\Table::updateAll()
     * @return bool Success Returns true if one or more rows are affected.
     */
    public function updateAll($fields, $conditions)
    {
        $target = $this->getTarget();
        $expression = $target->query()
            ->where($this->getConditions())
            ->where($conditions)
            ->clause('where');

        return $target->updateAll($fields, $expression);
    }

    /**
     * Proxies the delete operation to the target table's deleteAll method
     *
     * @param mixed $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @return int Returns the number of affected rows.
     * @see \Cake\ORM\Table::deleteAll()
     */
    public function deleteAll($conditions)
    {
        $target = $this->getTarget();
        $expression = $target->query()
            ->where($this->getConditions())
            ->where($conditions)
            ->clause('where');

        return $target->deleteAll($expression);
    }

    /**
     * Returns true if the eager loading process will require a set of the owning table's
     * binding keys in order to use them as a filter in the finder query.
     *
     * @param array $options The options containing the strategy to be used.
     * @return bool true if a list of keys will be required
     */
    public function requiresKeys(array $options = [])
    {
        $strategy = isset($options['strategy']) ? $options['strategy'] : $this->getStrategy();

        return $strategy === static::STRATEGY_SELECT;
    }

    /**
     * Triggers beforeFind on the target table for the query this association is
     * attaching to
     *
     * @param \Cake\ORM\Query $query the query this association is attaching itself to
     * @return void
     */
    protected function _dispatchBeforeFind($query)
    {
        $query->triggerBeforeFind();
    }

    /**
     * Helper function used to conditionally append fields to the select clause of
     * a query from the fields found in another query object.
     *
     * @param \Cake\ORM\Query $query the query that will get the fields appended to
     * @param \Cake\ORM\Query $surrogate the query having the fields to be copied from
     * @param array $options options passed to the method `attachTo`
     * @return void
     */
    protected function _appendFields($query, $surrogate, $options)
    {
        if ($query->getEagerLoader()->isAutoFieldsEnabled() === false) {
            return;
        }

        $fields = $surrogate->clause('select') ?: $options['fields'];
        $target = $this->_targetTable;
        $autoFields = $surrogate->isAutoFieldsEnabled();

        if (empty($fields) && !$autoFields) {
            if ($options['includeFields'] && ($fields === null || $fields !== false)) {
                $fields = $target->getSchema()->columns();
            }
        }

        if ($autoFields === true) {
            $fields = array_merge((array)$fields, $target->getSchema()->columns());
        }

        if ($fields) {
            $query->select($query->aliasFields($fields, $target->getAlias()));
        }
        $query->addDefaultTypes($target);
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
     * @param array $options options passed to the method `attachTo`
     * @return void
     */
    protected function _formatAssociationResults($query, $surrogate, $options)
    {
        $formatters = $surrogate->formatResults();

        if (!$formatters || empty($options['propertyPath'])) {
            return;
        }

        $property = $options['propertyPath'];
        $propertyPath = explode('.', $property);
        $query->formatResults(function ($results) use ($formatters, $property, $propertyPath) {
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
                $extracted = new ResultSetDecorator($callable($extracted));
            }

            return $results->insert($property, $extracted);
        }, Query::PREPEND);
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
     * @param array $options options passed to the method `attachTo`
     * @return void
     */
    protected function _bindNewAssociations($query, $surrogate, $options)
    {
        $loader = $surrogate->getEagerLoader();
        $contain = $loader->contain();
        $matching = $loader->getMatching();

        if (!$contain && !$matching) {
            return;
        }

        $newContain = [];
        foreach ($contain as $alias => $value) {
            $newContain[$options['aliasPath'] . '.' . $alias] = $value;
        }

        $eagerLoader = $query->getEagerLoader();
        $eagerLoader->contain($newContain);

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
     * @param array $options list of options passed to attachTo method
     * @return array
     * @throws \RuntimeException if the number of columns in the foreignKey do not
     * match the number of columns in the source table primaryKey
     */
    protected function _joinCondition($options)
    {
        $conditions = [];
        $tAlias = $this->getTarget()->getAlias();
        $sAlias = $this->getSource()->getAlias();
        $foreignKey = (array)$options['foreignKey'];
        $bindingKey = (array)$this->getBindingKey();

        if (count($foreignKey) !== count($bindingKey)) {
            if (empty($bindingKey)) {
                $msg = 'The "%s" table does not define a primary key. Please set one.';
                throw new RuntimeException(sprintf($msg, $this->getTarget()->getTable()));
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
     * @param string|array $finderData The finder name or an array having the name as key
     * and options as value.
     * @return array
     */
    protected function _extractFinder($finderData)
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
    abstract public function type();

    /**
     * Eager loads a list of records in the target table that are related to another
     * set of records in the source table. Source records can specified in two ways:
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
     * @param array $options The options for eager loading.
     * @return \Closure
     */
    abstract public function eagerLoader(array $options);

    /**
     * Handles cascading a delete from an associated model.
     *
     * Each implementing class should handle the cascaded delete as
     * required.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascaded delete.
     * @param array $options The options for the original delete.
     * @return bool Success
     */
    abstract public function cascadeDelete(EntityInterface $entity, array $options = []);

    /**
     * Returns whether or not the passed table is the owning side for this
     * association. This means that rows in the 'target' table would miss important
     * or required information if the row in 'source' did not exist.
     *
     * @param \Cake\ORM\Table $side The potential Table with ownership
     * @return bool
     */
    abstract public function isOwningSide(RepositoryInterface $side);

    /**
     * Extract the target's association data our from the passed entity and proxies
     * the saving operation to the target table.
     *
     * @param \Cake\Datasource\EntityInterface $entity the data to be saved
     * @param array|\ArrayObject $options The options for saving associated data.
     * @return bool|\Cake\Datasource\EntityInterface false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\ORM\Table::save()
     */
    abstract public function saveAssociated(EntityInterface $entity, array $options = []);
}
