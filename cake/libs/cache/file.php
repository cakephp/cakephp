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
 * Does the cache engine handle prefixes on it's own?
 *
 * @var boolean
 * @access private
 */
	var $_usesPrefixes = true;
/**
 * Cache directory
 *
 * @var string
 * @access private
 */
	var $_dir = '';
/**
 * Cache filename prefix
 *
 * @var string
 * @access private
 */
	var $_prefix = '';
/**
 * Use locking
 *
 * @var boolean
 * @access private
 */
	var $_lock = false;
/**
 * Set up the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 * @access public
 */
	function init($params) {
		$dir = CACHE;
		$prefix = 'cake_';
		$lock = false;
		extract($params);
		$dir = trim($dir);
		$this->Folder =& new Folder();

		if (!empty($dir)) {
			$dir = $this->Folder->slashTerm($dir);
		}

		if (empty($dir) || !$this->Folder->isAbsolute($dir) || !is_writable($dir)) {
			return false;
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
 * @return boolean True if garbage collection was succesful, false on failure
 * @access public
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
 * @access public
 */
	function write($key, &$data, $duration = CACHE_DEFAULT_DURATION) {
		if(!$data) {
			return false;
		}

		$data = serialize($data);

		if (!$data) {
			return false;
		}
		$expires = time() + $duration;

		$fileName = $this->_getFilename($key);
		if ($fileName === false) {
			return false;
		}

		return $this->_writeCache($fileName, $data, $expires);
	}
/**
 * Get absolute filename for a key
 *
 * @param string $key The key
 * @return mixed Absolute cache filename for the given key or false if erroneous
 * @access private
 */
	function _getFilename($key) {
		$file =& new File($this->_dir);
		$path = array_map(array($file , 'safe'), explode(DS, $key));
		$key = array_pop($path);
		$fullpath = $this->Folder->realpath($this->_dir . implode(DS, $path) . DS . $this->_prefix . $key);
		if (!$this->Folder->inPath($fullpath, true)) {
			return false;
		}
		return $fullpath;
	}
/**
 * write serialized data to a file
 *
 * @param string $filename
 * @param string $value
 * @param integer $expires
 * @return boolean True on success, false on failure
 * @access private
 */
	function _writeCache(&$filename, &$data, &$expires) {
		$directoryName = dirname($filename);
		if (!is_writable($directoryName)) {
			if (!$this->Folder->create($directoryName)) {
				return false;
			}
		}
		$contents = $expires."\n".$data."\n";
		return ife(file_put_contents($filename, $contents, ife($this->_lock, LOCK_EX, 0)), true, false);
	}
/**
 * Read a value from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		$filename = $this->_getFilename($key);
		if ($filename === false || !is_file($filename) || !is_readable($filename)) {
			return false;
		}
		$fp = fopen($filename, 'r');
		if (!$fp) {
			return false;
		}
		if ($this->_lock && !flock($fp, LOCK_SH)) {
			return false;
		}
		$cachetime = fgets($fp, 11);
		if (intval($cachetime) < time()) {
			fclose($fp);
			unlink($filename);
			return false;
		}
		$data = '';
		while (!feof($fp)) {
			$data .= fgets($fp, 4096);
		}
		$data = trim($data);
		return unserialize($data);
	}
/**
 * Get the expiry time for a cache file
 *
 * @param string $filename
 * @return mixed Expiration timestamp, or false on failure
 * @access private
 */
	function _getExpiry($filename) {
		$fp = fopen($filename, 'r');

		if (!$fp) {
			return false;
		}

		if ($this->_lock && !flock($fp, LOCK_SH)) {
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
 * @access public
 */
	function delete($key) {
		$filename = $this->_getFilename($key);
		if ($filename === false) {
			return false;
		}
		return unlink($filename);
	}
/**
 * Delete all values from the cache
 *
 * @param boolean $checkExpiry Optional - only delete expired cache items
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear($checkExpiry = false) {
		$dir = dir($this->_dir);

		if ($checkExpiry) {
			$now = time();
			$threshold = $now - 86400;
		}

		while (($entry = $dir->read()) !== false) {
			if (strpos($entry, $this->_prefix) !== 0) {
				continue;
			}
			$filename = $this->_dir . $entry;

			if ($checkExpiry) {
				$mtime = filemtime($filename);

				if ($mtime === false || $mtime > $threshold) {
					continue;
				}
				$expires = $this->_getExpiry($filename);

				if ($expires > $now) {
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
 * @access public
 */
	function settings() {
		$lock = 'false';
		if ($this->_lock) {
			$lock = 'true';
		}
		return array('class' => get_class($this),
						'directory' => $this->_dir,
						'prefix' => $this->_prefix,
						'lock' => $lock);
	}
}
?>