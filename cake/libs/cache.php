<?php
/* SVN FILE: $Id$ */
/**
 * Caching for CakePHP.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0.4933
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 */
if (!class_exists('object')) {
	uses('object');
}
/**
 * Set defines if not set in core.php
 */
if (!defined('CACHE_DEFAULT_DURATION')) {
	define('CACHE_DEFAULT_DURATION', 3600);
}
if (!defined('CACHE_GC_PROBABILITY')) {
	define('CACHE_GC_PROBABILITY', 100);
}
/**
 * Caching for CakePHP.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Cache extends Object {
/**
 * Cache engine to use
 *
 * @var object
 * @access protected
 */
	var $_Engine = null;
/**
 * Create cache.
 */
	function __construct() {
	}
/**
 * Returns a singleton instance
 *
 * @return object
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new Cache();
		}
		return $instance[0];
	}
/**
 * Tries to find and include a file for a cache engine
 *
 * @param $name	Name of the engine (without 'Engine')
 * @return boolean
 * @access protected
 */
	function _includeEngine($name) {
		if (class_exists($name.'Engine')) {
			return true;
		}
		$fileName = strtolower($name);
		if (vendor('cache_engines' . DS . $fileName)) {
			return true;
		}
		$fileName = dirname(__FILE__) . DS . 'cache' . DS . $fileName . '.php';
		if (is_readable($fileName)) {
			require $fileName;
			return true;
		}
		return false;
	}
/**
 * Set the cache engine to use
 *
 * @param string $name Name of the engine (without 'Engine')
 * @param array $parmas Optional associative array of parameters passed to the engine
 * @return boolean True on success, false on failure
 * @access public
 */
	function engine($name = 'File', $params = array()) {
		if (defined('DISABLE_CACHE')) {
			return false;
		}
		$_this =& Cache::getInstance();
		$cacheClass = $name.'Engine';
		if (!Cache::_includeEngine($name) || !class_exists($cacheClass)) {
			return false;
		}

		$_this->_Engine =& new $cacheClass();

		if ($_this->_Engine->init($params)) {
			if (time() % CACHE_GC_PROBABILITY == 0) {
				$_this->_Engine->gc();
			}
			return true;
		}
		$_this->_Engine = null;
		return false;
	}
/**
 * Write a value in the cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached - anything except a resource
 * @param mixed $duration Optional - how long to cache the data, either in seconds or a string that can be parsed by the strtotime() function
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, $value, $duration = CACHE_DEFAULT_DURATION) {
		if (defined('DISABLE_CACHE')) {
			return false;
		}
		$key = strval($key);
		if (empty($key)) {
			return false;
		}
		if (is_resource($value)) {
			return false;
		}
		$duration = ife(is_string($duration), strtotime($duration) - time(), intval($duration));
		if ($duration < 1) {
			return false;
		}

		$_this =& Cache::getInstance();
		if (!isset($_this->_Engine)) {
			return false;
		}
		return $_this->_Engine->write($key, $value, $duration);
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		if (defined('DISABLE_CACHE')) {
			return false;
		}
		$key = strval($key);
		if (empty($key)) {
			return false;
		}

		$_this =& Cache::getInstance();
		if (!isset($_this->_Engine)) {
			return false;
		}
		return $_this->_Engine->read($key);
	}
/**
 * Delete a value from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		if (defined('DISABLE_CACHE')) {
			return false;
		}
		$key = strval($key);
		if (empty($key)) {
			return false;
		}

		$_this =& Cache::getInstance();
		if (!isset($_this->_Engine)) {
			return false;
		}
		return $_this->_Engine->delete($key);
	}
/**
 * Delete all values from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		if (defined('DISABLE_CACHE')) {
			return false;
		}

		$_this =& Cache::getInstance();
		if (!isset($_this->_Engine)) {
			return false;
		}
		return $_this->_Engine->clear();
	}
/**
 * Check if Cache has initialized a working storage engine
 *
 * @return boolean
 * @access public
 */
	function isInitialized() {
		if (defined('DISABLE_CACHE')) {
			return false;
		}
		$_this =& Cache::getInstance();
		return isset($_this->_Engine);
	}

/**
 * Return the settings for current cache engine
 *
 * @return array list of settings for this engine
 * @access public
 */
	function settings() {
		$_this =& Cache::getInstance();
		if (!is_null($_this->_Engine)) {
			return $_this->_Engine->settings();
		}
	}
}
/**
 * Storage engine for CakePHP caching
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class CacheEngine extends Object {
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 * @access public
 */
	function init($params) {
	}
/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 *
 * @access public
 */
	function gc() {
	}
/**
 * Write a value in the cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$value, $duration = CACHE_DEFAULT_DURATION) {
		trigger_error(sprintf(__('Method write() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		trigger_error(sprintf(__('Method read() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
/**
 * Delete a value from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
	}
/**
 * Delete all values from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
	}
/**
 * Delete all values from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function settings() {
		trigger_error(sprintf(__('Method settings() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
}
?>