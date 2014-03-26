<?php
/**
 * PHP Version 5.4
 *
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

use Cake\Database\TypeMap;

trait TypeMapTrait {

/**
 * @var \Cake\Database\TypeMap
 */
	protected $_typeMap;

/**
 * Setter/Getter for type map
 *
 * @return this|TypeMap
 */
	public function typeMap($typeMap = null) {
		if ($typeMap === null) {
			$this->_typeMap = ($this->_typeMap) ?: new TypeMap();
			return $this->_typeMap;
		}
		$this->_typeMap = $typeMap;
		return $this;
	}

/**
 * Allows setting default types when chaining query
 *
 * @return this|array
 */
	public function defaultTypes(array $types = null) {
		if ($types === null) {
			return $this->typeMap()->defaults();
		}
		$this->typeMap()->defaults($types);
		return $this;
	}

}