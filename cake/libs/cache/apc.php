<?php
/**
 * APC storage engine for cache.
 *
 *
 * PHP versions 4 and 5
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
 * @subpackage    cake.cake.libs.cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * APC storage engine for cache
 *
 * @package       cake
 * @subpackage    cake.cake.libs.cache
 */
class ApcEngine extends CacheEngine {

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $setting array of setting for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 * @see CacheEngine::__defaults
 * @access public
 */
	function init($settings = array()) {
		parent::init(array_merge(array('engine' => 'Apc', 'prefix' => Inflector::slug(APP_DIR) . '_'), $settings));
		return function_exists('apc_cache_info');
	}

/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param integer $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$value, $duration) {
		$expires = time() + $duration;
		apc_store($key.'_expires', $expires, $duration);
		return apc_store($key, $value, $duration);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		$time = time();
		$cachetime = intval(apc_fetch($key.'_expires'));
		if ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime) {
			return false;
		}
		return apc_fetch($key);
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to increment
 * @param integer $duration How long to cache the data, in seconds
 * @return New incremented value, false otherwise
 * @access public
 */
	function increment($key, $offset = 1) {
		return apc_inc($key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to substract
 * @param integer $duration How long to cache the data, in seconds
 * @return New decremented value, false otherwise
 * @access public
 */
	function decrement($key, $offset = 1) {
		return apc_dec($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return apc_delete($key);
	}

/**
 * Delete all keys from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		return apc_clear_cache('user');
	}
}
