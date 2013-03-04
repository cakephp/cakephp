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

/**
 * Add a row of data to be inserted.
 *
 * @param array $data Array of data to append into the insert.
 * @return void
 */
	public function add($data) {
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
		foreach ($this->_values as $row) {
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
		return $bindings;
	}

/**
 * Convert the values into a SQL string with placeholders.
 *
 * @return string
 */
	public function sql() {
		$placeholders = [];
		foreach ($this->_values as $row) {
			if (is_array($row)) {
				$placeholders[] = implode(', ', array_fill(0, count($row), '?'));
			}
		}
		return sprintf('(%s)', implode('), (', $placeholders));
	}

}
