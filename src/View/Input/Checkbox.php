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
 * Input widget for creating checkbox widgets.
 */
class Checkbox {

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
 * Render a checkbox element.
 *
 * @param array $data The data to create a checkbox with.
 */
	public function render($data) {
		$data += [
			'name' => '',
			'value' => 1,
			'checked' => false,
			'disabled' => false,
		];
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

}
