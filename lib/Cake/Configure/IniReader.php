<?php
/**
 * IniReader
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
 * @package       Cake.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Hash', 'Utility');
App::uses('CakePlugin', 'Core');

/**
 * Ini file configuration engine.
 *
 * Since IniReader uses parse_ini_file underneath, you should be aware that this
 * class shares the same behavior, especially with regards to boolean and null values.
 *
 * In addition to the native `parse_ini_file` features, IniReader also allows you
 * to create nested array structures through usage of `.` delimited names. This allows
 * you to create nested arrays structures in an ini config file. For example:
 *
 * `db.password = secret` would turn into `array('db' => array('password' => 'secret'))`
 *
 * You can nest properties as deeply as needed using `.`'s. In addition to using `.` you
 * can use standard ini section notation to create nested structures:
 *
 * {{{
 * [section]
 * key = value
 * }}}
 *
 * Once loaded into Configure, the above would be accessed using:
 *
 * `Configure::read('section.key');
 *
 * You can combine `.` separated values with sections to create more deeply
 * nested structures.
 *
 * IniReader also manipulates how the special ini values of
 * 'yes', 'no', 'on', 'off', 'null' are handled. These values will be
 * converted to their boolean equivalents.
 *
 * @package       Cake.Configure
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
 * @param string $path Path to load ini config files from. Defaults to APP . 'Config' . DS
 * @param string $section Only get one section, leave null to parse and fetch
 *     all sections in the ini file.
 */
	public function __construct($path = null, $section = null) {
		if (!$path) {
			$path = APP . 'Config' . DS;
		}
		$this->_path = $path;
		$this->_section = $section;
	}

/**
 * Read an ini file and return the results as an array.
 *
 * For backwards compatibility, acl.ini.php will be treated specially until 3.0.
 *
 * @param string $key The identifier to read from. If the key has a . it will be treated
 *  as a plugin prefix. The chosen file must be on the reader's path.
 * @return array Parsed configuration values.
 * @throws ConfigureException when files don't exist.
 *  Or when files contain '..' as this could lead to abusive reads.
 */
	public function read($key) {
		if (strpos($key, '..') !== false) {
			throw new ConfigureException(__d('cake_dev', 'Cannot load configuration files with ../ in them.'));
		}

		$file = $this->_getFilePath($key);
		if (!is_file($file)) {
			throw new ConfigureException(__d('cake_dev', 'Could not load configuration file: %s', $file));
		}

		$contents = parse_ini_file($file, true);
		if (!empty($this->_section) && isset($contents[$this->_section])) {
			$values = $this->_parseNestedValues($contents[$this->_section]);
		} else {
			$values = array();
			foreach ($contents as $section => $attribs) {
				if (is_array($attribs)) {
					$values[$section] = $this->_parseNestedValues($attribs);
				} else {
					$parse = $this->_parseNestedValues(array($attribs));
					$values[$section] = array_shift($parse);
				}
			}
		}
		return $values;
	}

/**
 * parses nested values out of keys.
 *
 * @param array $values Values to be exploded.
 * @return array Array of values exploded
 */
	protected function _parseNestedValues($values) {
		foreach ($values as $key => $value) {
			if ($value === '1') {
				$value = true;
			}
			if ($value === '') {
				$value = false;
			}
			unset($values[$key]);
			if (strpos($key, '.') !== false) {
				$values = Hash::insert($values, $key, $value);
			} else {
				$values[$key] = $value;
			}
		}
		return $values;
	}

/**
 * Dumps the state of Configure data into an ini formatted string.
 *
 * @param string $key The identifier to write to. If the key has a . it will be treated
 *  as a plugin prefix.
 * @param array $data The data to convert to ini file.
 * @return int Bytes saved.
 */
	public function dump($key, $data) {
		$result = array();
		foreach ($data as $k => $value) {
			$isSection = false;
			if ($k[0] !== '[') {
				$result[] = "[$k]";
				$isSection = true;
			}
			if (is_array($value)) {
				$kValues = Hash::flatten($value, '.');
				foreach ($kValues as $k2 => $v) {
					$result[] = "$k2 = " . $this->_value($v);
				}
			}
			if ($isSection) {
				$result[] = '';
			}
		}
		$contents = trim(implode("\n", $result));

		$filename = $this->_getFilePath($key);
		return file_put_contents($filename, $contents);
	}

/**
 * Converts a value into the ini equivalent
 *
 * @param mixed $val Value to export.
 * @return string String value for ini file.
 */
	protected function _value($val) {
		if ($val === null) {
			return 'null';
		}
		if ($val === true) {
			return 'true';
		}
		if ($val === false) {
			return 'false';
		}
		return (string)$val;
	}

/**
 * Get file path
 *
 * @param string $key The identifier to write to. If the key has a . it will be treated
 *  as a plugin prefix.
 * @return string Full file path
 */
	protected function _getFilePath($key) {
		if (substr($key, -8) === '.ini.php') {
			$key = substr($key, 0, -8);
			list($plugin, $key) = pluginSplit($key);
			$key .= '.ini.php';
		} else {
			if (substr($key, -4) === '.ini') {
				$key = substr($key, 0, -4);
			}
			list($plugin, $key) = pluginSplit($key);
			$key .= '.ini';
		}

		if ($plugin) {
			$file = CakePlugin::path($plugin) . 'Config' . DS . $key;
		} else {
			$file = $this->_path . $key;
		}

		return $file;
	}

}
