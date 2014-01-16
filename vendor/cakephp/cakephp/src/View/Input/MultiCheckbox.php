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

use Cake\Utility\Inflector;

/**
 * Input widget class for generating multiple checkboxes.
 *
 */
class MultiCheckbox {

/**
 * Template instance to use.
 *
 * @var Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Render multi-checkbox widget.
 *
 * @param Cake\View\StringTemplate $templates
 */
	public function __construct($templates) {
		$this->_templates = $templates;
	}

/**
 * Render multi-checkbox widget.
 *
 * Data supports the following options.
 *
 * - `name` The name attribute of the inputs to create.
 *   `[]` will be appended to the name.
 * - `options` An array of options to create checkboxes out of.
 * - `val` Either a string/integer or array of values that should be
 *   checked. Can also be a complex options set.
 * - `disabled` Either a boolean or an array of checkboxes to disable.
 * - `escape` Set to false to disable HTML escaping.
 * - `options` An associative array of value=>labels to generate options for.
 *
 * ### Options format
 *
 * The options option can take a variety of data format depending on
 * the complexity of HTML you want generated.
 *
 * You can generate simple options using a basic associative array:
 *
 * {{{
 * 'options' => ['elk' => 'Elk', 'beaver' => 'Beaver']
 * }}}
 *
 * If you need to define additional attributes on your option elements
 * you can use the complex form for options:
 *
 * {{{
 * 'options' => [
 *   ['value' => 'elk', 'text' => 'Elk', 'data-foo' => 'bar'],
 * ]
 * }}}
 *
 * This form **requires** that both the `value` and `text` keys be defined.
 * If either is not set options will not be generated correctly.
 *
 * @param array $data
 * @return string
 */
	public function render($data) {
		$data += [
			'name' => '',
			'escape' => true,
			'options' => [],
			'disabled' => null,
			'val' => null,
		];
		$out = [];
		foreach ($data['options'] as $key => $val) {
			$checkbox = [
				'value' => $key,
				'text' => $val,
			];
			if (is_array($val) && isset($val['text'], $val['value'])) {
				$checkbox = $val;
			}
			$checkbox['name'] = $data['name'];
			$checkbox['escape'] = $data['escape'];

			if ($this->_isSelected($key, $data['val'])) {
				$checkbox['checked'] = true;
			}
			if ($this->_isDisabled($key, $data['disabled'])) {
				$checkbox['disabled'] = true;
			}
			if (empty($checkbox['id'])) {
				$checkbox['id'] = mb_strtolower(Inflector::slug($checkbox['name'] . $checkbox['value'], '-'));
			}

			$out[] = $this->_renderInput($checkbox);
		}
		return implode('', $out);
	}

/**
 * Render a single checkbox & wrapper.
 *
 * @return string
 */
	protected function _renderInput($checkbox) {
		$input = $this->_templates->format('checkbox', [
			'name' => $checkbox['name'] . '[]',
			'value' => $checkbox['escape'] ? h($checkbox['value']) : $checkbox['value'],
			'attrs' => $this->_templates->formatAttributes(
				$checkbox,
				['name', 'value', 'text']
			)
		]);

		$labelAttrs = [
			'for' => $checkbox['id'],
			'escape' => $checkbox['escape']
		];
		$label = $this->_templates->format('label', [
			'text' => $checkbox['escape'] ? h($checkbox['text']) : $checkbox['text'],
			'input' => $input,
			'attrs' => $this->_templates->formatAttributes($labelAttrs)
		]);

		return $this->_templates->format('checkboxContainer', [
			'label' => $label,
			'input' => $input
		]);
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
		if ($disabled === null || $disabled === false) {
			return false;
		}
		if ($disabled === true) {
			return true;
		}
		$strict = !is_numeric($key);
		return in_array((string)$key, $disabled, $strict);
	}

}
