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

/**
 * Input widget class for generating a selectbox.
 */
class SelectBox {

/**
 * Minimized attributes
 *
 * @var array
 */
	protected $_minimizedAttributes = array(
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize',
		'autoplay', 'controls', 'loop', 'muted', 'required', 'novalidate', 'formnovalidate'
	);

/**
 * Format to attribute
 *
 * @var string
 */
	protected $_attributeFormat = '%s="%s"';

/**
 * Format to attribute
 *
 * @var string
 */
	protected $_minimizedAttributeFormat = '%s="%s"';

	protected $_templates;

	public function __construct($templates) {
		$this->_templates = $templates;
	}

	public function render($data) {
		if (empty($data['name'])) {
			throw new \RuntimeException('Cannot make inputs with empty name attributes.');
		}
		$options = $this->_renderContent($data);
		$name = $data['name'];
		unset($data['name'], $data['options'], $data['empty'], $data['value']);
		if (isset($data['disabled']) && is_array($data['disabled'])) {
			unset($data['disabled']);
		}

		$attrs = $this->_parseAttributes($data);
		return $this->_templates->format('select', [
			'name' => $name,
			'attrs' => $attrs,
			'content' => implode('', $options),
		]);
	}

	protected function _renderContent($data) {
		$out = [];
		if (!isset($data['options'])) {
			$data['options'] = [];
		}
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
		return $this->_renderOptions($options, $disabled, $selected);
	}

	protected function _renderOptions($options, $disabled, $selected) {
		foreach ($options as $key => $val) {
			if (is_array($val)) {
				$groupOptions = $this->_renderOptions($val, $disabled, $selected);
				$out[] = $this->_templates->format('optgroup', [
					'label' => $key,
					'content' => implode('', $groupOptions)
				]);
			} else {
				$template = 'option';
				$isSelected = $this->_isSelected($key, $selected);
				$isDisabled = $this->_isDisabled($key, $disabled);
				if ($isSelected) {
					$template .= 'Selected';
				}
				if ($isDisabled) {
					$template .= 'Disabled';
				}

				$out[] = $this->_templates->format($template, [
					'name' => $key,
					'value' => $val
				]);
			}
		}
		return $out;
	}

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

	protected function _isDisabled($key, $disabled) {
		if ($disabled === null) {
			return false;
		}
		$strict = !is_numeric($key);
		return in_array((string)$key, $disabled, $strict);
	}

	protected function _parseAttributes($options, $exclude = null) {
		$insertBefore = ' ';
		$options = (array)$options + array('escape' => true);

		if (!is_array($exclude)) {
			$exclude = array();
		}

		$exclude = array('escape' => true) + array_flip($exclude);
		$escape = $options['escape'];
		$attributes = array();

		foreach ($options as $key => $value) {
			if (!isset($exclude[$key]) && $value !== false && $value !== null) {
				$attributes[] = $this->_formatAttribute($key, $value, $escape);
			}
		}
		$out = implode(' ', $attributes);
		return $out ? $insertBefore . $out : '';
	}

/**
 * Formats an individual attribute, and returns the string value of the composed attribute.
 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
 *
 * TODO MOVE TO StringTemplate class?
 *
 * @param string $key The name of the attribute to create
 * @param string $value The value of the attribute to create.
 * @param boolean $escape Define if the value must be escaped
 * @return string The composed attribute.
 * @deprecated This method will be moved to HtmlHelper in 3.0
 */
	protected function _formatAttribute($key, $value, $escape = true) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		if (is_numeric($key)) {
			return sprintf($this->_minimizedAttributeFormat, $value, $value);
		}
		$truthy = array(1, '1', true, 'true', $key);
		$isMinimized = in_array($key, $this->_minimizedAttributes);
		if ($isMinimized && in_array($value, $truthy, true)) {
			return sprintf($this->_minimizedAttributeFormat, $key, $key);
		}
		if ($isMinimized) {
			return '';
		}
		return sprintf($this->_attributeFormat, $key, ($escape ? h($value) : $value));
	}

}
