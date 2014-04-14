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
 * Constructor
 *
 * @param \Cake\View\StringTemplate $templates
 */
	public function __construct($templates) {
		$this->_templates = $templates;
	}

/**
 * Render a checkbox element.
 *
 * Data supports the following keys:
 *
 * - `name` - The name of the input.
 * - `value` - The value attribute. Defaults to '1'.
 * - `val` - The current value. If it matches `value` the checkbox will be checked.
 *   You can also use the 'checked' attribute to make the checkbox checked.
 * - `disabled` - Whether or not the checkbox should be disabled.
 *
 * Any other attributes passed in will be treated as HTML attributes.
 *
 * @param array $data The data to create a checkbox with.
 * @return string Generated HTML string.
 */
	public function render(array $data) {
		$data += [
			'name' => '',
			'value' => 1,
			'val' => null,
			'checked' => false,
			'disabled' => false,
		];
		if ($this->_isChecked($data)) {
			$data['checked'] = true;
		}
		unset($data['val']);

		$attrs = $this->_templates->formatAttributes(
			$data,
			['name', 'value']
		);

		return $this->_templates->format('checkbox', [
			'name' => $data['name'],
			'value' => $data['value'],
			'attrs' => $attrs
		]);
	}

/**
 * Check whether or not the checkbox should be checked.
 *
 * @param array $data Data to look at and determine checked state.
 * @return bool
 */
	protected function _isChecked($data) {
		if (!empty($data['checked'])) {
			return true;
		}
		if ((string)$data['val'] === (string)$data['value']) {
			return true;
		}
		return false;
	}

}
