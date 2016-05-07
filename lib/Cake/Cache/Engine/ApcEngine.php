<?php
/**
 * APC storage engine for cache.
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
 * @package       Cake.Cache.Engine
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * APC storage engine for cache
 *
 * @package       Cake.Cache.Engine
 */
class ApcEngine extends CacheEngine {

/**
 * Contains the compiled group names
 * (prefixed with the global configuration prefix)
 *
 * @var array
 */
	protected $_compiledGroupNames = array();

/**
 * APC or APCu extension
 *
 * @var string
 */
	protected $_apcExtension = 'apc';

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $settings array of setting for the engine
 * @return bool True if the engine has been successfully initialized, false if not
 * @see CacheEngine::__defaults
 */
	public function init($settings = array()) {
		if (!isset($settings['prefix'])) {
			$settings['prefix'] = Inflector::slug(APP_DIR) . '_';
		}
		$settings += array('engine' => 'Apc');
		parent::init($settings);
		if (function_exists('apcu_dec')) {
			$this->_apcExtension = 'apcu';
			return true;
		}
		return function_exists('apc_dec');
	}

/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param int $duration How long to cache the data, in seconds
 * @return bool True if the data was successfully cached, false on failure
 */
	public function write($key, $value, $duration) {
		$expires = 0;
		if ($duration) {
			$expires = time() + $duration;
		}
		$this->_apcCall('store', $key . '_expires', $expires, $duration);
		return $this->_apcCall('store', $key, $value, $duration);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		$time = time();
		$cachetime = (int)$this->_apcCall('fetch', $key . '_expires');
		if ($cachetime !== 0 && ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime)) {
			return false;
		}
		return $this->_apcCall('fetch', $key);
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to increment
 * @return New incremented value, false otherwise
 */
	public function increment($key, $offset = 1) {
		return $this->_apcCall('inc', $key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to subtract
 * @return New decremented value, false otherwise
 */
	public function decrement($key, $offset = 1) {
		return $this->_apcCall('dec', $key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		return $this->_apcCall('delete', $key);
	}

/**
 * Delete all keys from the cache. This will clear every cache config using APC.
 *
 * @param bool $check If true, nothing will be cleared, as entries are removed
 *    from APC as they expired. This flag is really only used by FileEngine.
 * @return bool True Returns true.
 */
	public function clear($check) {
		if ($check) {
			return true;
		}
		if (class_exists('APCIterator', false)) {
			$iterator = new APCIterator(
				'user',
				'/^' . preg_quote($this->settings['prefix'], '/') . '/',
				APC_ITER_NONE
			);
			$this->_apcCall('delete', $iterator);
			return true;
		}
		$cache = $this->_apcExtension === 'apc' ? apc_cache_info('user') : apcu_cache_info();
		foreach ($cache['cache_list'] as $key) {
			if (strpos($key['info'], $this->settings['prefix']) === 0) {
				$this->_apcCall('delete', $key['info']);
			}
		}
		return true;
	}

/**
 * Returns the `group value` for each of the configured groups
 * If the group initial value was not found, then it initializes
 * the group accordingly.
 *
 * @return array
 */
	public function groups() {
		if (empty($this->_compiledGroupNames)) {
			foreach ($this->settings['groups'] as $group) {
				$this->_compiledGroupNames[] = $this->settings['prefix'] . $group;
			}
		}

		$groups = $this->_apcCall('fetch', $this->_compiledGroupNames);
		if (count($groups) !== count($this->settings['groups'])) {
			foreach ($this->_compiledGroupNames as $group) {
				if (!isset($groups[$group])) {
					$this->_apcCall('store', $group, 1);
					$groups[$group] = 1;
				}
			}
			ksort($groups);
		}

		$result = array();
		$groups = array_values($groups);
		foreach ($this->settings['groups'] as $i => $group) {
			$result[] = $group . $groups[$i];
		}
		return $result;
	}

/**
 * Increments the group value to simulate deletion of all keys under a group
 * old values will remain in storage until they expire.
 *
 * @param string $group The group to clear.
 * @return bool success
 */
	public function clearGroup($group) {
		$func = function_exists('apc_inc') ? 'apc_inc' : 'apcu_inc';
		$func($this->settings['prefix'] . $group, 1, $success);
		return $success;
	}

/**
 * Write data for key into cache if it doesn't exist already. 
 * If it already exists, it fails and returns false.
 *
 * @param string $key Identifier for the data.
 * @param mixed $value Data to be cached.
 * @param int $duration How long to cache the data, in seconds.
 * @return bool True if the data was successfully cached, false on failure.
 * @link http://php.net/manual/en/function.apc-add.php
 */
	public function add($key, $value, $duration) {
		$expires = 0;
		if ($duration) {
			$expires = time() + $duration;
		}
		$this->_apcCall('add', $key . '_expires', $expires, $duration);
		return $this->_apcCall('add', $key, $value, $duration);
	}

/**
 * Call APC(u) function
 *
 * @return mixed
 */
	protected function _apcCall() {
		$params = func_get_args();
		$func = $this->_apcExtension . '_' . array_shift($params);
		return call_user_func_array($func, $params);
	}
}
