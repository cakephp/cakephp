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

/**
 * Text input class.
 */
class Text {

/**
 * StringTemplate instance.
 *
 * @var Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Constructor.
 *
 * @param StringTemplate $templates
 */
	public function __construct($templates) {
		$this->_templates = $templates;
	}

/**
 * Render a text widget or other simple widget like email/tel/number.
 *
 * @param array $data The data to build an input with.
 * @return string
 */
	public function render($data) {
		$data += [
			'name' => '',
			'value' => null,
			'type' => 'text',
			'escape' => true,
		];
		return $this->_templates->format('input', [
			'name' => $data['name'],
			'type' => $data['type'],
			'attrs' => $this->_templates->formatAttributes(
				$data,
				['name', 'type']
			),
		]);
	}

}
