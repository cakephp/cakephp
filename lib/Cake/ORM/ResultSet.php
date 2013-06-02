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
namespace Cake\ORM;

use \Iterator;
use Cake\Database\Type;

class ResultSet implements Iterator {

	protected $_query;

	protected $_statement;

	protected $_getNext = false;

	protected $_count = 0;

	protected $_counter = -1;

	protected $_current;

	protected $_types = [];

	protected $_defaultTable;

	protected $_defaultAlias;

	protected $_associationMap = [];

	protected $_map;

	public function __construct($query, $statement) {
		$this->_query = $query;
		$this->_statement = $statement;
		$this->_defaultTable = $this->_query->repository();
		$this->_defaultAlias = $this->_defaultTable->alias();
		$this->_calculateAssociationMap();
	}

	public function _calculateAssociationMap() {
		$contain = $this->_query->normalizedContainments();

		if (!$contain) {
			return;
		}

		$map = [];
		$visitor = function($level) use (&$visitor, &$map) {
			foreach ($level as $assoc => $meta) {
				$map[$assoc] = $meta['instance'];
				if (!empty($meta['associations'])) {
					$visitor($meta['associations']);
				}
			}
		};
		$visitor($contain, []);
		$this->_associationMap = $map;
	}

	public function toArray() {
		return iterator_to_array($this);
	}

	public function current() {
		return $this->_groupResult($this->_current);
	}

	public function key() {
		return $this->_count;
	}

	public function next() {
		$this->_count++;
		$this->_fetchResult();
	}

	public function rewind() {
	}

	public function valid() {
		$this->_fetchResult();
		return $this->_current !== false;
	}

	protected function _fetchResult() {
		if ($this->_counter < $this->_count) {
			$this->_current = $this->_statement->fetch('assoc');
			$this->_counter = $this->_count;
		}
	}

	protected function _groupResult() {
		$results = [];
		foreach ($this->_current as $key => $value) {
			$table = $this->_defaultAlias;
			$field = $key;

			if (empty($this->_map[$key])) {
				$parts = explode('__', $key);
				if (count($parts) > 1) {
					$this->_map[$key] = $parts;
				}
			}

			if (!empty($this->_map[$key])) {
				list($table, $field) = $this->_map[$key];
			}

			$results[$table][$field] = $value;
		}

		$results[$this->_defaultAlias] = $this->_castValues(
			$this->_defaultTable,
			$results[$this->_defaultAlias]
		);

		foreach (array_reverse($this->_associationMap) as $alias => $assoc) {
			if (!isset($results[$alias])) {
				continue;
			}
			$results[$alias] = $this->_castValues($assoc->target(), $results[$alias]);
			$results = $assoc->transformRow($results);
		}

		return $results[$this->_defaultAlias];
	}

	protected function _castValues($table, $values) {
		$alias = $table->alias();
		$driver = $this->_query->connection()->driver();
		if (empty($this->types[$alias])) {
			$this->types[$alias] = array_map(function($f) {
				return $f['type'];
			}, $table->schema());
		}

		foreach ($values as $field => $value) {
			if (!isset($this->types[$alias][$field])) {
				continue;
			}
			if (is_string($this->types[$alias][$field])) {
				$this->types[$alias][$field] = Type::build($this->types[$alias][$field]);
			}
			$values[$field] = $this->types[$alias][$field]->toPHP($value, $driver);
		}

		return $values;
	}
}
