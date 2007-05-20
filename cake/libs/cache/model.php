<?php
/* SVN FILE: $Id$ */
/**
 * Database Storage engine for cache
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
 * Database Storage engine for cache
 *
 * @todo Not Implemented
 * @package		cake
 * @subpackage	cake.cake.libs.cache
 */
class ModelEngine extends CacheEngine {
/**
 * Model instance.
 *
 * @var object
 * @access private
 */
	var $_Model = null;
/**
 * Fields that holds data.
 *
 * @var string
 * @access private
 */
	var $_dataField = '';
/**
 * Field that holds expiration information.
 *
 * @var string
 * @access private
 */

	var $_expiryField = '';
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @todo does not work will return false
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 */
	function init($params) {
		return false;

		$modelName = 'DbCache';
		$dataField = 'value';
		$expiryField = 'expires';
		extract($params);

		if(!class_exists($modelName) && !loadModel($modelName)) {
			return false;
		}
		$this->_Model = new $modelName;
	}
/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 *
 * @access public
 */
	function gc() {
		return $this->_Model->deleteAll(array($this->_expiryField => '<= '.time()));
	}
/**
 * Write a value in the cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$value, $duration = CACHE_DEFAULT_DURATION) {
		$serialized = serialize($value);

		if(!$serialized) {
			return false;
		}
		$data = array($this->_Model->name => array(
							$this->_dataField => $serialized,
							$this->_expiryField => time() + $duration));

		$oldId = $this->_Model->id;
		$this->_Model->id = $key;
		$res = $this->_Model->save($data);
		$this->_Model->id = $oldId;
		return $res;
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		$val = $this->_Model->field($this->_expiryField, array($this->_Model->primaryKey => $key, $this->_expiryField => '> '.time()));
		return ife($val, unserialize($val), false);
	}
/**
 * Delete a value from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		return $this->_Model->del($key);
	}
/**
 * Delete all values from the cache
 *
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear() {
		return $this->_Model->deleteAll(null);
	}
/**
 * Return the settings for this cache engine
 *
 * @return array list of settings for this engine
 * @access public
 */
	function settings() {
		$class = null;
		if(is_a($this->_Model, 'Model')) {
			$class = get_class($this->_Model);
		}
		return array('class' => get_class($this),
						'modelName' => $class,
						'dataField' => $this->_dataField,
						'expiryField' => $this->_expiryField);
	}
}
?>