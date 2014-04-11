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
 * Input widget class for generating a file upload control.
 *
 * This class is intended as an internal implementation detail
 * of Cake\View\Helper\FormHelper and is not intended for direct use.
 */
class File implements WidgetInterface {

/**
 * Constructor
 *
 * @param \Cake\View\StringTemplate $templates
 */
	public function __construct($templates) {
		$this->_templates = $templates;
	}

/**
 * Render a file upload form widget.
 *
 * Data supports the following keys:
 *
 * - `name` - Set the input name.
 * - `escape` - Set to false to disable HTML escaping.
 *
 * All other keys will be converted into HTML attributes.
 * Unlike other input objects the `val` property will be specifically
 * ignored.
 *
 * @param array $data The data to build a file input with.
 * @return string HTML elements.
 */
	public function render(array $data) {
		$data += [
			'name' => '',
			'escape' => true,
		];
		unset($data['val']);

		return $this->_templates->format('file', [
			'name' => $data['name'],
			'attrs' => $this->_templates->formatAttributes(
				$data, ['name', 'val']
			)
		]);
	}

}
