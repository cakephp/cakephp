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

	public function __construct($query, $statement) {
		$this->_query = $query;
		$this->_statement = $statement;
		$this->_defaultTable = $this->_query->repository()->alias();
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
			$parts = explode('__', $key);
			$table = $this->_defaultTable;
			$field = $key;
			if (count($parts) > 1) {
				list($table, $field) = $parts;
				$value = $this->_castValue($table, $field, $value);
			}
			$results[$table][$field] = $value;
		}
		return $results;
	}

	protected function _castValue($table, $field, $value) {
		$schema = $this->_query->aliasedTable($table)->schema();
		if (!isset($schema[$field])) {
			return $value;
		}

		$key = $table . '.' . $value;
		if (!isset($this->types[$key])) {
			$this->types[$key] = Type::build($schema[$field]['type']);
		}
		return $this->types[$key]->toPHP($value, $this->_query->connection()->driver());
	}
}
