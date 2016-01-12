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
 * @since         CakePHP(tm) v 2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Memcached storage engine for cache. Memcached has some limitations in the amount of
 * control you have over expire times far in the future. See MemcachedEngine::write() for
 * more information.
 *
 * Main advantage of this Memcached engine over the memcached engine is
 * support of binary protocol, and igbibnary serialization
 * (if memcached extension compiled with --enable-igbinary)
 * Compressed keys can also be incremented/decremented
 *
 * @package       Cake.Cache.Engine
 */
class MemcachedEngine extends CacheEngine {

/**
 * memcached wrapper.
 *
 * @var Memcache
 */
	protected $_Memcached = null;

/**
 * Settings
 *
 *  - servers = string or array of memcached servers, default => 127.0.0.1. If an
 *    array MemcacheEngine will use them as a pool.
 *  - compress = boolean, default => false
 *  - persistent = string The name of the persistent connection. All configurations using
 *    the same persistent value will share a single underlying connection.
 *  - serialize = string, default => php. The serializer engine used to serialize data.
 *    Available engines are php, igbinary and json. Beside php, the memcached extension
 *    must be compiled with the appropriate serializer support.
 *  - options - Additional options for the memcached client. Should be an array of option => value.
 *    Use the Memcached::OPT_* constants as keys.
 *
 * @var array
 */
	public $settings = array();

/**
 * List of available serializer engines
 *
 * Memcached must be compiled with json and igbinary support to use these engines
 *
 * @var array
 */
	protected $_serializers = array(
		'igbinary' => Memcached::SERIALIZER_IGBINARY,
		'json' => Memcached::SERIALIZER_JSON,
		'php' => Memcached::SERIALIZER_PHP
	);

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $settings array of setting for the engine
 * @return bool True if the engine has been successfully initialized, false if not
 * @throws CacheException when you try use authentication without Memcached compiled with SASL support
 */
	public function init($settings = array()) {
		if (!class_exists('Memcached')) {
			return false;
		}
		if (!isset($settings['prefix'])) {
			$settings['prefix'] = Inflector::slug(APP_DIR) . '_';
		}

		if (defined('Memcached::HAVE_MSGPACK') && Memcached::HAVE_MSGPACK) {
			$this->_serializers['msgpack'] = Memcached::SERIALIZER_MSGPACK;
		}

		$settings += array(
			'engine' => 'Memcached',
			'servers' => array('127.0.0.1'),
			'compress' => false,
			'persistent' => false,
			'login' => null,
			'password' => null,
			'serialize' => 'php',
			'options' => array()
		);
		parent::init($settings);

		if (!is_array($this->settings['servers'])) {
			$this->settings['servers'] = array($this->settings['servers']);
		}

		if (isset($this->_Memcached)) {
			return true;
		}

		if (!$this->settings['persistent']) {
			$this->_Memcached = new Memcached();
		} else {
			$this->_Memcached = new Memcached((string)$this->settings['persistent']);
		}
		$this->_setOptions();

		if (count($this->_Memcached->getServerList())) {
			return true;
		}

		$servers = array();
		foreach ($this->settings['servers'] as $server) {
			$servers[] = $this->_parseServerString($server);
		}

		if (!$this->_Memcached->addServers($servers)) {
			return false;
		}

		if ($this->settings['login'] !== null && $this->settings['password'] !== null) {
			if (!method_exists($this->_Memcached, 'setSaslAuthData')) {
				throw new CacheException(
					__d('cake_dev', 'Memcached extension is not build with SASL support')
				);
			}
			$this->_Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
			$this->_Memcached->setSaslAuthData($this->settings['login'], $this->settings['password']);
		}
		if (is_array($this->settings['options'])) {
			foreach ($this->settings['options'] as $opt => $value) {
				$this->_Memcached->setOption($opt, $value);
			}
		}

		return true;
	}

/**
 * Settings the memcached instance
 *
 * @throws CacheException when the Memcached extension is not built with the desired serializer engine
 * @return void
 */
	protected function _setOptions() {
		$this->_Memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

		$serializer = strtolower($this->settings['serialize']);
		if (!isset($this->_serializers[$serializer])) {
			throw new CacheException(
				__d('cake_dev', '%s is not a valid serializer engine for Memcached', $serializer)
			);
		}

		if ($serializer !== 'php' && !constant('Memcached::HAVE_' . strtoupper($serializer))) {
			throw new CacheException(
				__d('cake_dev', 'Memcached extension is not compiled with %s support', $serializer)
			);
		}

		$this->_Memcached->setOption(Memcached::OPT_SERIALIZER, $this->_serializers[$serializer]);

		// Check for Amazon ElastiCache instance
		if (defined('Memcached::OPT_CLIENT_MODE') && defined('Memcached::DYNAMIC_CLIENT_MODE')) {
			$this->_Memcached->setOption(Memcached::OPT_CLIENT_MODE, Memcached::DYNAMIC_CLIENT_MODE);
		}

		$this->_Memcached->setOption(Memcached::OPT_COMPRESSION, (bool)$this->settings['compress']);
	}

/**
 * Parses the server address into the host/port. Handles both IPv6 and IPv4
 * addresses and Unix sockets
 *
 * @param string $server The server address string.
 * @return array Array containing host, port
 */
	protected function _parseServerString($server) {
		$socketTransport = 'unix://';
		if (strpos($server, $socketTransport) === 0) {
			return array(substr($server, strlen($socketTransport)), 0);
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
		return array($host, (int)$port);
	}

/**
 * Write data for key into cache. When using memcached as your cache engine
 * remember that the Memcached pecl extension does not support cache expiry times greater
 * than 30 days in the future. Any duration greater than 30 days will be treated as never expiring.
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param int $duration How long to cache the data, in seconds
 * @return bool True if the data was successfully cached, false on failure
 * @see http://php.net/manual/en/memcache.set.php
 */
	public function write($key, $value, $duration) {
		if ($duration > 30 * DAY) {
			$duration = 0;
		}

		return $this->_Memcached->set($key, $value, $duration);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		return $this->_Memcached->get($key);
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to increment
 * @return New incremented value, false otherwise
 * @throws CacheException when you try to increment with compress = true
 */
	public function increment($key, $offset = 1) {
		return $this->_Memcached->increment($key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param int $offset How much to subtract
 * @return New decremented value, false otherwise
 * @throws CacheException when you try to decrement with compress = true
 */
	public function decrement($key, $offset = 1) {
		return $this->_Memcached->decrement($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		return $this->_Memcached->delete($key);
	}

/**
 * Delete all keys from the cache
 *
 * @param bool $check If true no deletes will occur and instead CakePHP will rely
 *   on key TTL values.
 * @return bool True if the cache was successfully cleared, false otherwise. Will
 *   also return false if you are using a binary protocol.
 */
	public function clear($check) {
		if ($check) {
			return true;
		}

		$keys = $this->_Memcached->getAllKeys();
		if ($keys === false) {
			return false;
		}

		foreach ($keys as $key) {
			if (strpos($key, $this->settings['prefix']) === 0) {
				$this->_Memcached->delete($key);
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

		$groups = $this->_Memcached->getMulti($this->_compiledGroupNames);
		if (count($groups) !== count($this->settings['groups'])) {
			foreach ($this->_compiledGroupNames as $group) {
				if (!isset($groups[$group])) {
					$this->_Memcached->set($group, 1, 0);
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
		return (bool)$this->_Memcached->increment($this->settings['prefix'] . $group);
	}
}
