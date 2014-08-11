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

/**
 * A list of strings or other expression objects that represent the conditions of
 * the case statement. For example one key of the array might look like "sum > :value"
 *
 * @var array
 */
	protected $_conditions = [];

/**
 * Values that are associated with the conditions in the $_conditions array.
 * Each value represents the 'true' value for the condition with the corresponding key
 *
 * @var array
 */
	protected $_trueValues = [];

/**
 * The value to be used if none of the conditions match
 *
 * @var array|QueryExpression|string
 */
	protected $_defaultValue;

/**
 * Constructs the case expression
 *
 * @param array|QueryExpression $conditions The conditions to test.
 *                                          Must be a QueryExpression, or an array of QueryExpressions.
 * @param string|array|QueryExpression $trueValues Value of each condition if that condition is true
 * @param string|array|QueryExpression $defaultValue Default value if none of the conditiosn are true
 */
	public function __construct($conditions = [], $trueValues = [], $defaultValue = 0) {
		if (!empty($conditions)) {
			$this->add($conditions, $trueValues);
		}

		$this->_defaultValue = $this->_parseValue($defaultValue);
	}

/**
 * Adds one or more conditions and their respective true values to the case object.
 * Conditions must be a one dimensional array or a QueryExpression.
 * The trueValues must be a similar structure, but may contain a string value.
 *
 * @param array|QueryExpression $conditions Must be a QueryExpression, or an array of QueryExpressions.
 * @param string|array|QueryExpression $trueValues Values of each condition if that condition is true
 *
 * @return $this
 */
	public function add($conditions = [], $trueValues = []) {
		if (!is_array($conditions)) {
			$conditions = [$conditions];
		}
		if (!is_array($trueValues)) {
			$trueValues = [$trueValues];
		}

		$this->_addExpressions($conditions, $trueValues);

		return $this;
	}

/**
 * Iterates over the passed in conditions and ensures that there is a matching true value for each.
 * If no matching true value, then it is defaulted to '1'.
 *
 * @param array|QueryExpression $conditions Must be a QueryExpression, or an array of QueryExpressions.
 * @param string|array|QueryExpression $trueValues Values of each condition if that condition is true
 *
 * @return void
 */
	protected function _addExpressions($conditions, $trueValues) {
		foreach ($conditions as $k => $c) {
			$numericKey = is_numeric($k);

			if ($numericKey && empty($c)) {
				continue;
			}

			if (!$c instanceof QueryExpression) {
				continue;
			}

			$trueValue = isset($trueValues[$k]) ? $trueValues[$k] : 1;

			if ($trueValue === 'literal') {
				$trueValue = $k;
			} elseif (is_string($trueValue)) {
				$trueValue = [
					'value' => $trueValue,
					'type' => null
				];
			} elseif (empty($trueValue)) {
				$trueValue = 1;
			}

			$this->_conditions[] = $c;
			$this->_trueValues[] = $trueValue;
		}
	}

/**
 * Gets/sets the default value part
 *
 * @param array|string|QueryExpression $value Value to set
 *
 * @return array|string|QueryExpression
 */
	public function defaultValue($value = null) {
		if ($value !== null) {
			$this->_defaultValue = $this->_parseValue($value);
		}

		return $this->_defaultValue;
	}

/**
 * Parses the value into a understandable format
 *
 * @param array|string|QueryExpression $value The value to parse
 *
 * @return array|string|QueryExpression
 */
	protected function _parseValue($value) {
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
 * Compiles the relevant parts into sql
 *
 * @param array|string|QueryExpression $part The part to compile
 * @param ValueBinder $generator Sql generator
 *
 * @return string
 */
	protected function _compile($part, ValueBinder $generator) {
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
		$parts[] = 'CASE';
		foreach ($this->_conditions as $k => $part) {
			$trueValue = $this->_trueValues[$k];
			$parts[] = 'WHEN ' . $this->_compile($part, $generator) . ' THEN ' . $this->_compile($trueValue, $generator);
		}
		$parts[] = 'ELSE';
		$parts[] = $this->_compile($this->_defaultValue, $generator);
		$parts[] = 'END';

		return implode(' ', $parts);
	}

/**
 * {@inheritDoc}
 *
 */
	public function traverse(callable $visitor) {
		foreach (['_conditions', '_trueValues'] as $part) {
			foreach ($this->{$part} as $c) {
				if ($c instanceof ExpressionInterface) {
					$visitor($c);
					$c->traverse($visitor);
				}
			}
		}
		if ($this->_defaultValue instanceof ExpressionInterface) {
			$visitor($this->_defaultValue);
			$this->_defaultValue->traverse($visitor);
		}
	}

}