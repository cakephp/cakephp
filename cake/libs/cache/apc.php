<?php
/* SVN FILE: $Id$ */
/**
 * APC storage engine for cache.
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
 * @since			CakePHP(tm) v 1.2.0.4933
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * APC storage engine for cache
 *
 * @package		cake
 * @subpackage	cake.cake.libs.cache
 */
class APCEngine extends CacheEngine {
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params	Associative array of parameters for the engine
 * @return boolean	True if the engine has been succesfully initialized, false if not
 * @access public
 */
	function init(&$params) {
		return function_exists('apc_cache_info');
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
		return apc_store($key, $value, $duration);
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		return apc_fetch($key);
	}
/**
 * Delete a value from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return apc_delete($key);
	}
/**
 * Delete all values from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		return apc_clear_cache('user');
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
}
?>