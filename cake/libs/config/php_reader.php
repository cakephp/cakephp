<?php
/**
 * PhpReader file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * PHP Reader allows Configure to load configuration values from 
 * files containing simple PHP arrays.
 *
 * @package cake.libs.config
 */
class PhpReader {
/**
 * The path this reader finds files on.
 *
 * @var string
 */
	protected $_path = null;

/**
 * Constructor for PHP Config file reading.
 *
 * @param string $path The path to read config files from.  Defaults to CONFIGS
 */
	public function __construct($path = CONFIGS) {
		$this->_path = $path;
	}

/**
 * Read a config file and return its contents.
 *
 * Keys with `.` will be treated as values in plugins.  Instead of reading from
 * the initialized path, plugin keys will be located using App::pluginPath().
 *
 *
 * @param string $key The identifier to read from.  If the key has a . it will be treated
 *   as a plugin prefix.
 * @return array Parsed configuration values.
 */
	public function read($key) {
		list($plugin, $key) = pluginSplit($key);
		
		if ($plugin) {
			$file = App::pluginPath($plugin) . 'config' . DS . $key . '.php';
		} else {
			$file = $this->_path . $key . '.php';
		}
		if (!file_exists($file)) {
			throw new RuntimeException(__('Could not load configuration file: ') . $file);
		}
		include $file;
		if (!isset($config)) {
			throw new RuntimeException(
				sprintf(__('No variable $config found in %s.php'), $file)
			);
		}
		return $config;
	}
}