<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

use Cake\Utility\Inflector;

/**
 * Storage engine for CakePHP caching
 *
 */
abstract class CacheEngine {

/**
 * Runtime config
 *
 * This is the config of a particular instance
 *
 * @var array
 */
	protected $_config = [];

/**
 * The default cache configuration is overriden in most cache adapters. These are
 * the keys that are common to all adapters. If overriden, this property is not used.
 *
 * - `duration` Specify how long items in this cache configuration last.
 * - `groups` List of groups or 'tags' associated to every key stored in this config.
 *    handy for deleting a complete group from cache.
 * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
 *    with either another cache config or another application.
 * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
 *    cache::gc from ever being called automatically.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'duration' => 3600,
		'groups' => [],
		'prefix' => 'cake_',
		'probability' => 100
	];

/**
 * Contains the compiled string with all groups
 * prefixes to be prepended to every key in this cache engine
 *
 * @var string
 */
	protected $_groupPrefix = null;

/**
 * Initialize the cache engine
 *
 * Called automatically by the cache frontend. Merge the runtime config with the defaults
 * before use.
 *
 * @param array $config Associative array of parameters for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 */
	public function init($config = []) {
		$this->_config = $config + $this->_defaultConfig;
		if (!empty($this->_config['groups'])) {
			sort($this->_config['groups']);
			$this->_groupPrefix = str_repeat('%s_', count($this->_config['groups']));
		}
		if (!is_numeric($this->_config['duration'])) {
			$this->_config['duration'] = strtotime($this->_config['duration']) - time();
		}
		return true;
	}

/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 *
 * @param integer $expires [optional] An expires timestamp, invalidating all data before.
 * @return void
 */
	public function gc($expires = null) {
	}

/**
 * Write value for a key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @return boolean True if the data was successfully cached, false on failure
 */
	abstract public function write($key, $value);

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
 * Clears all values belonging to a group. Is up to the implementing engine
 * to decide whether actually delete the keys or just simulate it to achieve
 * the same result.
 *
 * @param string $group name of the group to be cleared
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
		return $this->_config['groups'];
	}

/**
 * Cache Engine config
 *
 * @return array config
 */
	public function config() {
		return $this->_config;
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

		$key = preg_replace('/[\s]+/', '_', strtolower(trim(str_replace([DS, '/', '.'], '_', strval($key)))));
		return $prefix . $key;
	}

/**
 * Generates a safe key, taking account of the configured key prefix
 *
 * @param string $key the key passed over
 * @return mixed string $key or false
 * @throws \InvalidArgumentException If key's value is empty
 */
	protected function _key($key) {
		$key = $this->key($key);
		if (!$key) {
			throw new \InvalidArgumentException('An empty value is not valid as a cache key');
		}

		return $this->_config['prefix'] . $key;
	}

}
