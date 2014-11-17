<?php
namespace Cake\View\Helper;

use Cake\Core\ConventionsTrait;
use Cake\Utility\Inflector;
use Cake\View\Helper;

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
 * Used for generating formatted properties such as component and helper arrays
 *
 * @param string $name the name of the property
 * @param array $value the array of values
 * @param array $options extra options to be passed ot the element
 * @return string
 */
	public function arrayProperty($name, array $value = [], array $options = []) {
		if (!$value) {
			return '';
		}

		foreach ($value as &$val) {
			$val = Inflector::camelize($val);
		}
		$options += [
			'name' => $name,
			'value' => $value
		];
		return $this->_View->element('array_property', $options);
	}

/**
 * Returns an array converted into a formatted multiline string
 *
 * @param array $list array of items to be stringified
 * @param array $options options to use
 * @return string
 */
	public function stringifyList(array $list, array $options = []) {
		$options += [
			'indent' => 2
		];

		if (!$list) {
			return '';
		}

		foreach ($list as $k => &$v) {
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

/**
 * Extract the aliases for associations
 *
 * @param \Cake\ORM\Table $table object to find associations on
 * @param string $assoc association to extract
 * @return array
 */
	public function aliasExtractor($table, $assoc) {
		$extractor = function ($val) {
			return $val->target()->alias();
		};

		return array_map($extractor, $table->associations()->type($assoc));
	}

}
