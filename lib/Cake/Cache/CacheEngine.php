<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Storage engine for CakePHP caching
 *
 * @package       Cake.Cache
 */
abstract class CacheEngine {

/**
 * Settings of current engine instance
 *
 * @var array
 */
	public $settings = array();

/**
 * Contains the compiled string with all groups
 * prefixes to be prepeded to every key in this cache engine
 *
 * @var string
 */
	protected $_groupPrefix = null;

/**
 * Initialize the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $settings Associative array of parameters for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 */
	public function init($settings = array()) {
		$settings += $this->settings + array(
			'prefix' => 'cake_',
			'duration' => 3600,
			'probability' => 100,
			'groups' => array()
		);
		$this->settings = $settings;
		if (!empty($this->settings['groups'])) {
			sort($this->settings['groups']);
			$this->_groupPrefix = str_repeat('%s_', count($this->settings['groups']));
		}
		if (!is_numeric($this->settings['duration'])) {
			$this->settings['duration'] = strtotime($this->settings['duration']) - time();
		}
		return true;
	}

/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 *
 * @param integer $expires [optional] An expires timestamp, invalidataing all data before.
 * @return void
 */
	public function gc($expires = null) {
	}

/**
 * Write value for a key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param integer $duration How long to cache for.
 * @return boolean True if the data was successfully cached, false on failure
 */
	abstract public function write($key, $value, $duration);

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	abstract public function read($key);

/**
 * Increment a number under the key and return incremented value
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to add
 * @return New incremented value, false otherwise
 */
	abstract public function increment($key, $offset = 1);

/**
 * Decrement a number under the key and return decremented value
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to subtract
 * @return New incremented value, false otherwise
 */
	abstract public function decrement($key, $offset = 1);

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	abstract public function delete($key);

/**
 * Delete all keys from the cache
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @return boolean True if the cache was successfully cleared, false otherwise
 */
	abstract public function clear($check);

/**
 * Clears all values belonging to a group. Is upt to the implementing engine
 * to decide whether actually deete the keys or just simulate it to acheive
 * the same result.
 *
 * @param string $groups name of the group to be cleared
 * @return boolean
 */
	public function clearGroup($group) {
		return false;
	}

/**
 * Does whatever initialization for each group is required
 * and returns the `group value` for each of them, this is
 * the token representing each group in the cache key
 *
 * @return array
 */
	public function groups() {
		return $this->settings['groups'];
	}

/**
 * Cache Engine settings
 *
 * @return array settings
 */
	public function settings() {
		return $this->settings;
	}

/**
 * Generates a safe key for use with cache engine storage engines.
 *
 * @param string $key the key passed over
 * @return mixed string $key or false
 */
	public function key($key) {
		if (empty($key)) {
			return false;
		}

		$prefix = '';
		if (!empty($this->_groupPrefix)) {
			$prefix = vsprintf($this->_groupPrefix, $this->groups());
		}

		$key = preg_replace('/[\s]+/', '_', strtolower(trim(str_replace(array(DS, '/', '.'), '_', strval($key)))));
		return $prefix . $key;
	}

}
