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

use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\ResultSetDecorator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * An Association is a relationship established between two tables and is used
 * to configure and customize the way interconnected records are retrieved.
 *
 */
abstract class Association {

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
 * Association type for one to one associations.
 *
 * @var string
 */
	const ONE_TO_ONE = 'oneToOne';

/**
 * Association type for one to many associations.
 *
 * @var string
 */
	const ONE_TO_MANY = 'oneToMany';

/**
 * Association type for many to many associations.
 *
 * @var string
 */
	const MANY_TO_MANY = 'manyToMany';

/**
 * Association type for many to one associations.
 *
 * @var string
 */
	const MANY_TO_ONE = 'manyToOne';

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
 * @var string
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
 * Constructor. Subclasses can override _options function to get the original
 * list of passed options if expecting any other special key
 *
 * @param string $name The name given to the association
 * @param array $options A list of properties to be set on this object
 */
	public function __construct($name, array $options = []) {
		$defaults = [
			'className',
			'foreignKey',
			'conditions',
			'dependent',
			'cascadeCallbacks',
			'sourceTable',
			'targetTable',
			'joinType',
			'propertyName'
		];
		foreach ($defaults as $property) {
			if (isset($options[$property])) {
				$this->{'_' . $property} = $options[$property];
			}
		}

		$this->_name = $name;
		$this->_options($options);

		if (!empty($options['strategy'])) {
			$this->strategy($options['strategy']);
		}
	}

/**
 * Sets the name for this association. If no argument is passed then the current
 * configured name will be returned
 *
 * @param string $name Name to be assigned
 * @return string
 */
	public function name($name = null) {
		if ($name !== null) {
			$this->_name = $name;
		}
		return $this->_name;
	}

/**
 * Sets the table instance for the source side of the association. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param \Cake\ORM\Table $table the instance to be assigned as source side
 * @return \Cake\ORM\Table
 */
	public function source(Table $table = null) {
		if ($table === null) {
			return $this->_sourceTable;
		}
		return $this->_sourceTable = $table;
	}

/**
 * Sets the table instance for the target side of the association. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param \Cake\ORM\Table $table the instance to be assigned as target side
 * @return \Cake\ORM\Table
 */
	public function target(Table $table = null) {
		if ($table === null && $this->_targetTable) {
			return $this->_targetTable;
		}

		if ($table !== null) {
			return $this->_targetTable = $table;
		}

		if ($table === null) {
			$config = [];
			if (!TableRegistry::exists($this->_name)) {
				$config = ['className' => $this->_className];
			}
			$this->_targetTable = TableRegistry::get($this->_name, $config);
		}
		return $this->_targetTable;
	}

/**
 * Sets a list of conditions to be always included when fetching records from
 * the target association. If no parameters are passed the current list is returned
 *
 * @param array $conditions list of conditions to be used
 * @see \Cake\Database\Query::where() for examples on the format of the array
 * @return array
 */
	public function conditions($conditions = null) {
		if ($conditions !== null) {
			$this->_conditions = $conditions;
		}
		return $this->_conditions;
	}

/**
 * Sets the name of the field representing the foreign key to the target table.
 * If no parameters are passed the current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string|array
 */
	public function foreignKey($key = null) {
		if ($key !== null) {
			$this->_foreignKey = $key;
		}
		return $this->_foreignKey;
	}

/**
 * Sets whether the records on the target table are dependent on the source table.
 *
 * This is primarily used to indicate that records should be removed if the owning record in
 * the source table is deleted.
 *
 * If no parameters are passed the current setting is returned.
 *
 * @param bool $dependent
 * @return bool
 */
	public function dependent($dependent = null) {
		if ($dependent !== null) {
			$this->_dependent = $dependent;
		}
		return $this->_dependent;
	}

/**
 * Whether this association can be expressed directly in a query join
 *
 * @param array $options custom options key that could alter the return value
 * @return bool
 */
	public function canBeJoined(array $options = []) {
		$strategy = isset($options['strategy']) ? $options['strategy'] : $this->strategy();
		return $strategy == $this::STRATEGY_JOIN;
	}

/**
 * Sets the type of join to be used when adding the association to a query.
 * If no arguments are passed, the currently configured type is returned.
 *
 * @param string $type the join type to be used (e.g. INNER)
 * @return string
 */
	public function joinType($type = null) {
		if ($type === null) {
			return $this->_joinType;
		}
		return $this->_joinType = $type;
	}

/**
 * Sets the property name that should be filled with data from the target table
 * in the source table record.
 * If no arguments are passed, the currently configured type is returned.
 *
 * @param string $name
 * @return string
 */
	public function property($name = null) {
		if ($name !== null) {
			$this->_propertyName = $name;
		}
		if ($name === null && !$this->_propertyName) {
			list($plugin, $name) = pluginSplit($this->_name);
			$this->_propertyName = Inflector::underscore($name);
		}
		return $this->_propertyName;
	}

/**
 * Sets the strategy name to be used to fetch associated records. Keep in mind
 * that some association types might not implement but a default strategy,
 * rendering any changes to this setting void.
 * If no arguments are passed, the currently configured strategy is returned.
 *
 * @param string $name
 * @return string
 * @throws \InvalidArgumentException When an invalid strategy is provided.
 */
	public function strategy($name = null) {
		if ($name !== null) {
			$valid = [self::STRATEGY_JOIN, self::STRATEGY_SELECT, self::STRATEGY_SUBQUERY];
			if (!in_array($name, $valid)) {
				throw new \InvalidArgumentException(
					sprintf('Invalid strategy "%s" was provided', $name)
				);
			}
			$this->_strategy = $name;
		}
		return $this->_strategy;
	}

/**
 * Override this function to initialize any concrete association class, it will
 * get passed the original list of options used in the constructor
 *
 * @param array $options List of options used for initialization
 * @return void
 */
	protected function _options(array $options) {
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
 * - matching: Indicates whether the query records should be filtered based on
 *   the records found on this association. This will force a 'INNER JOIN'
 * - aliasPath: A dot separated string representing the path of association names
 *   followed from the passed query main table to this association.
 * - propertyPath: A dot separated string representing the path of association
 *   properties to be followed from the passed query main entity to this
 *   association
 *
 * @param Query $query the query to be altered to include the target table data
 * @param array $options Any extra options or overrides to be taken in account
 * @return void
 * @throws \RuntimeException if the query builder passed does not return a query
 * object
 */
	public function attachTo(Query $query, array $options = []) {
		$target = $this->target();
		$options += [
			'includeFields' => true,
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'fields' => [],
			'type' => empty($options['matching']) ? $this->joinType() : 'INNER',
			'table' => $target->table()
		];
		$options['conditions'] = array_merge($this->conditions(), $options['conditions']);

		if (!empty($options['foreignKey'])) {
			$joinCondition = $this->_joinCondition($options);
			if ($joinCondition) {
				$options['conditions'][] = $joinCondition;
			}
		}

		$dummy = $target->query()->eagerLoaded(true);
		if (!empty($options['queryBuilder'])) {
			$dummy = $options['queryBuilder']($dummy);
			if (!($dummy instanceof Query)) {
				throw new \RuntimeException(sprintf(
					'Query builder for association "%s" did not return a query',
					$this->name()
				));
			}
		}

		$dummy->where($options['conditions']);
		$this->_dispatchBeforeFind($dummy);

		$joinOptions = ['table' => 1, 'conditions' => 1, 'type' => 1];
		$options['conditions'] = $dummy->clause('where');
		$query->join([$target->alias() => array_intersect_key($options, $joinOptions)]);

		$this->_appendFields($query, $dummy, $options);
		$this->_formatAssociationResults($query, $dummy, $options);
		$this->_bindNewAssociations($query, $dummy, $options);
	}

/**
 * Correctly nests a result row associated values into the correct array keys inside the
 * source results.
 *
 * @param array $row
 * @param string $nestKey The array key under which the results for this association
 * should be found
 * @param bool $joined Whether or not the row is a result of a direct join
 * with this association
 * @return array
 */
	public function transformRow($row, $nestKey, $joined) {
		$sourceAlias = $this->source()->alias();
		$nestKey = $nestKey ?: $this->_name;
		if (isset($row[$sourceAlias])) {
			$row[$sourceAlias][$this->property()] = $row[$nestKey];
			unset($row[$nestKey]);
		}
		return $row;
	}

/**
 * Returns a modified row after appending a property for this association
 * with the default empty value according to whether the association was
 * joined or fetched externally.
 *
 * @param array $row
 * @param bool $joined Whether or not the row is a result of a direct join
 * with this association
 * @return array
 */
	public function defaultRowValue($row, $joined) {
		$sourceAlias = $this->source()->alias();
		if (isset($row[$sourceAlias])) {
			$row[$sourceAlias][$this->property()] = null;
		}
		return $row;
	}

/**
 * Proxies the finding operation to the target table's find method
 * and modifies the query accordingly based of this association
 * configuration
 *
 * @param string|array $type the type of query to perform, if an array is passed,
 * it will be interpreted as the `$options` parameter
 * @param array $options
 * @see \Cake\ORM\Table::find()
 * @return \Cake\ORM\Query
 */
	public function find($type = 'all', array $options = []) {
		return $this->target()
			->find($type, $options)
			->where($this->conditions());
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
	public function updateAll($fields, $conditions) {
		$target = $this->target();
		$expression = $target->query()
			->where($this->conditions())
			->where($conditions)
			->clause('where');
		return $target->updateAll($fields, $expression);
	}

/**
 * Proxies the delete operation to the target table's deleteAll method
 *
 * @param mixed $conditions Conditions to be used, accepts anything Query::where()
 * can take.
 * @return bool Success Returns true if one or more rows are affected.
 * @see \Cake\ORM\Table::delteAll()
 */
	public function deleteAll($conditions) {
		$target = $this->target();
		$expression = $target->query()
			->where($this->conditions())
			->where($conditions)
			->clause('where');
		return $target->deleteAll($expression);
	}

/**
 * Triggers beforeFind on the target table for the query this association is
 * attaching to
 *
 * @param \Cake\ORM\Query $query the query this association is attaching itself to
 * @return void
 */
	protected function _dispatchBeforeFind($query) {
		$table = $this->target();
		$options = $query->getOptions();
		$event = new Event('Model.beforeFind', $table, [$query, $options, false]);
		$table->getEventManager()->dispatch($event);
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
	protected function _appendFields($query, $surrogate, $options) {
		$options['fields'] = $surrogate->clause('select') ?: $options['fields'];
		$target = $this->_targetTable;
		if (empty($options['fields'])) {
			$f = isset($options['fields']) ? $options['fields'] : null;
			if ($options['includeFields'] && ($f === null || $f !== false)) {
				$options['fields'] = $target->schema()->columns();
			}
		}

		if (!empty($options['fields'])) {
			$query->select($query->aliasFields($options['fields'], $target->alias()));
		}
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
	protected function _formatAssociationResults($query, $surrogate, $options) {
		$formatters = $surrogate->formatResults();

		if (!$formatters) {
			return;
		}

		$property = $options['propertyPath'];
		$query->formatResults(function($results) use ($formatters, $property) {
			$extracted = $results->extract($property)->compile();
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
	protected function _bindNewAssociations($query, $surrogate, $options) {
		$contain = $surrogate->contain();
		$target = $this->_targetTable;

		if (!$contain) {
			return;
		}

		$loader = $surrogate->eagerLoader();
		$loader->attachAssociations($query, $target, $options['includeFields']);
		$newBinds = [];
		foreach ($contain as $alias => $value) {
			$newBinds[$options['aliasPath'] . '.' . $alias] = $value;
		}
		$query->contain($newBinds);
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
	protected function _joinCondition($options) {
		$conditions = [];
		$tAlias = $this->target()->alias();
		$sAlias = $this->source()->alias();
		$foreignKey = (array)$options['foreignKey'];
		$primaryKey = (array)$this->_sourceTable->primaryKey();

		if (count($foreignKey) !== count($primaryKey)) {
			$msg = 'Cannot match provided foreignKey for "%s", got "(%s)" but expected foreign key for "(%s)"';
			throw new \RuntimeException(sprintf(
				$msg,
				$this->_name,
				implode(', ', $foreignKey),
				implode(', ', $primaryKey)
			));
		}

		foreach ($foreignKey as $k => $f) {
			$field = sprintf('%s.%s', $sAlias, $primaryKey[$k]);
			$value = new IdentifierExpression(sprintf('%s.%s', $tAlias, $f));
			$conditions[$field] = $value;
		}

		return $conditions;
	}

/**
 * Proxies property retrieval to the target table. This is handy for getting this
 * association's associations
 *
 * @param string $property the property name
 * @return \Cake\ORM\Association
 * @throws \RuntimeException if no association with such name exists
 */
	public function __get($property) {
		return $this->target()->{$property};
	}

/**
 * Proxies the isset call to the target table. This is handy to check if the
 * target table has another association with the passed name
 *
 * @param string $property the property name
 * @return bool true if the property exists
 */
	public function __isset($property) {
		return isset($this->target()->{$property});
	}

/**
 * Proxies method calls to the target table.
 *
 * @param string $method name of the method to be invoked
 * @param array $args List of arguments passed to the function
 * @return mixed
 * @throws \BadMethodCallException
 */
	public function __call($method, $argument) {
		return call_user_func_array([$this->target(), $method], $argument);
	}

/**
 * Get the relationship type.
 *
 * @return string Constant of either ONE_TO_ONE, MANY_TO_ONE, ONE_TO_MANY or MANY_TO_MANY.
 */
	public abstract function type();

/**
 * Eager loads a list of records in the target table that are related to another
 * set of records in the source table. Source records can specified in two ways:
 * first one is by passing a Query object setup to find on the source table and
 * the other way is by explicitly passing an array of primary key values from
 * the source table.
 *
 * The required way of passing related source records is controlled by "strategy"
 * By default the subquery strategy is used, which requires a query on the source
 * When using the select strategy, the list of primary keys will be used.
 *
 * Returns a closure that should be run for each record returned in an specific
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
 * @param array $options
 * @return \Closure
 */
	public abstract function eagerLoader(array $options);

/**
 * Handles cascading a delete from an associated model.
 *
 * Each implementing class should handle the cascaded delete as
 * required.
 *
 * @param \Cake\ORM\Entity $entity The entity that started the cascaded delete.
 * @param array $options The options for the original delete.
 * @return bool Success
 */
	public abstract function cascadeDelete(Entity $entity, array $options = []);

/**
 * Returns whether or not the passed table is the owning side for this
 * association. This means that rows in the 'target' table would miss important
 * or required information if the row in 'source' did not exist.
 *
 * @param \Cake\ORM\Table $side The potential Table with ownership
 * @return bool
 */
	public abstract function isOwningSide(Table $side);

/**
 * Proxies the saving operation for an entity to the target table
 *
 * @param \Cake\ORM\Entity $entity the data to be saved
 * @param array|\ArrayObject $options
 * @return bool|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 */
	public abstract function save(Entity $entity, array $options = []);

}
