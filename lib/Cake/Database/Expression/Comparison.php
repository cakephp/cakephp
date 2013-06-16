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

		$this->_identifier = substr(spl_object_hash($this), 7, 9);
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

	public function placeholder($token) {
		return sprintf(':c%s', $this->_identifier);
	}

	public function sql() {
		$value = $this->_value;
		$template = '%s %s %s';
		if (!($this->_value instanceof ExpressionInterface)) {
			$value = $this->_bindValue($this->_field, $value, $this->_type);
		}

		return sprintf($template, $this->_field, $this->_conjunction, $value);
	}

	public function count() {
		return 1;
	}

}
