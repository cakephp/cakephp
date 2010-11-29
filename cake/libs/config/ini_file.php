<?php
/**
 * IniFile
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Ini file configuration parser.
 *
 * @package cake.config
 */
class IniFile implements ArrayAccess {

/**
 * Values inside the ini file.
 *
 * @var array
 */
	protected $_values = array();

/**
 * Build and construct a new ini file parser, the parser will be a representation of the ini 
 * file as an object.
 *
 * @param string $filename Full path to the file to parse.
 */
	public function __construct($filename) {
		$contents = parse_ini_file($filename, true);
		$this->_values = $contents;
	}

	public function offsetExists($name) {
		return isset($this->_values[$name]);
	}
	
	public function offsetGet($name) {
		if (!isset($this->_values[$name])) {
			return null;
		}
		return $this->_values[$name];
	}
	
	public function offsetSet($name, $value) {
		$this->_values[$name] = $value;
	}
	
	public function offsetUnset($name) {
		unset($this->_values[$name]);
	}
}