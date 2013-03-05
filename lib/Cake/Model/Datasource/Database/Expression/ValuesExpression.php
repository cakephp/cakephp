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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 0.10.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Expression;

use Cake\Error;
use Cake\Model\Datasource\Database\Expression;
use Cake\Model\Datasource\Database\Query;
use \Countable;

/**
 * An expression object to contain values being inserted.
 *
 * Helps generate SQL with the correct number of placeholders and bind
 * values correctly into the statement.
 */
class ValuesExpression implements Expression {

	protected $_values = [];
	protected $_columns = [];
	protected $_hasQuery = false;

	public function __construct($columns) {
		$this->_columns = $columns;
	}

/**
 * Add a row of data to be inserted.
 *
 * @param array|Query $data Array of data to append into the insert, or
 *   a query for doing INSERT INTO .. SELECT style commands
 * @return void
 * @throws Cake\Error\Exception When mixing array + Query data types.
 */
	public function add($data) {
		if (
			count($this->_values) &&
			($data instanceof Query || ($this->_hasQuery && is_array($data)))
		) {
			throw new Error\Exception(
				__d('cake_dev', 'You cannot mix subqueries and array data in inserts.')
			);
		}
		if ($data instanceof Query) {
			$this->_hasQuery = true;
		}
		$this->_values[] = $data;
	}

/**
 * Convert the rows of data into a format that works with Query::_bindParams()
 *
 * @return array
 */
	public function bindings() {
		$bindings = [];
		$i = 0;
		$defaults = array_fill_keys($this->_columns, null);
		foreach ($this->_values as $row) {
			if (is_array($row)) {
				$row = array_merge($defaults, $row);
				foreach ($row as $column => $value) {
					$bindings[] = [
						// TODO add types.
						'type' => null,
						'placeholder' => $i,
						'value' => $value
					];
					$i++;
				}
			}
		}
		return $bindings;
	}

/**
 * Convert the values into a SQL string with placeholders.
 *
 * @return string
 */
	public function sql() {
		if (empty($this->_values)) {
			return '';
		}
		if ($this->_hasQuery) {
			return ' ' . $this->_values[0]->sql();
		}
		$placeholders = [];
		$numColumns = count($this->_columns);

		foreach ($this->_values as $row) {
			$placeholders[] = implode(', ', array_fill(0, $numColumns, '?'));
		}
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
		if (!$this->_hasQuery) {
			return;
		}
		$this->_values[0]->traverse($visitor);
	}

}
