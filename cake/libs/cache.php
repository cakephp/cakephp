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
 * Tries to find and include a file for a cache engine and returns object instance
 *
 * @param $name	Name of the engine (without 'Engine')
 * @return mixed $engine object or null
 * @access private
 */
	function __loadEngine($name) {
		if (!class_exists($name . 'Engine')) {
			$fileName = LIBS . DS . 'cache' . DS . strtolower($name) . '.php';
			if (!require($fileName)) {
				return false;
			}
		}
		return true;
	}
/**
 * Set the cache engine to use
 *
 * @param string $name Name of the engine (without 'Engine')
 * @param array $settings Optional associative array of settings passed to the engine
 * @return boolean True on success, false on failure
 * @access public
 */
	function engine($name = 'File', $settings = array()) {
		if (Configure::read('Cache.disable')) {
			return false;
		}
		$cacheClass = $name . 'Engine';
		$_this =& Cache::getInstance();
		if (!isset($_this->_Engine)) {
			if ($_this->__loadEngine($name) === false) {
				return false;
			}
		}

		if (!isset($_this->_Engine) || (isset($_this->_Engine) && $_this->_Engine->name !== $name)) {
			$_this->_Engine =& new $cacheClass($name);
		}

		if ($_this->_Engine->init($settings)) {
			if (time() % $_this->_Engine->settings['probability'] == 0) {
				$_this->_Engine->gc();
			}
			return true;
		}

		$_this->_Engine = null;
		return false;
	}
/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached - anything except a resource
 * @param mixed $duration Optional - how long to cache the data, either in seconds or a string that can be parsed by the strtotime() function
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, $value, $duration = null) {
		$_this =& Cache::getInstance();
		if (!$_this->isInitialized()) {
			return false;
		}

		$key = strval($key);
		if (empty($key)) {
			return false;
		}
		if (is_resource($value)) {
			return false;
		}
		if ($duration == null) {
			$duration = $_this->_Engine->settings['duration'];
		}
		$duration = ife(is_string($duration), strtotime($duration) - time(), intval($duration));
		if ($duration < 1) {
			return false;
		}
		return $_this->_Engine->write($key, $value, $duration);
	}
/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		$_this =& Cache::getInstance();
		if (!$_this->isInitialized()) {
			return false;
		}
		$key = strval($key);
		if (empty($key)) {
			return false;
		}
		return $_this->_Engine->read($key);
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		$_this =& Cache::getInstance();
		if (!$_this->isInitialized()) {
			return false;
		}
		$key = strval($key);
		if (empty($key)) {
			return false;
		}
		return $_this->_Engine->delete($key);
	}
/**
 * Delete all keys from the cache
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear($check = false) {
		$_this =& Cache::getInstance();
		if (!$_this->isInitialized()) {
			return false;
		}
		return $_this->_Engine->clear($check);
	}
/**
 * Check if Cache has initialized a working storage engine
 *
 * @return boolean
 * @access public
 */
	function isInitialized() {
		if (Configure::read('Cache.disable')) {
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
 * Name of engine being used
 *
 * @var int
 * @access public
 */
	var $name;
/**
 * settings of current engine instance
 *
 * @var int
 * @access public
 */
	var $settings;
/**
 * Constructor
 *
 * @access private
 */
	function __construct($name = null) {
		$this->name = $name;
	}
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 * @access public
 */
	function init($settings = array()) {
		$this->settings = am(array('duration'=> 3600, 'probability'=> 100), $settings);
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
 * Write value for a key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$value, $duration) {
		trigger_error(sprintf(__('Method write() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		trigger_error(sprintf(__('Method read() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
	}
/**
 * Delete all keys from the cache
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear($check) {
	}
/**
 * Cache Engine settings
 *
 * @return array settings
 * @access public
 */
	function settings() {
		return am($this->settings, array('name'=> $this->name));
	}
}
?>