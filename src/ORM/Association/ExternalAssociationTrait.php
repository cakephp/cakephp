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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Association\SelectableAssociationTrait;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

/**
 * Represents a type of association that that needs to be recovered by performing
 * a extra query.
 */
trait ExternalAssociationTrait {

	use SelectableAssociationTrait;

/**
 * Order in which target records should be returned
 *
 * @var mixed
 */
	protected $_sort;

/**
 * Whether this association can be expressed directly in a query join
 *
 * @param array $options custom options key that could alter the return value
 * @return boolean if the 'matching' key in $option is true then this function
 * will return true, false otherwise
 */
	public function canBeJoined($options = []) {
		return !empty($options['matching']);
	}

/**
 * Sets the name of the field representing the foreign key to the source table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function foreignKey($key = null) {
		if ($key === null) {
			if ($this->_foreignKey === null) {
				$key = Inflector::singularize($this->source()->alias());
				$this->_foreignKey = Inflector::underscore($key) . '_id';
			}
			return $this->_foreignKey;
		}
		return parent::foreignKey($key);
	}

/**
 * Sets the sort order in which target records should be returned.
 * If no arguments are passed the currently configured value is returned
 * @param mixed $sort A find() compatible order clause
 * @return mixed
 */
	public function sort($sort = null) {
		if ($sort !== null) {
			$this->_sort = $sort;
		}
		return $this->_sort;
	}

/**
 * Correctly nests a result row associated values into the correct array keys inside the
 * source results.
 *
 * @param array $row
 * @param boolean $joined Whether or not the row is a result of a direct join
 * with this association
 * @return array
 */
	public function transformRow($row, $joined) {
		$sourceAlias = $this->source()->alias();
		$targetAlias = $this->target()->alias();

		$collectionAlias = $this->_name . '___collection_';
		if (isset($row[$collectionAlias])) {
			$values = $row[$collectionAlias];
		} else {
			$values = $row[$this->_name];
		}

		$row[$sourceAlias][$this->property()] = $values;
		return $row;
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
	protected function _joinCondition(array $options) {
		$conditions = [];
		$tAlias = $this->target()->alias();
		$sAlias = $this->source()->alias();
		$foreignKey = (array)$options['foreignKey'];
		$primaryKey = (array)$this->_sourceTable->primaryKey();

		if (count($foreignKey) !== count($primaryKey)) {
			$msg = 'Cannot match provided foreignKey, got %d columns expected %d';
			throw new \RuntimeException(sprintf($msg, count($foreignKey), count($primaryKey)));
		}

		foreach ($foreignKey as $k => $f) {
			$field = sprintf('%s.%s', $sAlias, $primaryKey[$k]);
			$value = new IdentifierExpression(sprintf('%s.%s', $tAlias, $f));
			$conditions[$field] = $value;
		}

		return $conditions;
	}

/**
 * Returns a callable to be used for each row in a query result set
 * for injecting the eager loaded rows
 *
 * @param \Cake\ORM\Query $fetchQuery the Query used to fetch results
 * @param array $resultMap an array with the foreignKey as keys and
 * the corresponding target table results as value.
 * @return \Closure
 */
	protected function _resultInjector($fetchQuery, $resultMap) {
		$source = $this->source();
		$sAlias = $source->alias();
		$tAlias = $this->target()->alias();

		$sourceKeys = [];
		foreach ((array)$source->primaryKey() as $key) {
			$sourceKeys[] = key($fetchQuery->aliasField($key, $sAlias));
		}

		$nestKey = $tAlias . '___collection_';

		if (count($sourceKeys) > 1) {
			return $this->_multiKeysInjector($resultMap, $sourceKeys, $nestKey);
		}

		$sourceKey = $sourceKeys[0];
		return function($row) use ($resultMap, $sourceKey, $nestKey) {
			if (isset($resultMap[$row[$sourceKey]])) {
				$row[$nestKey] = $resultMap[$row[$sourceKey]];
			}
			return $row;
		};
	}

/**
 * Returns a callable to be used for each row in a query result set
 * for injecting the eager loaded rows when the matching needs to
 * be done with multiple foreign keys
 *
 * @param array $resultMap a keyed arrays containing the target table
 * @param array $sourceKeys an array with aliased keys to match
 * @param string $nestKey the key under which results should be nested
 * @return \Closure
 */
	protected function _multiKeysInjector($resultMap, $sourceKeys, $nestKey) {
		return function($row) use ($resultMap, $sourceKeys, $nestKey) {
			$values = [];
			foreach ($sourceKeys as $key) {
				$values[] = $row[$key];
			}

			$key = implode(';', $values);
			if (isset($resultMap[$key])) {
				$row[$nestKey] = $resultMap[$key];
			}
			return $row;
		};
	}

/**
 * Parse extra options passed in the constructor.
 *
 * @param array $opts original list of options passed in constructor
 * @return void
 */
	protected function _options(array $opts) {
		if (isset($opts['sort'])) {
			$this->sort($opts['sort']);
		}
	}

}
