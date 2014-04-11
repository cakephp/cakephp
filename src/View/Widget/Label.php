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
 * Form 'widget' for creating labels.
 *
 * Generally this element is used by other widgets,
 * and FormHelper itself.
 */
class Label implements WidgetInterface {

/**
 * Templates
 *
 * @var \Cake\View\StringTemplate
 */
	protected $_templates;

/**
 * Constructor.
 *
 * This class uses the following template:
 *
 * - `label` Used to generate the label for a radio button.
 *   Can use the following variables `attrs`, `text` and `input`.
 *
 * @param \Cake\View\StringTemplate $templates
 */
	public function __construct($templates) {
		$this->_templates = $templates;
	}

/**
 * Render a label widget.
 *
 * Accepts the following keys in $data:
 *
 * - `text` The text for the label.
 * - `input` The input that can be formatted into the label if the template allows it.
 * - `escape` Set to false to disable HTML escaping.
 *
 * All other attributes will be converted into HTML attributes.
 *
 * @param array $data
 * @return string
 */
	public function render(array $data) {
		$data += [
			'text' => '',
			'input' => '',
			'escape' => true,
		];

		return $this->_templates->format('label', [
			'text' => $data['escape'] ? h($data['text']) : $data['text'],
			'input' => $data['input'],
			'attrs' => $this->_templates->formatAttributes($data, ['text', 'input']),
		]);
	}

}
