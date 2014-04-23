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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * An expression object that represents an expression with only a single operand.
 */
class UnaryExpression extends QueryExpression {

/**
 * Converts the expression to its string representation
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 * @return string
 */
	public function sql(ValueBinder $generator) {
		foreach ($this->_conditions as $condition) {
			if ($condition instanceof ExpressionInterface) {
				$condition = $condition->sql($generator);
			}
			// We only use the first (and only) condition
			return $this->_conjunction . ' (' . $condition . ')';
		}
	}

}
