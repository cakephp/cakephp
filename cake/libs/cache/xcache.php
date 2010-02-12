<?php
/* SVN FILE: $Id$ */
/**
 * Xcache storage engine for cache.
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
 * @since         CakePHP(tm) v 1.2.0.4947
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Xcache storage engine for cache
 *
 * @link          http://trac.lighttpd.net/xcache/ Xcache
 * @package       cake
 * @subpackage    cake.cake.libs.cache
 */
class XcacheEngine extends CacheEngine {
/**
 * settings
 * 		PHP_AUTH_USER = xcache.admin.user, default cake
 * 		PHP_AUTH_PW = xcache.admin.password, default cake
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
	function init($settings) {
		parent::init(array_merge(array(
			'engine' => 'Xcache', 'prefix' => Inflector::slug(APP_DIR) . '_', 'PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password'
			), $settings)
		);
		return function_exists('xcache_info');
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
		xcache_set($key.'_expires', $expires, $duration);
		return xcache_set($key, $value, $duration);
	}
/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		if (xcache_isset($key)) {
			$time = time();
			$cachetime = intval(xcache_get($key.'_expires'));
			if ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime) {
				return false;
			}
			return xcache_get($key);
		}
		return false;
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return xcache_unset($key);
	}
/**
 * Delete all keys from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		$this->__auth();
		$max = xcache_count(XC_TYPE_VAR);
		for ($i = 0; $i < $max; $i++) {
			xcache_clear_cache(XC_TYPE_VAR, $i);
		}
		$this->__auth(true);
		return true;
	}
/**
 * Populates and reverses $_SERVER authentication values
 * Makes necessary changes (and reverting them back) in $_SERVER
 *
 * This has to be done because xcache_clear_cache() needs to pass Basic Http Auth
 * (see xcache.admin configuration settings)
 *
 * @param boolean Revert changes
 * @access private
 */
	function __auth($reverse = false) {
		static $backup = array();
		$keys = array('PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password');
		foreach ($keys as $key => $setting) {
			if ($reverse) {
				if (isset($backup[$key])) {
					$_SERVER[$key] = $backup[$key];
					unset($backup[$key]);
				} else {
					unset($_SERVER[$key]);
				}
			} else {
				$value = env($key);
				if (!empty($value)) {
					$backup[$key] = $value;
				}
				if (!empty($this->settings[$setting])) {
					$_SERVER[$key] = $this->settings[$setting];
				} else if (!empty($this->settings[$key])) {
					$_SERVER[$key] = $this->settings[$key];
				} else {
					$_SERVER[$key] = $value;
				}
			}
		}
	}
}
?>