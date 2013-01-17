<?php
/**
 * 
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database;

trait TypeConverter {

/**
 * Converts a give value to a suitable database value based on type
 * and return relevant internal statement type
 *
 * @param mixed value
 * @param string $type
 * @return array list containing converted value and internal type
 **/
	public function cast($value, $type) {
		if (is_string($type)) {
			$type = Type::build($type);
		}
		if ($type instanceof Type) {
			$value = $type->toDatabase($value, $this->_driver);
			$type = $type->toStatement($value, $this->_driver);
		}
		return [$value, $type];
	}

/**
 * Matches columns to corresponding types
 *
 * Both $columns and $types should either be numeric based or string key based at
 * the same time.
 *
 * @param array $columns list or associative array of columns and parameters to be bound with types
 * @param array $types list or associative array of types
 * @return array
 **/
	public function matchTypes($columns, $types) {
		if (!is_int(key($types))) {
			$positions = array_intersect_key(array_flip($columns), $types);
			$types = array_intersect_key($types, $positions);
			$types = array_combine($positions, $types);
		}
		return $types;
	}

}
