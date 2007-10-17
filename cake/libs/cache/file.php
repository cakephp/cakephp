<?php
/* SVN FILE: $Id$ */
/**
 * File Storage engine for cache
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
 * Included libraries.
 *
 */
if (!class_exists('file')) {
	uses ('file');
}
/**
 * File Storage engine for cache
 *
 * @todo use the File and Folder classes (if it's not a too big performance hit)
 * @package		cake
 * @subpackage	cake.cake.libs.cache
 */
class FileEngine extends CacheEngine {
/**
 * instance of File class
 *
 * @var string
 * @access private
 */
	var $__File = null;
/**
 * settings
 * 		path = absolute path to cache directory, default => CACHE
 * 		prefix = string prefix for filename, default => cake_
 * 		lock = enable file locking on write, default => false
 * 		serialize = serialize the data, default => true
 *
 * @see var __defaults
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
		parent::init($settings);
		$defaults = array('path' => CACHE, 'prefix'=> 'cake_', 'lock'=> false, 'serialize'=> true);
		$this->settings = am($defaults, $this->settings, $settings);
		if(!isset($this->__File)) {
			$this->__File =& new File($this->settings['path'] . DS . 'cake');
		}
		$this->settings['path'] = $this->__File->Folder->cd($this->settings['path']);
		if (!is_writable($this->settings['path'])) {
			return false;
		}
		return true;
	}
/**
 * Garbage collection
 * Permanently remove all expired and deleted data
 *
 * @return boolean True if garbage collection was succesful, false on failure
 * @access public
 */
	function gc() {
		return $this->clear(true);
	}
/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $data Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$data, $duration) {
		if (empty($data)) {
			return false;
		}

		if($this->__setKey($key) === false) {
			return false;
		}

		if ($duration == null) {
			$duration = $this->settings['duration'];
		}
		if (!empty($this->settings['serialize'])) {
			$data = serialize($data);
		}

		if ($this->settings['lock']) {
			$this->__File->lock = true;
		}

		$expires = time() + $duration;

		$contents = $expires."\n".$data."\n";

		$success = $this->__File->write($contents);
		$this->__File->close();
		return $success;
	}
/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		if($this->__setKey($key) === false) {
			return false;
		}
		if ($this->settings['lock']) {
			$this->__File->lock = true;
		}
		$cachetime = $this->__File->read(11);

		if ($cachetime !== false && intval($cachetime) < time()) {
			$this->__File->close();
			$this->__File->delete();
			return false;
		}
		$data = $this->__File->read(true);
		if (!empty($data) && !empty($this->settings['serialize'])) {
			$data = unserialize($data);
		}
		$this->__File->close();
		return $data;
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
		if($this->__setKey($key) === false) {
			return false;
		}
		return $this->__File->delete();
	}
/**
 * Delete all values from the cache
 *
 * @param boolean $check Optional - only delete expired cache items
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear($check) {
		$dir = dir($this->settings['path']);
		if ($check) {
			$now = time();
			$threshold = $now - $this->settings['duration'];
		}
		while (($entry = $dir->read()) !== false) {
			if($this->__setKey(str_replace($this->settings['prefix'], '', $entry)) === false) {
				continue;
			}
			if ($check) {
				$mtime = $this->__File->lastChange();

				if ($mtime === false || $mtime > $threshold) {
					continue;
				}

				$expires = $this->__File->read(11);
				$this->__File->close();

				if ($expires > $now) {
					continue;
				}
			}
			$this->__File->delete();
		}
		$dir->close();
		return true;
	}
/**
 * Get absolute file for a given key
 *
 * @param string $key The key
 * @return mixed Absolute cache file for the given key or false if erroneous
 * @access private
 */
	function __setKey($key) {
		$this->__File->Folder->cd($this->settings['path']);
		$this->__File->name = $this->settings['prefix'] . $key;
		if (!$this->__File->Folder->inPath($this->__File->pwd(), true)) {
			return false;
		}
	}
}
?>