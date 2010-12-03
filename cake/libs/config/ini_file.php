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
 * Ini file configuration parser.  Since IniFile uses parse_ini_file underneath,
 * you should be aware that this class shares the same behavior, especially with
 * regards to boolean and null values.
 *
 * @package cake.config
 * @see http://php.net/parse_ini_file
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
 * @param string $section Only get one section.
 */
	public function __construct($filename, $section = null) {
		$contents = parse_ini_file($filename, true);
		if (!empty($section) && isset($contents[$section])) {
			$this->_values = $contents[$section];
		} else {
			$this->_values = $contents;
		}
	}

/**
 * Get the contents of the ini file as a plain array.
 *
 * @return array
 */
	public function asArray() {
		return $this->_values;
	}

/**
 * Part of ArrayAccess implementation.
 *
 * @param string $name
 */
	public function offsetExists($name) {
		return isset($this->_values[$name]);
	}

/**
 * Part of ArrayAccess implementation.
 *
 * @param string $name
 */
	public function offsetGet($name) {
		if (!isset($this->_values[$name])) {
			return null;
		}
		return $this->_values[$name];
	}

/**
 * Part of ArrayAccess implementation.
 *
 * @param string $name
 */
	public function offsetSet($name, $value) {
		throw new LogicException('You cannot modify an IniFile parse result.');
	}

/**
 * Part of ArrayAccess implementation.
 *
 * @param string $name
 */
	public function offsetUnset($name) {
		unset($this->_values[$name]);
	}
}