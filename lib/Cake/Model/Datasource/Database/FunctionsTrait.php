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
namespace Cake\Model\Datasource\Database;
use Cake\Model\Datasource\Database\Expression\FunctionExpression;

trait FunctionsTrait {

/**
 * Returns a new instance of a FunctionExpression. This is used for generating
 * arbitrary function calls in the final SQL string.
 *
 * @param string $name the name of the SQL function to constructed
 * @param array $params list of params to be passed to the function
 * @param array $types list of types for each function param
 * @return FunctionExpression
 */
	public function func($name, $params = [], $types = []) {
		return new FunctionExpression($name, $params, $types);
	}

	protected function _literalArgumentFunction($name, $expression, $types = []) {
		if (!is_string($expression)) {
			$expression = [$expression];
		} else {
			$expression = [$expression => 'literal'];
		}
		return $this->func($name, $expression, $types);
	}

	public function sum($expression, $types = []) {
		return $this->_literalArgumentFunction('SUM', $expression, $types);
	}

	public function avg($expression, $types = []) {
		return $this->_literalArgumentFunction('AVG', $expression, $types);
	}

	public function max($expression, $types = []) {
		return $this->_literalArgumentFunction('MAX', $expression, $types);
	}

	public function min($expression, $types = []) {
		return $this->_literalArgumentFunction('MIN', $expression, $types);
	}

	public function count($expression, $types = []) {
		return $this->_literalArgumentFunction('COUNT', $expression, $types);
	}

	public function concat($args, $types = []) {
		return $this->func('CONCAT', $args, $types);
	}

}
