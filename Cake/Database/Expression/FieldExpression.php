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
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

class FieldExpression implements ExpressionInterface {

	protected $_field;

	public function __construct($field) {
		$this->field($field);
	}

	public function field($field) {
		$this->_field = $field;
	}

	public function getField() {
		return $this->_field;
	}

/**
 * Converts the expression to its string representation
 *
 * @param Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		return $this->_field;
	}

/**
 * This method is a no-op, this is a leaf type of expression,
 * hence there is nothing to traverse
 *
 * @param callable $visitor
 * @return void
 */
	public function traverse(callable $callable) {
	}

}
