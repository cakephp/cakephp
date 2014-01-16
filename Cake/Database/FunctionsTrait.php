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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database;

use Cake\Database\Expression\FunctionExpression;

/**
 * Contains methods related to generating FunctionExpression objects
 * with most commonly used SQL functions.
 * This trait is just a factory for FunctionExpression objects.
 */
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

/**
 * Helper function to build a function expression that only takes one literal
 * argument.
 *
 * @param string $name name of the function to build
 * @param mixed $expression the function argument
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	protected function _literalArgumentFunction($name, $expression, $types = []) {
		if (!is_string($expression)) {
			$expression = [$expression];
		} else {
			$expression = [$expression => 'literal'];
		}
		return $this->func($name, $expression, $types);
	}

/**
 * Returns a FunctionExpression representing a call to SQL SUM function.
 *
 * @param mixed $expression the function argument
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function sum($expression, $types = []) {
		return $this->_literalArgumentFunction('SUM', $expression, $types);
	}

/**
 * Returns a FunctionExpression representing a call to SQL AVG function.
 *
 * @param mixed $expression the function argument
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function avg($expression, $types = []) {
		return $this->_literalArgumentFunction('AVG', $expression, $types);
	}

/**
 * Returns a FunctionExpression representing a call to SQL MAX function.
 *
 * @param mixed $expression the function argument
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function max($expression, $types = []) {
		return $this->_literalArgumentFunction('MAX', $expression, $types);
	}

/**
 * Returns a FunctionExpression representing a call to SQL MIN function.
 *
 * @param mixed $expression the function argument
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function min($expression, $types = []) {
		return $this->_literalArgumentFunction('MIN', $expression, $types);
	}

/**
 * Returns a FunctionExpression representing a call to SQL COUNT function.
 *
 * @param mixed $expression the function argument
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function count($expression, $types = []) {
		return $this->_literalArgumentFunction('COUNT', $expression, $types);
	}

/**
 * Returns a FunctionExpression representing a string concatenation
 *
 * @param array $args List of strings or expressions to concatenate
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function concat($args, $types = []) {
		return $this->func('CONCAT', $args, $types);
	}

/**
 * Returns a FunctionExpression representing a call to SQL COALESCE function.
 *
 * @param array $args List of expressions to evaluate as function parameters
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function coalesce($args, $types = []) {
		return $this->func('COALESCE', $args, $types);
	}

/**
 * Returns a FunctionExpression representing the difference in days between
 * two dates.
 *
 * @param array $args List of expressions to obtain the difference in days.
 * @param array $types list of types to bind to the arguments
 * @return FunctionExpression
 */
	public function dateDiff($dates, $types = []) {
		return $this->func('DATEDIFF', $dates, $types);
	}

/**
 * Returns a FunctionExpression representing a call that will return the current
 * date and time. By default it returns both date and time, but you can also
 * make it generate only the date or only the time.
 *
 * @param string $type (datetime|date|time)
 * @return FunctionExpression
 */
	public function now($type = 'datetime') {
		if ($type === 'datetime') {
			return $this->func('NOW');
		}
		if ($type === 'date') {
			return $this->func('CURRENT_DATE');
		}
		if ($type === 'time') {
			return $this->func('CURRENT_TIME');
		}
	}

}
