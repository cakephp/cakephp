<?php
/**
 * Memcache storage engine for cache
 *
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Memcache storage engine for cache.  Memcache has some limitations in the amount of 
 * control you have over expire times far in the future.  See MemcacheEngine::write() for
 * more information.
 *
 * @package       cake.libs.cache
 */
class MemcacheEngine extends CacheEngine {

/**
 * Memcache wrapper.
 *
 * @var Memcache
 * @access private
 */
	private $__Memcache = null;

/**
 * Settings
 *
 *  - servers = string or array of memcache servers, default => 127.0.0.1. If an
 *    array MemcacheEngine will use them as a pool.
 *  - compress = boolean, default => false
 *
 * @var array
 * @access public
 */
	public $settings = array();

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $setting array of setting for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 */
	public function init($settings = array()) {
		if (!class_exists('Memcache')) {
			return false;
		}
		parent::init(array_merge(array(
			'engine'=> 'Memcache', 
			'prefix' => Inflector::slug(APP_DIR) . '_', 
			'servers' => array('127.0.0.1'),
			'compress'=> false
			), $settings)
		);

		if ($this->settings['compress']) {
			$this->settings['compress'] = MEMCACHE_COMPRESSED;
		}
		if (!is_array($this->settings['servers'])) {
			$this->settings['servers'] = array($this->settings['servers']);
		}
		if (!isset($this->__Memcache)) {
			$return = false;
			$this->__Memcache = new Memcache();
			foreach ($this->settings['servers'] as $server) {
				list($host, $port) = $this->_parseServerString($server);
				if ($this->__Memcache->addServer($host, $port)) {
					$return = true;
				}
			}
			return $return;
		}
		return true;
	}

/**
 * Parses the server address into the host/port.  Handles both IPv6 and IPv4
 * addresses
 *
 * @param string $server The server address string.
 * @return array Array containing host, port
 */
	function _parseServerString($server) {
		if (substr($server, 0, 1) == '[') {
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
 * Write data for key into cache.  When using memcache as your cache engine
 * remember that the Memcache pecl extension does not support cache expiry times greater 
 * than 30 days in the future. If you wish to create cache entries that do not expire, set the duration
 * to `0` in your cache configuration.
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param integer $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @see http://php.net/manual/en/memcache.set.php
 */
	public function write($key, $value, $duration) {
		return $this->__Memcache->set($key, $value, $this->settings['compress'], $duration);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		return $this->__Memcache->get($key);
	}

/**
 * Increments the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to increment
 * @param integer $duration How long to cache the data, in seconds
 * @return New incremented value, false otherwise
 * @throws CacheException when you try to increment with compress = true
 */
	public function increment($key, $offset = 1) {
		if ($this->settings['compress']) {
			throw new CacheException(
				__('Method increment() not implemented for compressed cache in %s', __CLASS__)
			);
		}
		return $this->__Memcache->increment($key, $offset);
	}

/**
 * Decrements the value of an integer cached key
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to substract
 * @param integer $duration How long to cache the data, in seconds
 * @return New decremented value, false otherwise
 * @throws CacheException when you try to decrement with compress = true
 */
	public function decrement($key, $offset = 1) {
		if ($this->settings['compress']) {
			throw new CacheException(
				__('Method decrement() not implemented for compressed cache in %s', __CLASS__)
			);
		}
		return $this->__Memcache->decrement($key, $offset);
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		return $this->__Memcache->delete($key);
	}

/**
 * Delete all keys from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 */
	public function clear($check) {
		return $this->__Memcache->flush();
	}

/**
 * Connects to a server in connection pool
 *
 * @param string $host host ip address or name
 * @param integer $port Server port
 * @return boolean True if memcache server was connected
 */
	public function connect($host, $port = 11211) {
		if ($this->__Memcache->getServerStatus($host, $port) === 0) {
			if ($this->__Memcache->connect($host, $port)) {
				return true;
			}
			return false;
		}
		return true;
	}
}
