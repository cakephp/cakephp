<?php
namespace Cake\View\Helper;

use Cake\Core\Configure;
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
			$join = ',';
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

/**
 * Returns details about the given class.
 *
 * The returned array holds the following keys:
 *
 * - `namespace` (the full namespace without leading separator)
 * - `class` (the class name)
 * - `plugin` (either the name of the plugin, or `null`)
 * - `name` (the name of the component without suffix)
 * - `fullName` (the full name of the class, including possible vendor and plugin name)
 *
 * @param string $class Class name
 * @param string $type Class type/sub-namespace
 * @param string $suffix Class name suffix
 * @return array Class info
 */
	public function classInfo($class, $type, $suffix) {
		list($plugin, $name) = \pluginSplit($class);

		$base = Configure::read('App.namespace');
		if ($plugin !== null) {
			$base = $plugin;
		}
		$base = str_replace('/', '\\', trim($base, '\\'));
		$sub = '\\' . str_replace('/', '\\', trim($type, '\\'));

		if (class_exists('\Cake' . $sub . '\\' . $name . $suffix)) {
			$base = 'Cake';
		}

		return [
			'namespace' => $base . $sub,
			'plugin' => $plugin,
			'class' => $name . $suffix,
			'name' => $name,
			'fullName' => $class
		];
	}

}
