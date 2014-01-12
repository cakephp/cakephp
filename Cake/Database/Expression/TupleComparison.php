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

use Cake\Database\Expression\Comparison;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * This expression represents SQL fragments that are use for comparing one tuple
 * to another, one tuple to a set of other tuples or one tuple to an expression
 */
class TupleComparison extends Comparison {

/**
 * Constructor
 *
 * @param string $fields the fields to use to form a tuple
 * @param array|ExpressionInterface $values the values to use to form a tuple
 * @param array $types the types names to use for casting each of the values, only
 * one type per position in the value array in needed
 * @param string $conjunction the operator used for comparing field and value
 * @return void
 */
	public function __construct($fields, $values, $types = [], $conjuntion = '=') {
		parent::__construct($fields, $value, $type, $conjuntion);
		$this->_type = (array)$type;
	}

/**
 * Convert the expression into a SQL fragment.
 *
 * @param Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		$template = '(%s) %s (%s)';
		$fields = [];

		foreach ((array)$this->getField() as $field) {
			$fields[] = $field instanceof ExpressionInterface ? $field->sql($generator) : $field;
		}

		$values = $this->_stringifyValues($generator);

		$field = implode(', ', $fields);
		return sprintf($template, $field, $this->_conjunction, $values);
	}

/**
 * Returns a string with the values as placeholders in a string to be used
 * for the SQL version of this expression
 *
 * @param \Cake\Database\ValueBiender $generator
 * @return string
 */
	protected function _stringifyValues($generator) {
		$values = [];
		$parts = $this->getValue();

		if ($parts instanceof ExpressionInterface) {
			return $parts->sql($generator);
		}

		foreach ($parts as $i => $value) {
			if ($value instanceof ExpressionInterface) {
				$values[] = $value->sql($generator);
				continue;
			}

			$type = isset($this->_type[$i]) ? $this->_type[$i] : null;
			if ($this->_isMulti($i, $type)) {
				$type = str_replace('[]', '', $type);
				$value = $this->_flattenValue($value, $generator, $type);
				$values[] = "($value)";
				continue;
			}

			$values[] = $this->_bindValue($generator, $value, $type);
		}

		return implode(', ', $values);
	}

/*
 * Traverses the tree of expressions stored in this object, visiting first
 * expressions in the left hand side and the the rest.
 *
 * Callback function receives as only argument an instance of a ExpresisonInterface
 *
 * @param callable $callable
 * @return void
 */
	public function traverse(callable $callable) {
		foreach ($this->getField() as $field) {
			$this->_traverseValue($field, $callable);
		}
		foreach ($this->getValue() as $i => $value) {
			$type = isset($this->_type[$i]) ? $this->_type[$i] : null;
			if ($this->_isMulti($type)) {
				foreach ($value as $v) {
					$this->_traverseValue($v, $callable);
				}
			} else {
				$this->_traverseValue($value, $callable);
			}
		}
	}

/**
 * Conditionally executes the callback for the passed value if
 * it is an ExpressionInterface
 *
 * @param mixed $value
 * @Param callable $callable
 * @return void
 */
	protected function _traverseValue($value, $callable) {
		if ($value instanceof ExpressionInterface) {
			$callable($value);
			$value->traverse($callable);
		}
	}

/**
 * Determines if each of the values in this expressions is a tuple in
 * itself
 *
 * @param string $type the type to bind for values
 * @return boolean
 */
	protected function _isMulti($type) {
		$multi = in_array(strtolower($this->_conjunction), ['in', 'not in']);
		return $multi || strpos($type, '[]') !== false;
	}

}
