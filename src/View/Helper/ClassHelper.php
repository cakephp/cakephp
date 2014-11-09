<?php
namespace Cake\View\Helper;

use Cake\Core\ConventionsTrait;
use Cake\View\Helper;
use Cake\Utility\Inflector;

/**
 * Class helper
 */
class ClassHelper extends Helper {

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
 * @param array $params
 * @return string
 */
	public function arrayProperty($name, $value, $params = []) {
		if (!$value) {
			return '';
		}

		foreach($value as &$val) {
			$val = Inflector::camelize($val);
		}
		$params += [
			'name' => $name,
			'value' => $value
		];
		return $this->_View->element('array_property', $params);
	}

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
