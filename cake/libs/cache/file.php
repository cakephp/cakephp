<?php
/**
 * File Storage engine for cache
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

if (!class_exists('File')) {
	require LIBS . 'file.php';
}
/**
 * File Storage engine for cache
 *
 * @todo use the File and Folder classes (if it's not a too big performance hit)
 * @package       cake
 * @subpackage    cake.cake.libs.cache
 */
class FileEngine extends CacheEngine {

/**
 * Instance of File class
 *
 * @var File
 * @access protected
 */
	var $_File = null;

/**
 * Settings
 * 
 * - path = absolute path to cache directory, default => CACHE
 * - prefix = string prefix for filename, default => cake_
 * - lock = enable file locking on write, default => false
 * - serialize = serialize the data, default => true
 *
 * @var array
 * @see CacheEngine::__defaults
 * @access public
 */
	var $settings = array();

/**
 * True unless FileEngine::__active(); fails
 *
 * @var boolean
 * @access protected
 */
	var $_init = true;

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
		parent::init(array_merge(
			array(
				'engine' => 'File', 'path' => CACHE, 'prefix'=> 'cake_', 'lock'=> false,
				'serialize'=> true, 'isWindows' => false
			),
			$settings
		));
		if (!isset($this->_File)) {
			$this->_File =& new File($this->settings['path'] . DS . 'cake');
		}

		if (DIRECTORY_SEPARATOR === '\\') {
			$this->settings['isWindows'] = true;
		}

		$path = $this->_File->Folder->cd($this->settings['path']);
		if ($path) {
			$this->settings['path'] = $path;
		}
		return $this->__active();
	}

/**
 * Garbage collection. Permanently remove all expired and deleted data
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
		if ($data === '' || !$this->_init) {
			return false;
		}

		if ($this->_setKey($key) === false) {
			return false;
		}

		$lineBreak = "\n";

		if ($this->settings['isWindows']) {
			$lineBreak = "\r\n";
		}

		if (!empty($this->settings['serialize'])) {
			if ($this->settings['isWindows']) {
				$data = str_replace('\\', '\\\\\\\\', serialize($data));
			} else {
				$data = serialize($data);
			}
		}

		if ($this->settings['lock']) {
			$this->_File->lock = true;
		}
		$expires = time() + $duration;
		$contents = $expires . $lineBreak . $data . $lineBreak;
		$success = $this->_File->write($contents);
		$this->_File->close();
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
		if ($this->_setKey($key) === false || !$this->_init || !$this->_File->exists()) {
			return false;
		}
		if ($this->settings['lock']) {
			$this->_File->lock = true;
		}
		$time = time();
		$cachetime = intval($this->_File->read(11));

		if ($cachetime !== false && ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime)) {
			$this->_File->close();
			return false;
		}
		$data = $this->_File->read(true);

		if ($data !== '' && !empty($this->settings['serialize'])) {
			if ($this->settings['isWindows']) {
				$data = str_replace('\\\\\\\\', '\\', $data);
			}
			$data = unserialize((string)$data);
		}
		$this->_File->close();
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
		if ($this->_setKey($key) === false || !$this->_init) {
			return false;
		}
		return $this->_File->delete();
	}

/**
 * Delete all values from the cache
 *
 * @param boolean $check Optional - only delete expired cache items
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear($check) {
		if (!$this->_init) {
			return false;
		}
		$dir = dir($this->settings['path']);
		if ($check) {
			$now = time();
			$threshold = $now - $this->settings['duration'];
		}
		while (($entry = $dir->read()) !== false) {
			if ($this->_setKey($entry) === false) {
				continue;
			}
			if ($check) {
				$mtime = $this->_File->lastChange();

				if ($mtime === false || $mtime > $threshold) {
					continue;
				}

				$expires = $this->_File->read(11);
				$this->_File->close();

				if ($expires > $now) {
					continue;
				}
			}
			$this->_File->delete();
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
	function _setKey($key) {
		$this->_File->Folder->cd($this->settings['path']);
		if ($key !== $this->_File->name) {
			$this->_File->name = $key;
			$this->_File->path = null;
		}
		if (!$this->_File->Folder->inPath($this->_File->pwd(), true)) {
			return false;
		}
	}

/**
 * Determine is cache directory is writable
 *
 * @return boolean
 * @access private
 */
	function __active() {
		if ($this->_init && !is_writable($this->settings['path'])) {
			$this->_init = false;
			trigger_error(sprintf(__('%s is not writable', true), $this->settings['path']), E_USER_WARNING);
		}
		return true;
	}
}
