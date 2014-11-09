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
			'indent' => 2
		];

		if (!$list) {
			return '';
		}

		foreach($list as $k => &$v) {
			$v = "'$v'";
			if (!is_numeric($k)) {
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

		return $start . implode($join, $list) . $end;
	}

	public function aliasExtractor($modelObj, $assoc) {
		$extractor = function ($val) {
			return $val->target()->alias();
		};

		return array_map($extractor, $modelObj->associations()->type($assoc));
	}
}
