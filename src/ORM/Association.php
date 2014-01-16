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

use Cake\ORM\Entity;
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
 * Name given to the association, it usually represents the alias
 * assigned to the target associated table
 *
 * @var string
 */
	protected $_name;

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = false;

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
 * @var boolean
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
 * @var Cake\ORM\Table
 */
	protected $_sourceTable;

/**
 * Target table instance
 *
 * @var Cake\ORM\Table
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
 * @return void
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
 * @param Cake\ORM\Table $table the instance to be assigned as source side
 * @return Cake\ORM\Table
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
 * @param Cake\ORM\Table $table the instance to be assigned as target side
 * @return Cake\ORM\Table
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
 * @see Cake\Database\Query::where() for examples on the format of the array
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
 * @param boolean $dependent
 * @return boolean
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
 * @return boolean
 */
	public function canBeJoined($options = []) {
		return $this->_canBeJoined;
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
 *
 * @param Query $query the query to be altered to include the target table data
 * @param array $options Any extra options or overrides to be taken in account
 * @return void
 */
	public function attachTo(Query $query, array $options = []) {
		$target = $this->target();
		$source = $this->source();
		$options += [
			'includeFields' => true,
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'fields' => [],
			'type' => $this->joinType(),
			'table' => $target->table()
		];
		$options['conditions'] = array_merge($this->conditions(), $options['conditions']);

		if (!empty($options['foreignKey'])) {
			$joinCondition = $this->_joinCondition($options);
			if ($joinCondition) {
				$options['conditions'][] = $joinCondition;
			}
		}

		$options['conditions'] = $query->newExpr()->add($options['conditions']);

		if (!empty($options['queryBuilder'])) {
			$newQuery = $options['queryBuilder']($target->query());
			$options['fields'] = $newQuery->clause('select') ?: $options['fields'];
			$options['conditions']->add($newQuery->clause('where'));
		}

		$joinOptions = ['table' => 1, 'conditions' => 1, 'type' => 1];
		$query->join([$target->alias() => array_intersect_key($options, $joinOptions)]);

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
 * Correctly nests a result row associated values into the correct array keys inside the
 * source results.
 *
 * @param array $result
 * @return array
 */
	public function transformRow($row) {
		$sourceAlias = $this->source()->alias();
		$targetAlias = $this->target()->alias();
		if (isset($row[$sourceAlias])) {
			$row[$sourceAlias][$this->property()] = $row[$targetAlias];
		}
		return $row;
	}

/**
 * Get the relationship type.
 *
 * @return string Constant of either ONE_TO_ONE, MANY_TO_ONE, or MANY_TO_MANY.
 */
	public function type() {
		return self::ONE_TO_ONE;
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
	public function find($type = 'all', $options = []) {
		return $this->target()
			->find($type, $options)
			->where($this->conditions());
	}

/**
 * Returns a single or multiple condition(s) to be appended to the generated join
 * clause for getting the results on the target table. If false is returned then
 * it will not attach any new conditions to the join clause
 *
 * @param array $options list of options passed to attachTo method
 * @return string|array|boolean
 */
	protected abstract function _joinCondition(array $options);

/**
 * Handles cascading a delete from an associated model.
 *
 * Each implementing class should handle the cascaded delete as
 * required.
 *
 * @param Cake\ORM\Entity $entity The entity that started the cascaded delete.
 * @param array $options The options for the original delete.
 * @return boolean Success
 */
	public abstract function cascadeDelete(Entity $entity, $options = []);

/**
 * Returns whether or not the passed table is the owning side for this
 * association. This means that rows in the 'target' table would miss important
 * or required information if the row in 'source' did not exist.
 *
 * @return boolean
 */
	public abstract function isOwningSide(Table $side);

/**
 * Proxies the saving operation for an entity to the target table
 *
 * @param \Cake\ORM\Entity $entity the data to be saved
 * @param array|\ArrayObject $options
 * @return boolean|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 */
	public abstract function save(Entity $entity, $options = []);

}
