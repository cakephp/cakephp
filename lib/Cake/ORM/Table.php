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

class Table {

	protected $_name;

	protected $_alias;

	protected $_connection;

	protected $_schema;

	public function __construct($config = array()) {
		if (!empty($config['name'])) {
			$this->_name = $config['name'];
		}

		if (!empty($config['alias'])) {
			$this->alias($config['alias']);
		} else {
			$this->alias($this->_name);
		}

		if (!empty($config['connection'])) {
			$this->connection($config['connection']);
		}
		if (!empty($config['schema'])) {
			$this->schema($config['schema']);
		}
	}

	public function alias($alias = null) {
		if ($alias !== null) {
			$this->_alias = $alias;
		}
		return $this->_alias;
	}

	public function connection($conn = null) {
		if ($conn === null) {
			return $this->_connection;
		}
		return $this->_connection = $conn;
	}

	public function schema($schema = null) {
		if ($schema === null) {
			if ($this->_schema === null) {
				$this->_schema = $this->connection()->describe($this->_name);
			}
			return $this->_schema;
		}
		return $this->_schema = $schema;
	}

	public function find($type, $options = []) {
		return $this->{'find' . ucfirst($type)}($this->buildQuery(), $options);
	}

	public function findAll(Query $query, array $options = []) {
		return $query;
	}

	public function findFirst(Query $query, array $options = []) {
		return $query->limit(1);
	}

	protected function buildQuery() {
		$query = new Query($this->connection());
		return $query
			->repository($this)
			->select()
			->from([$this->_alias => $this->_name]);
	}

}
