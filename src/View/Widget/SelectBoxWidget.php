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
namespace Cake\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\Widget\WidgetInterface;
use Traversable;

/**
 * Input widget class for generating a selectbox.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class SelectBoxWidget implements WidgetInterface {

/**
 * Template instance.
 *
 * @var \Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Constructor
 *
 * @param \Cake\View\StringTemplate $templates Templates list.
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
 * - `val` - Either a string or an array of options to mark as selected.
 * - `empty` - Set to true to add an empty option at the top of the
 *   option elements. Set to a string to define the display value of the
 *   empty option.
 * - `escape` - Set to false to disable HTML escaping.
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
 * If you need to define option groups you can do those using nested arrays:
 *
 * {{{
 * 'options' => [
 *  'Mammals' => [
 *    'elk' => 'Elk',
 *    'beaver' => 'Beaver'
 *  ]
 * ]
 * }}}
 *
 * And finally, if you need to put attributes on your optgroup elements you
 * can do that with a more complex nested array form:
 *
 * {{{
 * 'options' => [
 *   [
 *     'text' => 'Mammals',
 *     'data-id' => 1,
 *     'options' => [
 *       'elk' => 'Elk',
 *       'beaver' => 'Beaver'
 *     ]
 *  ],
 * ]
 * }}}
 *
 * You are free to mix each of the forms in the same option set, and
 * nest complex types as required.
 *
 * @param array $data Data to render with.
 * @param \Cake\View\Form\ContextInterface $context The current form context.
 * @return string A generated select box.
 * @throws \RuntimeException when the name attribute is empty.
 */
	public function render(array $data, ContextInterface $context) {
		$data += [
			'name' => '',
			'empty' => false,
			'escape' => true,
			'options' => [],
			'disabled' => null,
			'val' => null,
		];

		if (empty($data['name'])) {
			throw new \RuntimeException('Cannot make inputs with empty name attributes.');
		}
		$options = $this->_renderContent($data);
		$name = $data['name'];
		unset($data['name'], $data['options'], $data['empty'], $data['val'], $data['escape']);
		if (isset($data['disabled']) && is_array($data['disabled'])) {
			unset($data['disabled']);
		}

		$template = 'select';
		if (!empty($data['multiple'])) {
			$template = 'selectMultiple';
			unset($data['multiple']);
		}
		$attrs = $this->_templates->formatAttributes($data);
		return $this->_templates->format($template, [
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
		$options = $data['options'];

		if ($options instanceof Traversable) {
			$options = iterator_to_array($options);
		}

		if (!empty($data['empty'])) {
			$value = $data['empty'] === true ? '' : $data['empty'];
			$options = ['' => $value] + (array)$options;
		}
		if (empty($options)) {
			return [];
		}

		$selected = isset($data['val']) ? $data['val'] : null;
		$disabled = null;
		if (isset($data['disabled']) && is_array($data['disabled'])) {
			$disabled = $data['disabled'];
		}
		return $this->_renderOptions($options, $disabled, $selected, $data['escape']);
	}

/**
 * Render the contents of an optgroup element.
 *
 * @param string $label The optgroup label text
 * @param array $optgroup The opt group data.
 * @param array|null $disabled The options to disable.
 * @param array|string|null $selected The options to select.
 * @param bool $escape Toggle HTML escaping
 * @return string Formatted template string
 */
	protected function _renderOptgroup($label, $optgroup, $disabled, $selected, $escape) {
		$opts = $optgroup;
		$attrs = [];
		if (isset($optgroup['options'], $optgroup['text'])) {
			$opts = $optgroup['options'];
			$label = $optgroup['text'];
			$attrs = $optgroup;
		}
		$groupOptions = $this->_renderOptions($opts, $disabled, $selected, $escape);

		return $this->_templates->format('optgroup', [
			'label' => $escape ? h($label) : $label,
			'content' => implode('', $groupOptions),
			'attrs' => $this->_templates->formatAttributes($attrs, ['text', 'options']),
		]);
	}

/**
 * Render a set of options.
 *
 * Will recursively call itself when option groups are in use.
 *
 * @param array $options The options to render.
 * @param array|null $disabled The options to disable.
 * @param array|string|null $selected The options to select.
 * @param bool $escape Toggle HTML escaping.
 * @return array Option elements.
 */
	protected function _renderOptions($options, $disabled, $selected, $escape) {
		$out = [];
		foreach ($options as $key => $val) {
			// Option groups
			$arrayVal = (is_array($val) || $val instanceof Traversable);
			if (
				(!is_int($key) && $arrayVal) ||
				(is_int($key) && $arrayVal && isset($val['options']))
			) {
				$out[] = $this->_renderOptgroup($key, $val, $disabled, $selected, $escape);
				continue;
			}

			// Basic options
			$optAttrs = [
				'value' => $key,
				'text' => $val,
			];
			if (is_array($val) && isset($optAttrs['text'], $optAttrs['value'])) {
				$optAttrs = $val;
			}
			if ($this->_isSelected($key, $selected)) {
				$optAttrs['selected'] = true;
			}
			if ($this->_isDisabled($key, $disabled)) {
				$optAttrs['disabled'] = true;
			}
			$optAttrs['escape'] = $escape;

			$out[] = $this->_templates->format('option', [
				'value' => $escape ? h($optAttrs['value']) : $optAttrs['value'],
				'text' => $escape ? h($optAttrs['text']) : $optAttrs['text'],
				'attrs' => $this->_templates->formatAttributes($optAttrs, ['text', 'value']),
			]);
		}
		return $out;
	}

/**
 * Helper method for deciding what options are selected.
 *
 * @param string $key The key to test.
 * @param array|string|null $selected The selected values.
 * @return bool
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
 * @param array|null $disabled The disabled values.
 * @return bool
 */
	protected function _isDisabled($key, $disabled) {
		if ($disabled === null) {
			return false;
		}
		$strict = !is_numeric($key);
		return in_array((string)$key, $disabled, $strict);
	}

/**
 * {@inheritDoc}
 */
	public function secureFields(array $data) {
		return [$data['name']];
	}

}
