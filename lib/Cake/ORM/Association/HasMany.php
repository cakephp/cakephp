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
use Cake\ORM\Query;
use Cake\Utility\Inflector;
/**
 * Represents an N - 1 relationship. Where the target side of the relationship
 * will have one or multiple records per each one in the source side.
 */
class HasMany extends Association {

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
 * Sets the name of the field representing the foreign key to the target table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function foreignKey($key = null) {
		if ($key === null) {
			if ($this->_foreignKey === null) {
				$this->_foreignKey =  Inflector::underscore($this->source()->alias()) . '_id';
			}
			return $this->_foreignKey;
		}
		return parent::foreignKey($key);
	}

	public function attachTo(Query $query, array $options = []) {
		return false;
	}

	public function eagerLoader($results, $options = []) {
		$target = $this->target();
		$source = $this->source();
		$alias = $target->alias();
		$fetchQuery = $target->find('all');
		$options += [
			'foreignKey' => $this->foreignKey(),
			'conditions' => []
		];
		$options['conditions'] = array_merge($this->conditions(), $options['conditions']);
		$key = sprintf('%s.%s in', $alias, $options['foreignKey']);
		$fetchQuery
			->where($options['conditions'])
			->andWhere([$key => $results]);

		if (!empty($options['fields'])) {
			$fields = $fetchQuery->aliasFields($options['fields'], $alias);
			$required = $alias . '.' . $options['foreignKey'];
			if (!in_array($required, $fields)) {
				throw new \InvalidArgumentException(
					sprintf('You are required to select the "%s" field', $required)
				);
			}
			$fetchQuery->select($fields);
		}

		$resultMap = [];
		$key = $options['foreignKey'];
		foreach ($fetchQuery->execute() as $result) {
			$resultMap[$result[$key]][] = $result;
		}

		$sourceKey = key($fetchQuery->aliasField(
			$source->primaryKey(),
			$source->alias()
		));
		$targetKey = key($fetchQuery->aliasField($this->property(), $source->alias()));
		return function($row) use ($alias, $resultMap, $sourceKey, $targetKey) {
			if (isset($resultMap[$row[$sourceKey]])) {
				$row[$targetKey] = $resultMap[$row[$sourceKey]];
			}
			return $row;
		};
	}

}
