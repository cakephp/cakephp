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
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Input;

use Cake\View\StringTemplate;
use Traversable;

/**
 * Input widget class for generating a selectbox.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class SelectBox {

/**
 * Template instance.
 *
 * @var Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Constructor
 *
 * @param Cake\View\StringTemplate $templates
 */
	public function __construct($templates) {
		$this->_templates = $templates;
	}

/**
 * Render a select box form input.
 *
 * Render a select box input given a set of data. Supported keys
 * are:
 *
 * - `name` - Set the input name.
 * - `options` - An array of options.
 * - `disabled` - Either true or an array of options to disable.
 *    When true, the select element will be disabled.
 * - `value` - Either a string or an array of options to mark as selected.
 * - `empty` - Set to true to add an empty option at the top of the
 *   option elements. Set to a string to define the display value of the
 *   empty option.
 * - `escape` - Set to false to disable HTML escaping.
 *
 * @param array $data Data to render with.
 * @return string A generated select box.
 * @throws \RuntimeException when the name attribute is empty.
 */
	public function render($data) {
		$data += [
			'name' => '',
			'empty' => false,
			'escape' => true,
			'options' => [],
			'disabled' => null,
			'value' => null,
		];

		if (empty($data['name'])) {
			throw new \RuntimeException('Cannot make inputs with empty name attributes.');
		}
		$options = $this->_renderContent($data);
		$name = $data['name'];
		unset($data['name'], $data['options'], $data['empty'], $data['value'], $data['escape']);
		if (isset($data['disabled']) && is_array($data['disabled'])) {
			unset($data['disabled']);
		}

		$attrs = $this->_templates->formatAttributes($data);
		return $this->_templates->format('select', [
			'name' => $name,
			'attrs' => $attrs,
			'content' => implode('', $options),
		]);
	}

/**
 * Render the contents of the select element.
 *
 * @param array $data The context for rendering a select.
 * @return array
 */
	protected function _renderContent($data) {
		$out = [];
		$options = $data['options'];

		if (!empty($data['empty'])) {
			$value = $data['empty'] === true ? '' : $data['empty'];
			$empty = ['' => $value];
			$options = $empty + $options;
		}
		if (empty($options)) {
			return $out;
		}

		$selected = isset($data['value']) ? $data['value'] : null;
		$disabled = null;
		if (isset($data['disabled']) && is_array($data['disabled'])) {
			$disabled = $data['disabled'];
		}
		return $this->_renderOptions($options, $disabled, $selected, $data['escape']);
	}

/**
 * Render a set of options.
 *
 * Will recursively call itself when option groups are in use.
 *
 * @param array $options The options to render.
 * @param array|null $disabled The options to disable.
 * @param array|string|null $selected The options to select.
 * @param boolean $escape Toggle HTML escaping.
 * @return array Option elements.
 */
	protected function _renderOptions($options, $disabled, $selected, $escape) {
		$out = [];
		foreach ($options as $key => $val) {
			if (is_array($val) || $val instanceof Traversable) {
				$groupOptions = $this->_renderOptions($val, $disabled, $selected, $escape);
				$out[] = $this->_templates->format('optgroup', [
					'label' => $escape ? h($key) : $key,
					'content' => implode('', $groupOptions)
				]);
			} else {
				$optAttrs = [];
				if ($this->_isSelected($key, $selected)) {
					$optAttrs['selected'] = true;
				}
				if ($this->_isDisabled($key, $disabled)) {
					$optAttrs['disabled'] = true;
				}
				$optAttrs['escape'] = $escape;

				$out[] = $this->_templates->format('option', [
					'name' => $escape ? h($key) : $key,
					'value' => $escape ? h($val) : $val,
					'attrs' => $this->_templates->formatAttributes($optAttrs),
				]);
			}
		}
		return $out;
	}

/**
 * Helper method for deciding what options are selected.
 *
 * @param string $key The key to test.
 * @param array|string|null The selected values.
 * @return boolean
 */
	protected function _isSelected($key, $selected) {
		if ($selected === null) {
			return false;
		}
		$isArray = is_array($selected);
		if (!$isArray) {
			return (string)$key === (string)$selected;
		}
		$strict = !is_numeric($key);
		return in_array((string)$key, $selected, $strict);
	}

/**
 * Helper method for deciding what options are disabled.
 *
 * @param string $key The key to test.
 * @param array|null The disabled values.
 * @return boolean
 */
	protected function _isDisabled($key, $disabled) {
		if ($disabled === null) {
			return false;
		}
		$strict = !is_numeric($key);
		return in_array((string)$key, $disabled, $strict);
	}

}
