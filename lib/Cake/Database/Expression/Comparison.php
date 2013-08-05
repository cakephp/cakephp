<?php
/**
 *
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

class Comparison extends QueryExpression {

	protected $_field;

	protected $_value;

	protected $_type;

	public function __construct($field, $value, $type, $conjuntion) {
		$this->_field = $field;
		$this->_value = $value;
		$this->type($conjuntion);

		if (is_string($type)) {
			$this->_type = $type;
		}
		if (is_string($field) && isset($types[$this->_field])) {
			$this->_type = current($types);
		}

		$this->_conditions[$field] = $value;
	}

	public function field($field) {
		$this->_field = $field;
	}

	public function value($value) {
		$this->_value = $value;
	}

	public function getField() {
		return $this->_field;
	}

	public function getValue() {
		return $this->_value;
	}

/**
 * Convert the expression into a SQL fragment.
 *
 * @param Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		$value = $this->_value;
		$template = '%s %s (%s)';

		if (!($this->_value instanceof ExpressionInterface)) {
			$type = $this->_type;
			if (strpos($this->_type, '[]') !== false) {
				$value = $this->_flattenValue($generator);
			} else {
				$template = '%s %s %s';
				$value = $this->_bindValue($generator, $value, $this->_type);
			}
		} else {
			$value = $value->sql($generator);
		}

		return sprintf($template, $this->_field, $this->_conjunction, $value);
	}

	protected function _bindValue($generator, $value, $type) {
		$placeholder = $generator->placeholder($this->_field);
		$generator->bind($placeholder, $value, $type);
		return $placeholder;
	}

	protected function _flattenValue($generator) {
		$parts = [];
		$type = str_replace('[]', '', $this->_type);
		foreach ($this->_value as $value) {
			$parts[] = $this->_bindValue($generator, $value, $type);
		}
		return implode(',', $parts);
	}

	public function count() {
		return 1;
	}

}
