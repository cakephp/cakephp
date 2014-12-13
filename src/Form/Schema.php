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
namespace Cake\Form;

/**
 * Contains the schema information for Form instances.
 */
class Schema {

	protected $_fields = [];

	protected $_fieldDefaults = [
		'type' => null,
		'length' => null,
		'required' => false,
	];

	public function addField($name, $attrs) {
		$attrs = array_intersect_key($attrs, $this->_fieldDefaults);
		$this->_fields[$name] = $attrs + $this->_fieldDefaults;
		return $this;
	}

	public function removeField($name) {
		unset($this->_fields[$name]);
		return $this;
	}

	public function fields() {
		return array_keys($this->_fields);
	}

	public function field($name) {
		if (!isset($this->_fields[$name])) {
			return null;
		}
		return $this->_fields[$name];
	}
}
