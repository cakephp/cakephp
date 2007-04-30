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
 * File Storage engine for cache
 *
 * @todo use the File and Folder classes (if it's not a too big performance hit)
 * @package		cake
 * @subpackage	cake.cake.libs.cache
 */
class FileEngine extends CacheEngine {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $_dir = '';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $_prefix = '';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $_lock = false;
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 */
	function init($params) {
		$dir = CACHE;
		$prefix = 'cake_';
		$lock = false;
		extract($params);
		$dir = trim($dir);

		if(strlen($dir) < 1) {
			return false;
		}

		if($dir{0} != DS) {
			return false;
		}

		if(!is_writable($dir)) {
			return false;
		}

		if($dir{strlen($dir)-1} != DS) {
			$dir .= DS;
		}
		$this->_dir = $dir;
		$this->_prefix = strval($prefix);
		$this->_lock = $lock;
		return true;
	}
/**
 * Garbage collection
 * Permanently remove all expired and deleted data
 *
 * @return boolean
 */
	function gc() {
		return $this->clear(true);
	}
/**
 * Write a value in the cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 */
	function write($key, &$value, $duration = CACHE_DEFAULT_DURATION) {
		$serialized = serialize($value);

		if(!$serialized) {
			return false;
		}
		$expires = time() + $duration;
		return $this->_writeCache($this->_getFilename($key), $serialized, $expires);
	}
/**
 * Get absolute filename for a key
 *
 * @param string $key The key
 * @return string Absolute cache filename for the given key
 */
	function _getFilename($key) {
		return $this->_dir . $this->_prefix . $this->base64url_encode($key);
	}
/**
 * write serialized data to a file
 *
 * @param unknown_type $filename
 * @param unknown_type $value
 * @param unknown_type $expires
 * @return unknown
 */
	function _writeCache(&$filename, &$value, &$expires) {
		$contents = $expires."\n".$value."\n";
		return ife(file_put_contents($filename, $contents, ife($this->_lock, LOCK_EX, 0)), true, false);
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	function read($key) {
		$filename = $this->_getFilename($key);

		if(!is_file($filename) || !is_readable($filename)) {
			return false;
		}
		$fp = fopen($filename, 'r');

		if(!$fp) {
			return false;
		}

		if($this->_lock && !flock($fp, LOCK_SH)) {
			return false;
		}
		$expires = fgets($fp, 11);

		if(intval($expires) < time()) {
			fclose($fp);
			unlink($filename);
			return false;
		}
		$data = '';

		while(!feof($fp)) {
			$data .= fgets($fp, 4096);
		}
		$data = trim($data);
		return unserialize($data);
	}
/**
 * Get the expiry time for a cache file
 *
 * @param unknown_type $filename
 * @return unknown
 */
	function _getExpiry($filename) {
		$fp = fopen($filename, 'r');

		if(!$fp) {
			return false;
		}

		if($this->_lock && !flock($fp, LOCK_SH)) {
			return false;
		}
		$expires = intval(fgets($fp, 11));
		fclose($fp);
		return $expires;
	}
/**
 * Delete a value from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 */
	function delete($key) {
		$filename = $this->_getFilename($key);
		return unlink($filename);
	}
/**
 * Delete all values from the cache
 *
 * @param boolean $checkExpiry Optional - only delete expired cache items
 * @return boolean True if the cache was succesfully cleared, false otherwise
 */
	function clear($checkExpiry = false) {
		$dir = dir($this->_dir);

		if ($checkExpiry) {
			$now = time();
			$threshold = $now - 86400;
		}

		while(($entry = $dir->read()) !== false) {
			if(strpos($entry, $this->_prefix) !== 0) {
				continue;
			}
			$filename = $this->_dir.$entry;

			if($checkExpiry) {
				$mtime = filemtime($filename);

				if($mtime === false || $mtime > $threshold) {
					continue;
				}
				$expires = $this->_getExpiry($filename);

				if($expires > $now) {
					continue;
				}
			}
			unlink($filename);
		}
		$dir->close();
		return true;
	}
/**
 * Return the settings for this cache engine
 *
 * @return array list of settings for this engine
 */
	function settings() {
		$lock = 'false';
		if($this->_lock) {
			$lock = 'true';
		}
		return array('class' => get_class($this),
						'directory' => $this->_dir,
						'prefix' => $this->_prefix,
						'lock' => $lock);
	}
/**
 * Get a filename-safe version of a string
 *
 * @param string $str String to encode
 * @return string Encoded version of the string
 */
	function base64url_encode($str) {
		return strtr(base64_encode($str), '+/', '-_');
	}
}
?>