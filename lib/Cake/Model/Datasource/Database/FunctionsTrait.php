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
 * @since         CakePHP(tm) v 3.0.0
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

	public function coalesce($args, $types = []) {
		return $this->func('COALESCE', $args, $types);
	}

	public function dateDiff($dates, $types = []) {
		return $this->func('DATEDIFF', $dates, $types);
	}

}
