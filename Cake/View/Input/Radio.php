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
use Cake\Utility\Inflector;
use Traversable;

/**
 * Input widget class for generating a set of radio buttons.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class Radio {

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
 * Render a set of radio buttons.
 *
 * Data supports the following keys:
 *
 * - `name` - Set the input name.
 * - `options` - An array of options. See below for more information.
 * - `disabled` - Either true or an array of inputs to disable.
 *    When true, the select element will be disabled.
 * - `value` - A string  of the option to mark as selected.
 * - `label` - Either false to disable label generation, or
 *   an array of attributes for all labels.
 *
 * @param array $data The data to build radio buttons with.
 * @return string
 */
	public function render($data) {
		$data += [
			'name' => '',
			'options' => [],
			'disabled' => null,
			'value' => null,
			'escape' => true,
			'label' => true,
		];
		$opts = [];
		$options = (array)$data['options'];
		$escape = $data['escape'];
		foreach ($options as $val => $text) {
			$radio = ['value' => $val];
			$radio['name'] = $data['name'];

			if (empty($radio['id'])) {
				$radio['id'] = Inflector::slug($radio['name'] . '_' . $radio['value']);
			}

			$labelAttrs = ['for' => $radio['id'], 'escape' => $escape];
			$label = $this->_templates->format('label', [
				'text' => $escape ? h($text) : $text,
				'attrs' => $this->_templates->formatAttributes($labelAttrs),
			]);

			$input = $this->_templates->format('radio', [
				'name' => $radio['name'],
				'value' => $escape ? h($val) : $val,
				'attrs' => $this->_templates->formatAttributes($radio, ['value', 'name']),
			]);

			$opts[] = $this->_templates->format('radioContainer', [
				'input' => $input,
				'label' => $label,
			]);
		}
		return implode('', $opts);
	}

}
