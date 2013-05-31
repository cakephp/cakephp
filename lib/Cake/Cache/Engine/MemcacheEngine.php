<?php
/**
 * Memcache storage engine for cache
 *
 *
 * PHP 5
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
 * Memcache storage engine for cache. Memcache has some limitations in the amount of
 * control you have over expire times far in the future. See MemcacheEngine::write() for
 * more information.
 *
 * @package       Cake.Cache.Engine
 */
class MemcacheEngine extends CacheEngine {

/**
 * Contains the compiled group names
 * (prefixed with the global configuration prefix)
 *
 * @var array
 */
	protected $_compiledGroupNames = array();

/**
 * Memcache wrapper.
 *
 * @var Memcache
 */
	protected $_Memcache = null;

/**
 * Settings
 *
 *  - servers = string or array of memcache servers, default => 127.0.0.1. If an
 *    array MemcacheEngine will use them as a pool.
 *  - compress = boolean, default => false
 *
 * @var array
 */
	public $settings = array();

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $settings array of setting for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 */
	public function init($settings = array()) {
		if (!class_exists('Memcache')) {
			return false;
		}
		if (!isset($settings['prefix'])) {
			$settings['prefix'] = Inflector::slug(APP_DIR) . '_';
		}
		$settings += array(
			'engine' => 'Memcache',
			'servers' => array('127.0.0.1'),
			'compress' => false,
			'persistent' => true
		);
		parent::init($settings);

		if ($this->settings['compress']) {
			$this->settings['compress'] = MEMCACHE_COMPRESSED;
		}
		if (is_string($this->settings['servers'])) {
			$this->settings['servers'] = array($this->settings['servers']);
		}
		if (!isset($this->_Memcache)) {
			$return = false;
			$this->_Memcache = new Memcache();
			foreach ($this->settings['servers'] as $server) {
				list($host, $port) = $this->_parseServerString($server);
				if ($this->_Memcache->addServer($host, $port, $this->settings['persistent'])) {
					$return = true;
				}
			}
			return $return;
		}
		return true;
	}

/**
 * Parses the server address into the host/port. Handles both IPv6 and IPv4
 * addresses and Unix sockets
 *
 * @param string $server The server address string.
 * @return array Array containing host, port
 */
	protected function _parseServerString($server) {
		if ($server[0] === 'u') {
			return array($server, 0);
		}
		if (substr($server, 0, 1) === '[') {
			$position = strpos($server, ']:');
			if ($position !== false) {
				$position++;
			}
		} else {
			$position = strpos($server, ':');
		}
		$port = 11211;
		$host = $server;
		if ($position !== false) {
			$host = substr($server, 0, $position);
			$port = substr($server, $position + 1);
		}
		return array($host, $port);
	}

/**
 * Write data for key into cache. When using memcache as your cache engine
 * remember that the Memcache pecl extension does not support cache expiry times greater
 * than 30 days in the future. Any duration greater than 30 days will be treated as never expiring.
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param integer $duration How long to cache the data, in seconds
 * @return boolean True if the data was successfully cached, false on failure
 * @see http://php.net/manual/en/memcache.set.php
 */
	public function write($key, $value, $duration) {
		if ($duration > 30 * DAY) {
			$duration = 0;
		}
		return $this->_Memcache->set($key, $value, $this->settings['compress'], $duration);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		return $this->_Memcache->get($key);
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to increment
 * @return New incremented value, false otherwise
 * @throws CacheException when you try to increment with compress = true
 */
	public function increment($key, $offset = 1) {
		if ($this->settings['compress']) {
			throw new CacheException(
				__d('cake_dev', 'Method increment() not implemented for compressed cache in %s', __CLASS__)
			);
		}
		return $this->_Memcache->increment($key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to subtract
 * @return New decremented value, false otherwise
 * @throws CacheException when you try to decrement with compress = true
 */
	public function decrement($key, $offset = 1) {
		if ($this->settings['compress']) {
			throw new CacheException(
				__d('cake_dev', 'Method decrement() not implemented for compressed cache in %s', __CLASS__)
			);
		}
		return $this->_Memcache->decrement($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		return $this->_Memcache->delete($key);
	}

/**
 * Delete all keys from the cache
 *
 * @param boolean $check
 * @return boolean True if the cache was successfully cleared, false otherwise
 */
	public function clear($check) {
		if ($check) {
			return true;
		}
		foreach ($this->_Memcache->getExtendedStats('slabs') as $slabs) {
			foreach (array_keys($slabs) as $slabId) {
				if (!is_numeric($slabId)) {
					continue;
				}

				foreach ($this->_Memcache->getExtendedStats('cachedump', $slabId) as $stats) {
					if (!is_array($stats)) {
						continue;
					}
					foreach (array_keys($stats) as $key) {
						if (strpos($key, $this->settings['prefix']) === 0) {
							$this->_Memcache->delete($key);
						}
					}
				}
			}
		}
		return true;
	}

/**
 * Connects to a server in connection pool
 *
 * @param string $host host ip address or name
 * @param integer $port Server port
 * @return boolean True if memcache server was connected
 */
	public function connect($host, $port = 11211) {
		if ($this->_Memcache->getServerStatus($host, $port) === 0) {
			if ($this->_Memcache->connect($host, $port)) {
				return true;
			}
			return false;
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

		$groups = $this->_Memcache->get($this->_compiledGroupNames);
		if (count($groups) !== count($this->settings['groups'])) {
			foreach ($this->_compiledGroupNames as $group) {
				if (!isset($groups[$group])) {
					$this->_Memcache->set($group, 1, false, 0);
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
 * @return boolean success
 */
	public function clearGroup($group) {
		return (bool)$this->_Memcache->increment($this->settings['prefix'] . $group);
	}
}
