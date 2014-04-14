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
namespace Cake\View;

use Cake\Configure\Engine\PhpConfig;
use Cake\Core\InstanceConfigTrait;
use Cake\Core\Plugin;
use Cake\Error;

/**
 * Provides an interface for registering and inserting
 * content into simple logic-less string templates.
 *
 * Used by several helpers to provide simple flexible templates
 * for generating HTML and other content.
 */
class StringTemplate {

	use InstanceConfigTrait {
		config as add;
		config as get;
	}

/**
 * List of attributes that can be made compact.
 *
 * @var array
 */
	protected $_compactAttributes = array(
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize',
		'autoplay', 'controls', 'loop', 'muted', 'required', 'novalidate', 'formnovalidate'
	);

/**
 * The default templates this instance holds.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'attribute' => '{{name}}="{{value}}"',
		'compactAttribute' => '{{name}}="{{value}}"',
	];

/**
 * Constructor.
 *
 * @param array $templates A set of templates to add.
 */
	public function __construct(array $config = []) {
		$this->config($config);
	}

/**
 * Load a config file containing templates.
 *
 * Template files should define a `$config` variable containing
 * all the templates to load. Loaded templates will be merged with existing
 * templates.
 *
 * @param string $file The file to load
 * @return void
 */
	public function load($file) {
		$loader = new PhpConfig(APP . 'Config/');
		$templates = $loader->read($file);
		$this->add($templates);
	}

/**
 * Remove the named template.
 *
 * @param string $name The template to remove.
 * @return void
 */
	public function remove($name) {
		$this->config($name, null);
	}

/**
 * Format a template string with $data
 *
 * @param string $name The template name.
 * @param array $data The data to insert.
 * @return string
 */
	public function format($name, array $data) {
		$template = $this->get($name);
		if ($template === null) {
			return '';
		}
		$replace = [];
		$keys = array_keys($data);
		foreach ($keys as $key) {
			$replace['{{' . $key . '}}'] = $data[$key];
		}
		return strtr($template, $replace);
	}

/**
 * Returns a space-delimited string with items of the $options array. If a key
 * of $options array happens to be one of those listed
 * in `StringTemplate::$_compactAttributes` and its value is one of:
 *
 * - '1' (string)
 * - 1 (integer)
 * - true (boolean)
 * - 'true' (string)
 *
 * Then the value will be reset to be identical with key's name.
 * If the value is not one of these 4, the parameter is not output.
 *
 * 'escape' is a special option in that it controls the conversion of
 * attributes to their html-entity encoded equivalents. Set to false to disable html-encoding.
 *
 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
 *
 * This method uses the 'attribute' and 'compactAttribute' templates. Each of
 * these templates uses the `name` and `value` variables. You can modify these
 * templates to change how attributes are formatted.
 *
 * @param array $options Array of options.
 * @param null|array $exclude Array of options to be excluded, the options here will not be part of the return.
 * @return string Composed attributes.
 */
	public function formatAttributes($options, $exclude = null) {
		$insertBefore = ' ';
		$options = (array)$options + ['escape' => true];

		if (!is_array($exclude)) {
			$exclude = [];
		}

		$exclude = ['escape' => true, 'idPrefix' => true] + array_flip($exclude);
		$escape = $options['escape'];
		$attributes = [];

		foreach ($options as $key => $value) {
			if (!isset($exclude[$key]) && $value !== false && $value !== null) {
				$attributes[] = $this->_formatAttribute($key, $value, $escape);
			}
		}
		$out = trim(implode(' ', $attributes));
		return $out ? $insertBefore . $out : '';
	}

/**
 * Formats an individual attribute, and returns the string value of the composed attribute.
 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
 *
 * @param string $key The name of the attribute to create
 * @param string|array $value The value of the attribute to create.
 * @param bool $escape Define if the value must be escaped
 * @return string The composed attribute.
 */
	protected function _formatAttribute($key, $value, $escape = true) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		if (is_numeric($key)) {
			return $this->format('compactAttribute', [
				'name' => $value,
				'value' => $value
			]);
		}
		$truthy = [1, '1', true, 'true', $key];
		$isMinimized = in_array($key, $this->_compactAttributes);
		if ($isMinimized && in_array($value, $truthy, true)) {
			return $this->format('compactAttribute', [
				'name' => $key,
				'value' => $key
			]);
		}
		if ($isMinimized) {
			return '';
		}
		return $this->format('attribute', [
			'name' => $key,
			'value' => $escape ? h($value) : $value
		]);
	}

}
