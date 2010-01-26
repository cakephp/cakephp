<?php
/* SVN FILE: $Id$ */
/**
 * Memcache storage engine for cache
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
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Memcache storage engine for cache
 *
 * @package       cake
 * @subpackage    cake.cake.libs.cache
 */
class MemcacheEngine extends CacheEngine {
/**
 * Memcache wrapper.
 *
 * @var Memcache
 * @access private
 */
	var $__Memcache = null;
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
	var $settings = array();
/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $setting array of setting for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 * @access public
 */
	function init($settings = array()) {
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
			$this->__Memcache =& new Memcache();
			foreach ($this->settings['servers'] as $server) {
				$parts = explode(':', $server);
				$host = $parts[0];
				$port = 11211;
				if (isset($parts[1])) {
					$port = $parts[1];
				}
				if ($this->__Memcache->addServer($host, $port)) {
					$return = true;
				}
			}
			return $return;
		}
		return true;
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
		$this->__Memcache->set($key . '_expires', $expires, $this->settings['compress'], $expires);
		return $this->__Memcache->set($key, $value, $this->settings['compress'], $expires);
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
		$cachetime = intval($this->__Memcache->get($key . '_expires'));
		if ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime) {
			return false;
		}
		return $this->__Memcache->get($key);
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return $this->__Memcache->delete($key);
	}
/**
 * Delete all keys from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		return $this->__Memcache->flush();
	}
/**
 * Connects to a server in connection pool
 *
 * @param string $host host ip address or name
 * @param integer $port Server port
 * @return boolean True if memcache server was connected
 * @access public
 */
	function connect($host, $port = 11211) {
		if ($this->__Memcache->getServerStatus($host, $port) === 0) {
			if ($this->__Memcache->connect($host, $port)) {
				return true;
			}
			return false;
		}
		return true;
	}
}
?>