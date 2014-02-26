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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Cake\Error;
use \Countable;

/**
 * An expression object to contain values being inserted.
 *
 * Helps generate SQL with the correct number of placeholders and bind
 * values correctly into the statement.
 */
class ValuesExpression implements ExpressionInterface {

/**
 * Array of values to insert.
 *
 * @var array
 */
	protected $_values = [];

/**
 * List of columns to ensure are part of the insert.
 *
 * @var array
 */
	protected $_columns = [];

/**
 * List of column types.
 *
 * @var array
 */
	protected $_types = [];

/**
 * The Query object to use as a values expression
 *
 * @var \Cake\Database\Query
 */
	protected $_query = false;

/**
 * Constructor
 *
 * @param array $columns The list of columns that are going to be part of the values.
 * @param array $types A dictionary of column -> type names
 */
	public function __construct(array $columns, array $types = []) {
		$this->_columns = $columns;
		$this->_types = $types;
	}

/**
 * Add a row of data to be inserted.
 *
 * @param array|Query $data Array of data to append into the insert, or
 *   a query for doing INSERT INTO .. SELECT style commands
 * @return void
 * @throws \Cake\Error\Exception When mixing array + Query data types.
 */
	public function add($data) {
		if (
			(count($this->_values) && $data instanceof Query) ||
			($this->_query && is_array($data))
		) {
			throw new Error\Exception(
				'You cannot mix subqueries and array data in inserts.'
			);
		}
		if ($data instanceof Query) {
			$this->query($data);
			return;
		}
		$this->_values[] = $data;
	}

/**
 * Sets the columns to be inserted. If no params are passed, then it returns
 * the currently stored columns
 *
 * @param array $cols arrays with columns to be inserted
 * @return array|ValuesExpression
 */
	public function columns($cols = null) {
		if ($cols === null) {
			return $this->_columns;
		}
		$this->_columns = $cols;
		return $this;
	}

/**
 * Sets the values to be inserted. If no params are passed, then it returns
 * the currently stored values
 *
 * @param array $values arrays with values to be inserted
 * @return array|ValuesExpression
 */
	public function values($values = null) {
		if ($values === null) {
			return $this->_values;
		}
		$this->_values = $values;
		return $this;
	}

/**
 * Sets the query object to be used as the values expression to be evaluated
 * to insert records in the table. If no params are passed, then it returns
 * the currently stored query
 *
 * @param \Cake\Database\Query $query
 * @return \Cake\Database\Query
 */
	public function query(Query $query = null) {
		if ($query === null) {
			return $this->_query;
		}
		$this->_query = $query;
	}

/**
 * Convert the values into a SQL string with placeholders.
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		if (empty($this->_values) && empty($this->_query)) {
			return '';
		}

		$i = 0;
		$defaults = array_fill_keys($this->_columns, null);
		foreach ($this->_values as $row) {
			$row = array_merge($defaults, $row);
			foreach ($row as $column => $value) {
				$type = isset($this->_types[$column]) ? $this->_types[$column] : null;
				$generator->bind($i++, $value, $type);
			}
		}

		if ($this->query()) {
			return ' ' . $this->query()->sql($generator);
		}

		$placeholders = [];
		$numColumns = count($this->_columns);
		$rowPlaceholders = implode(', ', array_fill(0, $numColumns, '?'));
		$placeholders = array_fill(0, count($this->_values), $rowPlaceholders);
		return sprintf(' VALUES (%s)', implode('), (', $placeholders));
	}

/**
 * Traverse the values expression.
 *
 * This method will also traverse any queries that are to be used in the INSERT
 * values.
 *
 * @param callable $visitor The visitor to traverse the expression with.
 * @return void
 */
	public function traverse(callable $visitor) {
		if ($this->_query) {
			return;
		}

		foreach ($this->_values as $v) {
			if ($v instanceof ExpressionInterface) {
				$v->traverse($visitor);
			}
		}
	}

}
