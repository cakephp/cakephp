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
if (!class_exists('folder')) {
	uses ('folder');
}
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
 * instance of Folder class
 *
 * @var string
 * @access private
 */
	var $__Folder = null;
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
		$this->settings = am($this->settings, $defaults, $settings);
		$this->__Folder =& new Folder($this->settings['path']);
		$this->settings['path'] = $this->__Folder->pwd();
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
		if (!$data) {
			return false;
		}
		if ($duration == null) {
			$duration = $this->settings['duration'];
		}
		if (isset($this->settings['serialize'])) {
			$data = serialize($data);
		}
		if (!$data) {
			return false;
		}
		$file = $this->fullpath($key);
		if ($file === false) {
			return false;
		}
		$expires = time() + $duration;
		return $this->__write($file, $data, $expires);
	}
/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		$file = $this->fullpath($key);
		if ($file === false || !is_file($file) || !is_readable($file)) {
			return false;
		}
		$fp = fopen($file, 'r');
		if (!$fp) {
			return false;
		}
		if ($this->settings['lock'] && !flock($fp, LOCK_SH)) {
			return false;
		}
		$cachetime = fgets($fp, 11);
		if (intval($cachetime) < time()) {
			fclose($fp);
			unlink($file);
			return false;
		}
		$data = '';
		while (!feof($fp)) {
			$data .= fgets($fp, 4096);
		}
		$data = trim($data);
		if (isset($this->settings['serialize'])) {
			return unserialize($data);
		}
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
		$file = $this->fullpath($key);
		if ($file === false) {
			return false;
		}
		return unlink($file);
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
			$threshold = $now - 86400;
		}
		while (($entry = $dir->read()) !== false) {
			if (strpos($entry, $this->settings['prefix']) !== 0) {
				continue;
			}
			$file = $this->settings['path'] . $entry;

			if ($check) {
				$mtime = filemtime($file);

				if ($mtime === false || $mtime > $threshold) {
					continue;
				}
				$expires = $this->__expires($file);

				if ($expires > $now) {
					continue;
				}
			}
			unlink($file);
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
	function fullpath($key) {
		if (!isset($this->__File)) {
			$this->__File =& new File($this->settings['path']);
		}
		$parts = array_map(array($this->__File , 'safe'), explode(DS, $key));
		$key = array_pop($parts);
		$dir = implode(DS, $parts) . DS;
		$path = str_replace(DS . DS, DS, $this->settings['path'] . $dir);
		$fullpath = $this->__Folder->realpath($path . $this->settings['prefix'] . $key);
		if (!$this->__Folder->inPath($fullpath, true)) {
			return false;
		}
		return $fullpath;
	}
/**
 * write data to a file
 *
 * @param string $file
 * @param string $value
 * @param integer $expires
 * @return boolean True on success, false on failure
 * @access private
 */
	function __write(&$file, &$data, &$expires) {
		$dir = dirname($file);
		if (!is_writable($dir)) {
			if (!$this->__Folder->create($dir)) {
				return false;
			}
		}
		$contents = $expires."\n".$data."\n";
		return ife(file_put_contents($file, $contents, ife($this->settings['lock'], LOCK_EX, 0)), true, false);
	}
/**
 * Get the time to live for cache
 *
 * @param string $file
 * @return mixed Expiration timestamp, or false on failure
 * @access private
 */
	function __expires($file) {
		$fp = fopen($file, 'r');
		if (!$fp) {
			return false;
		}
		if ($this->settings['lock'] && !flock($fp, LOCK_SH)) {
			return false;
		}
		$expires = intval(fgets($fp, 11));
		fclose($fp);
		return $expires;
	}
}
?>