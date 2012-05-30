<?php

namespace Cake\Model\Datasource\Database;

use PDO;

/**
 * Represents a database statement
 *
 **/
class Statement implements \IteratorAggregate, \Countable {

/**
 * Statement instance implementation, such as PDOStatement
 * or any other custom implementation
 *
 * @var mixed
 **/
	protected $_statement;

/**
 * Reference to the driver object associated to this statement
 *
 * @var Cake\Model\Datasource\Database\Driver
 **/
	protected $_driver;

/**
 * Human readable fetch type names to PDO equivalents
 *
 * @var array
 **/
	protected $_fetchMap = array(
		'num' => PDO::FETCH_NUM,
		'assoc' => PDO::FETCH_ASSOC
	);

/**
 * Constructor
 *
 * @param Statement implementation such as PDOStatement
 * @return void
 **/
	public function __construct($statement = null, $driver = null) {
		$this->_statement = $statement;
		$this->_driver = $driver;
	}

/**
 * Magic getter to return $queryString as read-only
 *
 * @param string $property internal property to get
 * @return mixed
 **/
	public function __get($property) {
		if ($property === 'queryString') {
			return $this->_statement->queryString;
		}
	}

	public function bindValue($column, $value, $type = null) {
		if ($type !== null && !ctype_digit($type)) {
			list($value, $type) = $this->_cast($value, $type);
		}
		$this->_statement->bindValue($column, $value, $type);
	}

	public function closeCursor() {
		$this->_statement->closeCursor();
	}

	public function columnCount() {
		return $this->_statement->columnCount();
	}

	public function errorCode() {
		return $this->_statement->errorCode();
	}

	public function errorInfo() {
		return $this->_statement->errorInfo();
	}

	public function execute($params = null) {
		return $this->_statement->execute($params = null);
	}

	public function fetch($type = 'num') {
		switch ($type) {
			case 'num':
				return $this->_statement->fetch(PDO::FETCH_NUM);
			case 'assoc':
				return $this->_statement->fetch(PDO::FETCH_ASSOC);
		}
	}

	public function fetchAll() {
		switch ($type) {
			case 'num':
				return $this->_statement->fetch(PDO::FETCH_NUM);
			case 'assoc':
				return $this->_statement->fetch(PDO::FETCH_ASSOC);
		}
	}

	public function rowCount() {
		return $this->_statement->rowCount();
	}

	public function getIterator() {
		return $this->_statement;
	}

	public function count() {
		return $this->rowCount();
	}

	protected function _cast($value, $type) {
		if (is_string($type)) {
			$type = Type::build($type);
		}
		if ($type instanceof Type) {
			$value = $type->toDatabase($value, $this->_driver);
			$type = $type->toStatement($value, $this->_driver);
		}
		return array($value, $type);
	}

}
