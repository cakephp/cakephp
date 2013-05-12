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
 * Represents an 1 - N relationship where the source side of the relation is
 * related to only one record in the target table.
 *
 */
class BelongsTo extends Association {

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = true;

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
				$this->_foreignKey =  Inflector::underscore($this->target()->alias()) . '_id';
			}
			return $this->_foreignKey;
		}
		return parent::foreignKey($key);
	}

/**
 * Alters a Query object to include the associated target table data in the final
 * result
 *
 * The options array accept the following keys:
 *
 * - includeFields: Whether to include target model fields in the result or not
 * - foreignKey: The name of the field to use as foreign key, if false none
 *   will be sued
 * - conditions: array with a list of conditions to filter the join with
 * - fields: a list of fields in the target table to include in the result
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
			'conditions' => []
		];
		$options['conditions'] = array_merge($this->conditions(), $options['conditions']);

		if (!empty($options['foreignKey'])) {
			$options['conditions'][] =  sprintf('%s.%s = %s.%s',
				$target->alias(),
				$target->primaryKey(),
				$source->alias(),
				$options['foreignKey']
			);
		}

		$joinOptions = ['table' => 1, 'conditions' => 1, 'type' => 1];
		$query->join([$target->alias() => array_intersect_key($options, $joinOptions)]);

		if (empty($options['fields'])) {
			$f = isset($options['fields']) ? $options['fields'] : null;
			if ($options['includeFields'] && ($f === null || $f !== false)) {
				$options['fields'] = array_keys($target->schema());
			}
		}

		if (!empty($options['fields'])) {
			$query->select($query->aliasFields($options['fields'], $target->alias()));
		}
	}

}
