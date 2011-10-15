<?php
/**
 * Wincache storage engine for cache.
 *
 * Supports wincache 1.1.0 and higher.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Cache.Engine
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Wincache storage engine for cache
 *
 * @package       Cake.Cache.Engine
 */
class WincacheEngine extends CacheEngine {

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $settings array of setting for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 * @see CacheEngine::__defaults
 */
	public function init($settings = array()) {
		parent::init(array_merge(array(
			'engine' => 'Wincache',
			'prefix' => Inflector::slug(APP_DIR) . '_'),
		$settings));
		return function_exists('wincache_ucache_info');
	}

/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param integer $duration How long to cache the data, in seconds
 * @return boolean True if the data was successfully cached, false on failure
 */
	public function write($key, $value, $duration) {
		$expires = time() + $duration;

		$data = array(
			$key . '_expires' => $expires,
			$key => $value
		);
		$result = wincache_ucache_set($data, null, $duration);
		return empty($result);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if
 *     there was an error fetching it
 */
	public function read($key) {
		$time = time();
		$cachetime = intval(wincache_ucache_get($key . '_expires'));
		if ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime) {
			return false;
		}
		return wincache_ucache_get($key);
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to increment
 * @return New incremented value, false otherwise
 */
	public function increment($key, $offset = 1) {
		return wincache_ucache_inc($key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to subtract
 * @return New decremented value, false otherwise
 */
	public function decrement($key, $offset = 1) {
		return wincache_ucache_dec($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		return wincache_ucache_delete($key);
	}

/**
 * Delete all keys from the cache.  This will clear every
 * item in the cache matching the cache config prefix.
 *
 * @param boolean $check If true, nothing will be cleared, as entries will
 *   naturally expire in wincache..
 * @return boolean True Returns true.
 */
	public function clear($check) {
		if ($check) {
			return true;
		}
		$info = wincache_ucache_info();
		$cacheKeys = $info['ucache_entries'];
		unset($info);
		foreach ($cacheKeys as $key) {
			if (strpos($key['key_name'], $this->settings['prefix']) === 0) {
				wincache_ucache_delete($key['key_name']);
			}
		}
		return true;
	}

}
