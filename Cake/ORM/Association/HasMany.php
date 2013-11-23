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
use Cake\ORM\Association\DependentDeleteTrait;
use Cake\ORM\Association\ExternalAssociationTrait;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * Represents an N - 1 relationship where the target side of the relationship
 * will have one or multiple records per each one in the source side.
 *
 * An example of a HasMany association would be Author has many Articles.
 */
class HasMany extends Association {

	use DependentDeleteTrait;
	use ExternalAssociationTrait;

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
		foreach ($fetchQuery->execute() as $result) {
			$resultMap[$result[$key]][] = $result;
		}

		return $this->_resultInjector($fetchQuery, $resultMap);
	}

/**
 * Returns boolean true, as target rows could not exist or would miss information
 * should corresponding rows in source association not exist.
 *
 * @return boolean
 */
	public function isOwningSide() {
		return true;
	}

/**
 * Proxies the saving operation for an entity to the target table
 *
 * @param \Cake\ORM\Entity $entity the data to be saved
 * @param array|\ArrayObject $options
 * @return boolean|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 */
	public function save(Entity $entity, $options = []) {
		return $entity;
	}

}
