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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database;

class TypeMap {

/**
 * @var array
 */
	protected $_defaults;

/**
 * @var array
 */
	protected $_types = [];
/**
 * @param array $defaults
 */
	public function __construct(array $defaults = []) {
			$this->defaults($defaults);
	}

/**
 * Set/Get defaults
 *
 * @var this|array
 */
	public function defaults(array $defaults = null) {
		if ($defaults === null) {
				return $this->_defaults;
		}
		$this->_defaults = $defaults;
		return $this;
	}

/**
 * Set/Get types
 *
 * @var this|array
 */
	public function types(array $types = null) {
		if ($types === null) {
			return $this->_types;
		}
		$this->_types = $types;
		return $this;
	}

/**
 * Get column type
 *
 * @var string
 */
	public function type($column) {
		if (isset($this->_types[$column])) {
			return $this->_types[$column];
		}
		if (isset($this->_defaults[$column])) {
			return $this->_defaults[$column];
		}
		return null;
	}

}