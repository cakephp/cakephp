<?php
/* SVN FILE: $Id$ */
/**
 * Xcache storage engine for cache.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.cache
 * @since			CakePHP(tm) v 1.2.0.4947
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Xcache storage engine for cache
 *
 * @package		cake
 * @subpackage	cake.cake.libs.cache
 */
class XcacheEngine extends CacheEngine {
/**
 * Admin username (xcache.admin.user)
 *
 * @var string
 * @access private
 */
	var $_php_auth_user = '';
/**
 * Plaintext password for basic auth (xcache.admin.pass)
 *
 * @var string
 * @access private
 */
	var $_php_auth_pw = '';
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params	Associative array of parameters for the engine
 * @return boolean	True if the engine has been succesfully initialized, false if not
 * @access public
 */
	function init($params) {
		$this->_php_auth_user = $params['user'];
		$this->_php_auth_pw = $params['password'];
		return function_exists('xcache_info');
	}
/**
 * Write a value in the cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param int $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$value, $duration = CACHE_DEFAULT_DURATION) {
		return xcache_set($key, $value, $duration);
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		if(xcache_isset($key)) {
			return xcache_get($key);
		}
		return false;
	}
/**
 * Delete a value from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return xcache_unset($key);
	}
/**
 * Delete all values from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		$result = true;
		$this->_phpAuth();

		for($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if(!xcache_clear_cache(XC_TYPE_VAR, $i)) {
				$result = false;
				break;
			}
		}
		$this->_phpAuth(true);
		return $result;
	}
/**
 * Return the settings for this cache engine
 *
 * @return array list of settings for this engine
 * @access public
 */
	function settings() {
		return array('class' => get_class($this));
	}
/**
 * Makes necessary changes (and reverting them back) in $_SERVER
 *
 * This has to be done because xcache_clear_cache() needs pass Basic Auth
 * (see xcache.admin configuration settings)
 *
 * @param boolean	Revert changes
 * @access private
 */
	function _phpAuth($reverse = false) {
		static $backup = array();
		$keys = array('PHP_AUTH_USER', 'PHP_AUTH_PW');

		foreach($keys as $key) {
			if($reverse) {
				if(isset($backup[$key])) {
					$_SERVER[$key] = $backup[$key];
					unset($backup[$key]);
				} else {
					unset($_SERVER[$key]);
				}
			} else {
				$value = env($key);
				if(!empty($value)) {
					$backup[$key] = $value;
				}
				$varName = '_' . low($key);
				$_SERVER[$key] = $this->{$varName};
			}
		}
	}
}
?>