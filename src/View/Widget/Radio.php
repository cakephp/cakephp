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

use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\Widget\WidgetInterface;
use Traversable;

/**
 * Input widget class for generating a set of radio buttons.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class Radio implements WidgetInterface {

	use IdGeneratorTrait;

/**
 * Template instance.
 *
 * @var \Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Label instance.
 *
 * @var \Cake\View\Widget\Label
 */
	protected $_label;

/**
 * Constructor
 *
 * This class uses a few templates:
 *
 * - `radio` Used to generate the input for a radio button.
 *   Can use the following variables `name`, `value`, `attrs`.
 * - `radioContainer` Used to generate the container element for
 *   the radio + input element. Can use the `input` and `label`
 *   variables.
 *
 * @param \Cake\View\StringTemplate $templates
 * @param \Cake\View\Widget\Label $label
 */
	public function __construct($templates, $label) {
		$this->_templates = $templates;
		$this->_label = $label;
	}

/**
 * Render a set of radio buttons.
 *
 * Data supports the following keys:
 *
 * - `name` - Set the input name.
 * - `options` - An array of options. See below for more information.
 * - `disabled` - Either true or an array of inputs to disable.
 *    When true, the select element will be disabled.
 * - `val` - A string of the option to mark as selected.
 * - `label` - Either false to disable label generation, or
 *   an array of attributes for all labels.
 * - `required` - Set to true to add the required attribute
 *   on all generated radios.
 * - `idPrefix` Prefix for generated ID attributes.
 *
 * @param array $data The data to build radio buttons with.
 * @return string
 */
	public function render(array $data) {
		$data += [
			'name' => '',
			'options' => [],
			'disabled' => null,
			'val' => null,
			'escape' => true,
			'label' => true,
			'empty' => false,
			'idPrefix' => null
		];
		if ($data['options'] instanceof Traversable) {
			$options = iterator_to_array($data['options']);
		} else {
			$options = (array)$data['options'];
		}

		if (!empty($data['empty'])) {
			$empty = $data['empty'] === true ? 'empty' : $data['empty'];
			$options = ['' => $empty] + $options;
		}
		unset($data['empty']);

		$this->_idPrefix = $data['idPrefix'];
		$this->_clearIds();
		$opts = [];
		foreach ($options as $val => $text) {
			$opts[] = $this->_renderInput($val, $text, $data);
		}
		return implode('', $opts);
	}

/**
 * Disabled attribute detection.
 *
 * @param array $radio
 * @param array|null|true $disabled
 * @return bool
 */
	protected function _isDisabled($radio, $disabled) {
		if (!$disabled) {
			return false;
		}
		if ($disabled === true) {
			return true;
		}
		$isNumeric = is_numeric($radio['value']);
		return (!is_array($disabled) || in_array((string)$radio['value'], $disabled, !$isNumeric));
	}

/**
 * Renders a single radio input and label.
 *
 * @param string|int $val The value of the radio input.
 * @param string|array $text The label text, or complex radio type.
 * @param array $data Additional options for input generation.
 * @return string
 */
	protected function _renderInput($val, $text, $data) {
		$escape = $data['escape'];
		if (is_int($val) && isset($text['text'], $text['value'])) {
			$radio = $text;
			$text = $radio['text'];
		} else {
			$radio = ['value' => $val, 'text' => $text];
		}
		$radio['name'] = $data['name'];

		if (empty($radio['id'])) {
			$radio['id'] = $this->_id($radio['name'], $radio['value']);
		}

		if (isset($data['val']) && is_bool($data['val'])) {
			$data['val'] = $data['val'] ? 1 : 0;
		}

		if (isset($data['val']) && strval($data['val']) === strval($radio['value'])) {
			$radio['checked'] = true;
		}

		if ($this->_isDisabled($radio, $data['disabled'])) {
			$radio['disabled'] = true;
		}
		if (!empty($data['required'])) {
			$radio['required'] = true;
		}
		if (!empty($data['form'])) {
			$radio['form'] = $data['form'];
		}

		$input = $this->_templates->format('radio', [
			'name' => $radio['name'],
			'value' => $escape ? h($radio['value']) : $radio['value'],
			'attrs' => $this->_templates->formatAttributes($radio, ['name', 'value', 'text']),
		]);

		$label = $this->_renderLabel(
			$radio,
			$data['label'],
			$input,
			$escape
		);

		return $this->_templates->format('radioContainer', [
			'input' => $input,
			'label' => $label,
		]);
	}

/**
 * Renders a label element for a given radio button.
 *
 * In the future this might be refactored into a separate widget as other
 * input types (multi-checkboxes) will also need labels generated.
 *
 * @param array $radio The input properties.
 * @param false|string|array $label The properties for a label.
 * @param string $input The input widget.
 * @param bool $escape Whether or not to HTML escape the label.
 * @return string Generated label.
 */
	protected function _renderLabel($radio, $label, $input, $escape) {
		if ($label === false) {
			return false;
		}
		$labelAttrs = is_array($label) ? $label : [];
		$labelAttrs += [
			'for' => $radio['id'],
			'escape' => $escape,
			'text' => $radio['text'],
			'input' => $input,
		];
		return $this->_label->render($labelAttrs);
	}

}
