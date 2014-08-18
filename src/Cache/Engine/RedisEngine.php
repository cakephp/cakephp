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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;

/**
 * Redis storage engine for cache.
 *
 */
class RedisEngine extends CacheEngine {

/**
 * Redis wrapper.
 *
 * @var Redis
 */
	protected $_Redis = null;

/**
 * The default config used unless overriden by runtime configuration
 *
 * - `database` database number to use for connection.
 * - `duration` Specify how long items in this cache configuration last.
 * - `groups` List of groups or 'tags' associated to every key stored in this config.
 *    handy for deleting a complete group from cache.
 * - `password` Redis server password.
 * - `persistent` Connect to the Redis server with a persistent connection
 * - `port` port number to the Redis server.
 * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
 *    with either another cache config or another application.
 * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
 *    cache::gc from ever being called automatically.
 * - `server` URL or ip to the Redis server host.
 * - `timeout` timeout in seconds (float).
 * - `unix_socket` Path to the unix socket file (default: false)
 *
 * @var array
 */
	protected $_defaultConfig = [
		'database' => 0,
		'duration' => 3600,
		'groups' => [],
		'password' => false,
		'persistent' => true,
		'port' => 6379,
		'prefix' => null,
		'probability' => 100,
		'server' => '127.0.0.1',
		'timeout' => 0,
		'unix_socket' => false,
	];

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $config array of setting for the engine
 * @return bool True if the engine has been successfully initialized, false if not
 */
	public function init(array $config = []) {
		if (!class_exists('Redis')) {
			return false;
		}
		parent::init($config);

		return $this->_connect();
	}

/**
 * Connects to a Redis server
 *
 * @return bool True if Redis server was connected
 */
	protected function _connect() {
		$return = false;
		try {
			$this->_Redis = new \Redis();
			if (!empty($this->settings['unix_socket'])) {
				$return = $this->_Redis->connect($this->settings['unix_socket']);
			} elseif (empty($this->_config['persistent'])) {
				$return = $this->_Redis->connect($this->_config['server'], $this->_config['port'], $this->_config['timeout']);
			} else {
				$persistentId = $this->_config['port'] . $this->_config['timeout'] . $this->_config['database'];
				$return = $this->_Redis->pconnect($this->_config['server'], $this->_config['port'], $this->_config['timeout'], $persistentId);
			}
		} catch (\RedisException $e) {
			return false;
		}
		if ($return && $this->_config['password']) {
			$return = $this->_Redis->auth($this->_config['password']);
		}
		if ($return) {
			$return = $this->_Redis->select($this->_config['database']);
		}
		return $return;
	}

/**
 * Write data for key into cache.
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @return bool True if the data was successfully cached, false on failure
 */
	public function write($key, $value) {
		$key = $this->_key($key);

		if (!is_int($value)) {
			$value = serialize($value);
		}

		$duration = $this->_config['duration'];
		if ($duration === 0) {
			return $this->_Redis->set($key, $value);
		}

		return $this->_Redis->setex($key, $duration, $value);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		$key = $this->_key($key);

		$value = $this->_Redis->get($key);
		if (ctype_digit($value)) {
			$value = (int)$value;
		}
		if ($value !== false && is_string($value)) {
			$value = unserialize($value);
		}
		return $value;
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to increment
 * @return bool|int New incremented value, false otherwise
 */
	public function increment($key, $offset = 1) {
		$key = $this->_key($key);

		return (int)$this->_Redis->incrBy($key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to subtract
 * @return bool|int New decremented value, false otherwise
 */
	public function decrement($key, $offset = 1) {
		$key = $this->_key($key);

		return (int)$this->_Redis->decrBy($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		$key = $this->_key($key);

		return $this->_Redis->delete($key) > 0;
	}

/**
 * Delete all keys from the cache
 *
 * @param bool $check If true will check expiration, otherwise delete all.
 * @return bool True if the cache was successfully cleared, false otherwise
 */
	public function clear($check) {
		if ($check) {
			return true;
		}
		$keys = $this->_Redis->getKeys($this->_config['prefix'] . '*');
		$this->_Redis->del($keys);

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
		$result = [];
		foreach ($this->_config['groups'] as $group) {
			$value = $this->_Redis->get($this->_config['prefix'] . $group);
			if (!$value) {
				$value = 1;
				$this->_Redis->set($this->_config['prefix'] . $group, $value);
			}
			$result[] = $group . $value;
		}
		return $result;
	}

/**
 * Increments the group value to simulate deletion of all keys under a group
 * old values will remain in storage until they expire.
 *
 * @param string $group name of the group to be cleared
 * @return bool success
 */
	public function clearGroup($group) {
		return (bool)$this->_Redis->incr($this->_config['prefix'] . $group);
	}

/**
 * Disconnects from the redis server
 */
	public function __destruct() {
		if (empty($this->_config['persistent']) && $this->_Redis instanceof \Redis) {
			$this->_Redis->close();
		}
	}
}
