<?php

namespace Cake\Model\Datasource\Database;

use PDO;

/**
 * Represents a database statement. Statements contains queries that can be
 * executed multiple times by binding different values on each call. This class
 * also helps convert values to their valid representation for the corresponding
 * types.
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

/**
 * Assign a value to an positional or named variable in prepared query. If using
 * positional variables you need to start with index one, if using named params then
 * just use the name in any order.
 *
 * You can pass PDO compatible constants for binding values with a type or optionally
 * any type name registered in the Type class. Any value will be converted to the valid type
 * representation if needed.
 *
 * It is not allowed to combine positional and named variables in the same statement
 *
 * ## Examples:
 *
 *	`$statement->bindValue(1, 'a title');`
 *	`$statement->bindValue(2, 5, PDO::INT);`
 *	`$statement->bindValue('active', true, 'boolean');`
 *	`$statement->bindValue(5, new \DateTime(), 'date');`
 *
 * @param string|integer $column name or param position to be bound
 * @param mixed $value the value to bind to variable in query
 * @param string|integer $type PDO type or name of configured Type class
 * @return void
 **/
	public function bindValue($column, $value, $type = null) {
		if ($type !== null && !ctype_digit($type)) {
			list($value, $type) = $this->_cast($value, $type);
		}
		$this->_statement->bindValue($column, $value, $type);
	}

/**
 * Closes a cursor in the database, freeing up any resources and memory
 * allocated to it. In most cases you don't need to call this method, as it is
 * automatically called after fetching all results from the result set.
 *
 * @return void
 **/
	public function closeCursor() {
		$this->_statement->closeCursor();
	}

/**
 * Returns the number of columns this statement's results will contain
 *
 * ## Example:
 *
 * {{{
 *	$statement = $connection->prepare('SELECT id, title from articles');
 *	$statement->execute();
 *	echo $statement->columnCount(); // outputs 2
 * }}}
 *
 * @return integer
 **/
	public function columnCount() {
		return $this->_statement->columnCount();
	}

/**
 * Returns the error code for the last error that occurred when executing this statement
 *
 * @return integer|string
 **/
	public function errorCode() {
		return $this->_statement->errorCode();
	}

/**
 * Returns the error information for the last error that occurred when executing
 * this statement
 *
 * @return array
 **/
	public function errorInfo() {
		return $this->_statement->errorInfo();
	}

/**
 * Executes the statement by sending the SQL query to the database. It can optionally
 * take an array or arguments to be bound to the query variables. Please note
 * that binding parameters from this method will not perform any custom type conversion
 * as it would normally happen when calling `bindValue`
 *
 * $param array $params list of values to be bound to query
 * @return boolean true on success, false otherwise
 **/
	public function execute($params = null) {
		return $this->_statement->execute($params);
	}

/**
 * Returns the next row for the result set after executing this statement.
 * Rows can be fetched to contain columns as names or positions. If no
 * rows are left in result set, this method will return false
 *
 * ## Example:
 *
 * {{{
 *	$statement = $connection->prepare('SELECT id, title from articles');
 *	$statement->execute();
 *	print_r($statement->fetch('assoc')); // will show array('id' => 1, 'title' => 'a title')
 * }}}
 *
 * @param string $type 'num' for positional columns, assoc for named columns
 * @return mixed|boolean result array containing columns and values or false if no results
 * are left
 **/
	public function fetch($type = 'num') {
		switch ($type) {
			case 'num':
				return $this->_statement->fetch(PDO::FETCH_NUM);
			case 'assoc':
				return $this->_statement->fetch(PDO::FETCH_ASSOC);
		}
	}

/**
 * Returns an array with all rows resulting from executing this statement
 *
 * ## Example:
 *
 * {{{
 *	$statement = $connection->prepare('SELECT id, title from articles');
 *	$statement->execute();
 *	print_r($statement->fetchAll('assoc')); // will show array(0 => array('id' => 1, 'title' => 'a title'))
 * }}}
 *
 * @param string $type num for fetching columns as positional keys or assoc for column names as keys
 * @return array list of all results from database for this statement
 **/
	public function fetchAll($type = 'num') {
		switch ($type) {
			case 'num':
				return $this->_statement->fetch(PDO::FETCH_NUM);
			case 'assoc':
				return $this->_statement->fetch(PDO::FETCH_ASSOC);
		}
	}

/**
 * Returns the number of rows affected by this SQL statement
 *
 * ## Example:
 *
 * {{{
 *	$statement = $connection->prepare('SELECT id, title from articles');
 *	$statement->execute();
 *	print_r($statement->rowCount()); // will show 1
 * }}}
 *
 * @return integer
 **/
	public function rowCount() {
		return $this->_statement->rowCount();
	}

/**
 * Statements are iterable as arrays, this method will return
 * the iterator object for traversing all items in the result.
 *
 * ## Example:
 *
 * {{{
 *	$statement = $connection->prepare('SELECT id, title from articles');
 *	$statement->execute();
 *	foreach ($statement as $row) {
 *		//do stuff
 *	}
 * }}}
 *
 * @return Iterator
 **/
	public function getIterator() {
		return $this->_statement;
	}

/**
 * Statements can be passed as argument for count()
 * to return the number for affected rows from last execution
 *
 * @return integer
 **/
	public function count() {
		return $this->rowCount();
	}

/**
 * Auxiliary function to convert values to database type
 * and return relevant internal statement type
 *
 * @param mixed value
 * @param string $type
 * @return array list containing converted value and internal type
 **/
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
