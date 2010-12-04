<?php
/**
 * IniReader
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
 * Ini file configuration parser.  Since IniReader uses parse_ini_file underneath,
 * you should be aware that this class shares the same behavior, especially with
 * regards to boolean and null values.
 *
 * @package cake.config
 * @see http://php.net/parse_ini_file
 */
class IniReader implements ConfigReaderInterface {

/**
 * The path to read ini files from.
 *
 * @var array
 */
	protected $_path;

/**
 * The section to read, if null all sections will be read.
 *
 * @var string
 */
	protected $_section;

/**
 * Build and construct a new ini file parser. The parser can be used to read
 * ini files that are on the filesystem.
 *
 * @param string $path Path to load ini config files from.
 * @param string $section Only get one section.
 */
	public function __construct($path, $section = null) {
		$this->_path = $path;
		$this->_section = $section;
	}

/**
 * Read an ini file and return the results as an array.
 *
 * @param string $file Name of the file to read.
 * @return array
 */
	public function read($file) {
		$filename = $this->_path . $file;
		$contents = parse_ini_file($filename, true);
		if (!empty($this->_section) && isset($contents[$this->_section])) {
			$values = $contents[$this->_section];
		} else {
			$values = $contents;
		}
		return $values;
	}
}