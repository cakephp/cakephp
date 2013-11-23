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
namespace Cake\ORM\Association;

use Cake\ORM\Association;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Represents an M - N relationship where there exists a pivot - or join - table
 * that contains the association fields between the source and the target table.
 *
 * An example of a BelongsToMany association would be Article belongs to many Tags.
 */
class BelongsToMany extends Association {

	use ExternalAssociationTrait {
		_options as _externalOptions;
	}

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = false;

/**
 * The type of join to be used when adding the association to a query
 *
 * @var string
 */
	protected $_joinType = 'INNER';

/**
 * The strategy name to be used to fetch associated records.
 *
 * @var string
 */
	protected $_strategy = parent::STRATEGY_SELECT;

/**
 * Pivot table instance
 *
 * @var Cake\ORM\Table
 */
	protected $_pivotTable;

/**
 * The physical name of the pivot table
 *
 * @var string
 */
	protected $_joinTable;

/**
 * The name of the hasMany association from the target table
 * to the pivot table
 *
 * @var string
 */
	protected $_pivotAssociationName;

/**
 * Sets the table instance for the pivot relation. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param string|Cake\ORM\Table $table Name or instance for the join table
 * @return Cake\ORM\Table
 */
	public function pivot($table = null) {
		$target = $this->target();
		$source = $this->source();
		$sAlias = $source->alias();
		$tAlias = $target->alias();

		if ($table === null) {
			if (empty($this->_pivotTable)) {
				$tableName = $this->_joinTableName();
				$tableAlias = Inflector::camelize($tableName);
				$table = TableRegistry::get($tableAlias, [
					'table' => $tableName
				]);
			} else {
				return $this->_pivotTable;
			}
		}

		if (is_string($table)) {
			$table = TableRegistry::get($table);
		}

		if (!$table->association($sAlias)) {
			$table->belongsTo($sAlias)->target($this->source());
		}

		if (!$table->association($tAlias)) {
			$table->belongsTo($tAlias)->target($this->target());
		}

		if (!$target->association($table->alias())) {
			$target->belongsToMany($sAlias);
			$target->hasMany($table->alias())->target($table);
		}

		if (!$source->association($table->alias())) {
			$source->hasMany($table->alias())->target($table);
		}

		return $this->_pivotTable = $table;
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
 * - conditions: array with a list of conditions to filter the join with
 * - fields: a list of fields in the target table to include in the result
 * - type: The type of join to be used (e.g. INNER)
 *
 * @param Query $query the query to be altered to include the target table data
 * @param array $options Any extra options or overrides to be taken in account
 * @return void
 */
	public function attachTo(Query $query, array $options = []) {
		parent::attachTo($query, $options);
		$pivot = $this->pivot();
		$belongsTo = $pivot->association($this->source()->alias());
		$cond = $belongsTo->_joinCondition(['foreignKey' => $belongsTo->foreignKey()]);

		if (isset($options['includeFields'])) {
			$includeFields = $options['includeFields'];
		}

		$options = ['conditions' => [$cond]] + compact('includeFields');
		$this->target()
			->association($this->pivot()->alias())
			->attachTo($query, $options);
	}

/**
 * Return false as join conditions are defined in the pivot table
 *
 * @param array $options list of options passed to attachTo method
 * @return boolean false
 */
	protected function _joinCondition(array $options) {
		return false;
	}

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
 * Options array accept the following keys:
 *
 * - query: Query object setup to find the source table records
 * - keys: List of primary key values from the source table
 * - foreignKey: The name of the field used to relate both tables
 * - conditions: List of conditions to be passed to the query where() method
 * - sort: The direction in which the records should be returned
 * - fields: List of fields to select from the target table
 * - contain: List of related tables to eager load associated to the target table
 * - strategy: The name of strategy to use for finding target table records
 *
 * @param array $options
 * @return \Closure
 */
	public function eagerLoader(array $options) {
		$options += [
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'sort' => $this->sort(),
			'strategy' => $this->strategy()
		];
		$fetchQuery = $this->_buildQuery($options);
		$resultMap = [];
		$key = $options['foreignKey'];
		$property = $this->target()->association($this->pivot()->alias())->property();

		foreach ($fetchQuery->execute() as $result) {
			$resultMap[$result[$property][$key]][] = $result;
		}

		return $this->_resultInjector($fetchQuery, $resultMap);
	}

/**
 * Clear out the data in the join/pivot table for a given entity.
 *
 * @param Cake\ORM\Entity $entity The entity that started the cascading delete.
 * @param array $options The options for the original delete.
 * @return boolean Success.
 */
	public function cascadeDelete(Entity $entity, $options = []) {
		$foreignKey = $this->foreignKey();
		$primaryKey = $this->source()->primaryKey();
		$conditions = [
			$foreignKey => $entity->get($primaryKey)
		];
		// TODO fix multi-column primary keys.
		$conditions = array_merge($conditions, $this->conditions());

		$table = $this->pivot();
		if ($this->_cascadeCallbacks) {
			foreach ($table->find('all')->where($conditions) as $related) {
				$table->delete($related, $options);
			}
			return true;
		}
		return $table->deleteAll($conditions);
	}

/**
 * Returns boolean false, as none of the tables 'own' rows in the other side
 * of the association
 *
 * @return boolean
 */
	public function isOwningSide() {
		return false;
	}

/**
 * Appends any conditions required to load the relevant set of records in the
 * target table query given a filter key and some filtering values.
 *
 * @param \Cake\ORM\Query target table's query
 * @param string $key the fields that should be used for filtering
 * @param mixed $filter the value that should be used to match for $key
 * @return \Cake\ORM\Query
 */
	protected function _addFilteringCondition($query, $key, $filter) {
		return $query->contain([
			$this->_pivotAssociationName() => [
				'conditions' => [$key . ' in' => $filter],
				'matching' => true
			]
		]);
	}

/**
 * Generates a string used as a table field that contains the values upon
 * which the filter should be applied
 *
 * params array $options
 * @return string
 */
	protected function _linkField($options) {
		return sprintf('%s.%s', $this->_pivotAssociationName(), $options['foreignKey']);
	}

/**
 * Returns the name of the association from the target table to the pivot table,
 * this name is used to generate alias in the query and to later on retrieve the
 * results.
 *
 * @return string
 */
	protected function _pivotAssociationName() {
		if (!$this->_pivotAssociationName) {
			$this->_pivotAssociationName = $this->target()
				->association($this->pivot()->alias())
				->name();
		}
		return $this->_pivotAssociationName;
	}

/**
 * Sets the name of the pivot table.
 * If no arguments are passed the current configured name is returned. A default
 * name based of the associated tables will be generated if none found.
 *
 * @param string $name
 * @return string
 */
	protected function _joinTableName($name = null) {
		if ($name === null) {
			if (empty($this->_joinTable)) {
				$aliases = array_map('\Cake\Utility\Inflector::underscore', [
					$sAlias = $this->source()->alias(),
					$tAlias = $this->target()->alias()
				]);
				sort($aliases);
				$this->_joinTable = implode('_', $aliases);
			}
			return $this->_joinTable;
		}
		return $this->_joinTable = $name;
	}

/**
 * Parse extra options passed in the constructor.
 * @param array $opts original list of options passed in constructor
 *
 * @return void
 */
	protected function _options(array $opts) {
		if (!empty($opts['through'])) {
			$this->pivot($opts['through']);
		}
		if (!empty($opts['joinTable'])) {
			$this->_joinTableName($opts['joinTable']);
		}
		$this->_externalOptions($opts);
	}

}
