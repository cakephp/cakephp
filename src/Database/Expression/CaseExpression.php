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
use Cake\Database\Expression\QueryExpression;
use Cake\Database\ValueBinder;

/**
 * This class represents a SQL Case statement
 *
 * @internal
 */
class CaseExpression implements ExpressionInterface {

	protected $_expression;

	protected $_isTrue;

	protected $_isFalse;

/**
 * Constructs the case expression
 *
 * @param QueryExpression $expression The expression to test
 * @param mixed           $isTrue Value if the expression is true
 * @param mixed           $isFalse Value if the expression is false
 */
	public function __construct(QueryExpression $expression, $isTrue = 1, $isFalse = 0) {
		$this->_expression = $expression;
		$this->_isTrue = $this->_getValue($isTrue);
		$this->_isFalse = $this->_getValue($isFalse);
	}

/**
 * Gets/sets the isTrue part
 *
 * @param mixed $value Value to set
 *
 * @return array|mixed
 */
	public function isTrue($value = null) {
		return $this->_part('isTrue', $value);
	}

/**
 * Gets/sets the isFalse part
 *
 * @param mixed $value Value to set
 *
 * @return array|mixed
 * @codeCoverageIgnore
 */
	public function isFalse($value = null) {
		return $this->_part('isFalse', $value);
	}

/**
 * Gets/sets the passed part
 *
 * @param string $part The part to get or set
 * @param mixed $value Value to set
 *
 * @return array|mixed
 */
	protected function _part($part, $value) {
		if ($value !== null) {
			$this->{'_' . $part} = $this->_getValue($value);
		}

		return $this->{'_' . $part};
	}

/**
 * Parses the value into a understandable format
 *
 * @param mixed $value The value to parse
 *
 * @return array|mixed
 */
	protected function _getValue($value) {
		if (is_string($value)) {
			$value = [
				'value' => $value,
				'type' => null
			];
		} elseif (is_array($value) && !isset($value['value'])) {
			$value = array_keys($value);
			$value = end($value);
		}
		return $value;
	}

/**
 * Compiles the true or false part into sql
 *
 * @param mixed       $part The part to compile
 * @param ValueBinder $generator Sql generator
 *
 * @return string
 */
	protected function _compile($part, ValueBinder $generator) {
		$part = $this->{'_' . $part};
		if ($part instanceof ExpressionInterface) {
			$part = $part->sql($generator);
		} elseif (is_array($part)) {
			$placeholder = $generator->placeholder('param');
			$generator->bind($placeholder, $part['value'], $part['type']);
			$part = $placeholder;
		}

		return $part;
	}

/**
 * Converts the Node into a SQL string fragment.
 *
 * @param \Cake\Database\ValueBinder $generator Placeholder generator object
 *
 * @return string
 */
	public function sql(ValueBinder $generator) {
		$parts = [];
		$parts[] = 'CASE WHEN';
		$parts[] = $this->_expression->sql($generator);
		$parts[] = 'THEN';
		$parts[] = $this->_compile('isTrue', $generator);
		$parts[] = 'ELSE';
		$parts[] = $this->_compile('isFalse', $generator);
		$parts[] = 'END';

		return implode(' ', $parts);
	}

/**
 * {@inheritDoc}
 *
 */
	public function traverse(callable $visitor) {
		foreach (['_expression', '_isTrue', '_isFalse'] as $c) {
			if ($this->{$c} instanceof ExpressionInterface) {
				$visitor($this->{$c});
				$this->{$c}->traverse($visitor);
			}
		}
	}

}