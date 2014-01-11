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
 *
 */
class TupleComparison extends Comparison {

/**
 * Constructor
 *
 * @param string $field the field name to compare to a value
 * @param mixed $value the value to be used in comparison
 * @param string $type the type name used to cast the value
 * @param string $conjunction the operator used for comparing field and value
 * @return void
 */
	public function __construct($field, $value, $type = [], $conjuntion = '=') {
		parent::__construct($field, $value, $type, $conjuntion);
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

		$values = $this->_extractValues($generator);

		$field = implode(', ', $fields);
		return sprintf($template, $field, $this->_conjunction, $values);
	}

	protected function _extractValues($generator) {
		$values = [];
		foreach ($this->getValue() as $i => $value) {
			if ($value instanceof ExpressionInterface) {
				$values[] = $value->sql($generator);
				continue;
			}

			$multi = in_array(strtolower($this->_conjunction), ['in', 'not in']);
			$type = isset($this->_type[$i]) ? $this->_type[$i] : null;

			if ($multi || strpos($type, '[]') !== false) {
				$type = str_replace('[]', '', $type);
				$value = $this->_flattenValue($value, $generator, $type);
				$values[] = "($value)";
				continue;
			}

			$values[] = $this->_bindValue($generator, $value, $type);
		}

		return implode(', ', $values);
	}

}
