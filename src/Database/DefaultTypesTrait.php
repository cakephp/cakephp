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

trait DefaultTypesTrait {
	
/**
 * Associative array with the default fields and their types this query might contain
 * used to avoid repetition when calling multiple times functions inside this class that
 * may require a custom type for a specific field.
 *
 * @var array
 */
	protected $_defaultTypes = [];

/**
 * Configures a map of default fields and their associated types to be
 * used as the default list of types for every function in this class
 * with a $types param. Useful to avoid repetition when calling the same
 * functions using the same fields and types.
 *
 * If called with no arguments it will return the currently configured types.
 *
 * ## Example
 *
 * {{{
 *	$query->defaultTypes(['created' => 'datetime', 'is_visible' => 'boolean']);
 * }}}
 *
 * @param array $types associative array where keys are field names and values
 * are the correspondent type.
 * @return Query|array
 */
	public function defaultTypes(array $types = null) {
		if ($types === null) {
			return $this->_defaultTypes;
		}
		$this->_defaultTypes = $types;
		return $this;
	}	

}