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

	protected $_templates;

	public function __construct($templates) {
		$this->_templates = $templates;
	}

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

	protected function _renderOptions($options, $disabled, $selected, $escape) {
		foreach ($options as $key => $val) {
			if (is_array($val)) {
				$groupOptions = $this->_renderOptions($val, $disabled, $selected, $escape);
				$out[] = $this->_templates->format('optgroup', [
					'label' => $escape ? h($key) : $key,
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
					'name' => $escape ? h($key) : $key,
					'value' => $escape ? h($val) : $val,
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

}
