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

/**
 * Input widget for creating checkbox widgets.
 */
class Checkbox implements WidgetInterface {

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
 * @param \Cake\View\StringTemplate $templates Templates list.
 */
	public function __construct($templates, $label) {
		$this->_templates = $templates;
		$this->_label = $label;
	}

/**
 * Render a checkbox element.
 *
 * Data supports the following keys:
 *
 * - `name` - The name of the input.
 * - `value` - The value attribute. Defaults to '1'.
 * - `val` - The current value. If it matches `value` the checkbox will be checked.
 * - `label` - Either false to disable label generation, or
 *   an array of attributes for the label template.
 *   You can also use the 'checked' attribute to make the checkbox checked.
 * - `disabled` - Whether or not the checkbox should be disabled.
 *
 * Any other attributes passed in will be treated as HTML attributes.
 *
 * @param array $data The data to create a checkbox with.
 * @param \Cake\View\Form\ContextInterface $context The current form context.
 * @return string Generated HTML string.
 */
	public function render(array $data, ContextInterface $context) {
		$data += [
			'name' => '',
			'value' => 1,
			'val' => null,
			'disabled' => false,
			'label' => null,
			'escape' => true
		];

		$escape = $data['escape'];
		$label = $data['label'];

		if ($this->_isChecked($data)) {
			$data['checked'] = true;
		}
		unset($data['val'], $data['escape'], $data['label']);

		$attrs = $this->_templates->formatAttributes(
			$data,
			['name', 'value']
		);

		$input = $this->_templates->format('checkbox', [
			'name' => $data['name'],
			'value' => $data['value'],
			'attrs' => $attrs
		]);

		return $this->_renderLabel(
			$data,
			$label,
			$input,
			$context,
			$escape
		) ?: $input;
	}

/**
 * Renders a label element for the given checkbox.
 *
 * @param array $attrs The input properties.
 * @param false|string|array $label The properties for a label.
 * @param string $input The input widget.
 * @param \Cake\View\Form\ContextInterface $context The form context.
 * @param bool $escape Whether or not to HTML escape the label.
 * @return string Generated label.
 */
	protected function _renderLabel($attrs, $label, $input, $context, $escape) {
		if ($label === false) {
			return false;
		}
		$labelAttrs = is_array($label) ? $label : ['text' => $label];
		$labelAttrs += [
			'for' => isset($attrs['id']) ? $attrs['id'] : null,
			'escape' => $escape,
			'input' => $input,
		];
		return $this->_label->render($labelAttrs, $context);
	}

/**
 * Check whether or not the checkbox should be checked.
 *
 * @param array $data Data to look at and determine checked state.
 * @return bool
 */
	protected function _isChecked($data) {
		if (array_key_exists('checked', $data)) {
			return (bool)$data['checked'];
		}
		if ((string)$data['val'] === (string)$data['value']) {
			return true;
		}
		return false;
	}

/**
 * {@inheritDoc}
 */
	public function secureFields(array $data) {
		return [$data['name']];
	}

}
