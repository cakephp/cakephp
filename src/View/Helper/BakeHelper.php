<?php
namespace Cake\View\Helper;

use Cake\Core\ConventionsTrait;
use Cake\View\Helper;
use Cake\Utility\Inflector;

/**
 * Bake helper
 */
class BakeHelper extends Helper {

	use ConventionsTrait;

/**
 * Default configuration.
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * arrayProperty
 *
 * Used for generating formatted properties such as component and helper arrays
 *
 * @param string $name
 * @param array $value
 * @param array $options
 * @return string
 */
	public function arrayProperty($name, $value, $options = []) {
		if (!$value) {
			return '';
		}

		foreach($value as &$val) {
			$val = Inflector::camelize($val);
		}
		$options += [
			'name' => $name,
			'value' => $value
		];
		return $this->_View->element('array_property', $options);
	}

/**
 * stringifyList
 *
 * @param array $list
 * @param array $options
 * @return string
 */
	public function stringifyList($list, $options = []) {
		$options += [
			'indent' => 2,
			'callback' => function ($v) {
				return "'$v'";
			},
		];

		if (!$list) {
			return '';
		}

		$wrapped = array_map($options['callback'], $list);
		$return = implode("\n" . str_repeat("\t", $indent) . ', ', $wrapped) . "\n";
	}

}
