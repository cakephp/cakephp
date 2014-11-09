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
 * Returns an array converted into a formatted multiline string
 *
 * @param array $list
 * @param array $options
 * @return string
 */
	public function stringifyList($list, $options = []) {
		$options += [
			'keys' => false,
			'indent' => 2,
			'callback' => function ($v) {
				return "'$v'";
			},
		];

		if (!$list) {
			return '';
		}

		$wrapped = array_map($options['callback'], $list);

		if (!empty($option['keys'])) {
			foreach($wrapped as $k => &$v) {
				$v = "'$k' => $v";
			}
		}

		$start = $end = '';
		$join = ', ';
		if ($options['indent']) {
			$start = "\n" . str_repeat("\t", $options['indent']);
			$join .= $start;
			$end = "\n" . str_repeat("\t", $options['indent'] - 1);
		}

		return $start . implode($join, $wrapped) . $end;
	}

}
