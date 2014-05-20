<?php
/**
 * Created by PhpStorm.
 * User: walther
 * Date: 2014/05/20
 * Time: 9:35 AM
 */

namespace Cake\Database\Statement;

use PDO;

class SqlServerStatement extends PDOStatement {

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
 *    `$statement->bindValue(1, 'a title');`
 *    `$statement->bindValue(2, 5, PDO::INT);`
 *    `$statement->bindValue('active', true, 'boolean');`
 *    `$statement->bindValue(5, new \DateTime(), 'date');`
 *
 * @param string|int $column name or param position to be bound
 * @param mixed      $value  The value to bind to variable in query
 * @param string|int $type   PDO type or name of configured Type class
 *
 * @return void
 */
	public function bindValue($column, $value, $type = 'string') {
		if ($type === null) {
			$type = 'string';
		}
		if (!ctype_digit($type)) {
			list($value, $type) = $this->cast($value, $type);
		}
		if ($type == PDO::PARAM_LOB) {
			$this->_statement->bindParam($column, $value, $type, 0, PDO::SQLSRV_ENCODING_BINARY);
		} else {
			$this->_statement->bindValue($column, $value, $type);
		}
	}
} 