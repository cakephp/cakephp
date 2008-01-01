<?php
/* SVN FILE: $Id$ */
/**
 * Database Storage engine for cache
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
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
 * Database Storage engine for cache
 *
 * @package		cake
 * @subpackage	cake.cake.libs.cache
 */
class ModelEngine extends CacheEngine {
/**
 * settings
 * 		className = name of the model to use, default => Cache
 * 		fields = database fields that hold data and ttl, default => data, expires
 *
 * @var array
 * @access public
 */
	var $settings = array();

/**
 * Model instance.
 *
 * @var object
 * @access private
 */
	var $__Model = null;
/**
 * Model instance.
 *
 * @var object
 * @access private
 */
	var $__fields = array();
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
		parent::init($settings);
		$defaults = array('className'=> 'CacheModel', 'fields'=> array('data', 'expires'));
		$this->settings = array_merge($this->settings, $defaults, $settings);
		$className = $this->settings['className'];
		$this->__fields = $this->settings['fields'];
		if (App::import($className)) {
			$this->__Model = ClassRegistry::init($className);
		} else {
			$this->__Model = new Model(array('name' => $className));
		}
		return true;
	}
/**
 * Garbage collection. Permanently remove all expired and deleted data
 *
 * @access public
 */
	function gc() {
		return $this->__Model->deleteAll(array($this->__fields[1] => '<= '.time()));
	}
/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $data Data to be cached
 * @param integer $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$data, $duration) {
		if (isset($this->settings['serialize'])) {
			$data = serialize($data);
		}

		if (!$data) {
			return false;
		}

		$cache = array('id' => $key,
						$this->__fields[0] => $data,
						$this->__fields[1] => time() + $duration
					);
		$result = false;
		if ($this->__Model->save($cache)) {
			$result = true;
		}
		return $result;
	}
/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		$data = $this->__Model->field($this->__fields[0], array($this->__Model->primaryKey => $key, $this->__fields[1] => '> '.time()));
		if (!$data) {
			return false;
		}
		if (isset($this->settings['serialize'])) {
		 	return unserialize($data);
		}
		return $data;
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return $this->__Model->del($key);
	}
/**
 * Delete all keys from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		return $this->__Model->deleteAll('1=1');
	}

}
?>