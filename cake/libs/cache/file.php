<?php
/**
 * File Storage engine for cache
 *
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * File Storage engine for cache
 *
 * @todo use the File and Folder classes (if it's not a too big performance hit)
 * @package       cake.libs.cache
 */
class FileEngine extends CacheEngine {

/**
 * Instance of SplFileObject class
 *
 * @var _File
 * @access protected
 */
	protected $_File = null;

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
	public $settings = array();

/**
 * True unless FileEngine::__active(); fails
 *
 * @var boolean
 * @access protected
 */
	protected $_init = true;

/**
 * Initialize the Cache Engine
 *
 * Called automatically by the cache frontend
 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
 *
 * @param array $setting array of setting for the engine
 * @return boolean True if the engine has been successfully initialized, false if not
 */
	public function init($settings = array()) {
		parent::init(array_merge(
			array(
				'engine' => 'File', 'path' => CACHE, 'prefix'=> 'cake_', 'lock'=> false,
				'serialize'=> true, 'isWindows' => false
			),
			$settings
		));

		if (DS === '\\') {
			$this->settings['isWindows'] = true;
		}
		if (substr($this->settings['path'], -1) !== DS) {
			$this->settings['path'] .= DS;
		}
		return $this->_active();
	}

/**
 * Garbage collection. Permanently remove all expired and deleted data
 *
 * @return boolean True if garbage collection was succesful, false on failure
 */
	public function gc() {
		return $this->clear(true);
	}

/**
 * Write data for key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $data Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 */
	public function write($key, $data, $duration) {
		if ($data === '' || !$this->_init) {
			return false;
		}

		if ($this->_setKey($key, true) === false) {
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
			$this->_File->flock(LOCK_EX);
		}

		$expires = time() + $duration;
		$contents = $expires . $lineBreak . $data . $lineBreak;
		$success = $this->_File->ftruncate(0) && $this->_File->fwrite($contents);

		if ($this->settings['lock']) {
			$this->_File->flock(LOCK_UN);
		}
		$this->_File = null;

		return $success;
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public function read($key) {
		if (!$this->_init || $this->_setKey($key) === false) {
			return false;
		}

		if ($this->settings['lock']) {
			$this->_File->flock(LOCK_SH);
		}

		$this->_File->rewind();
		$time = time();
		$cachetime = intval($this->_File->current());

		if ($cachetime !== false && ($cachetime < $time || ($time + $this->settings['duration']) < $cachetime)) {
			return false;
		}
		
		$data = '';
		$this->_File->next();
		while ($this->_File->valid()) {
			$data .= $this->_File->current();
			$this->_File->next();
		}

		if ($this->settings['lock']) {
			$this->_File->flock(LOCK_UN);
		}

		$data = trim($data);

		if ($data !== '' && !empty($this->settings['serialize'])) {
			if ($this->settings['isWindows']) {
				$data = str_replace('\\\\\\\\', '\\', $data);
			}
			$data = unserialize((string)$data);
		}
		return $data;
	}

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public function delete($key) {
		if ($this->_setKey($key) === false || !$this->_init) {
			return false;
		}
		$path = $this->_File->getRealPath();
		$this->_File = null;
		return unlink($path);
	}

/**
 * Delete all values from the cache
 *
 * @param boolean $check Optional - only delete expired cache items
 * @return boolean True if the cache was succesfully cleared, false otherwise
 */
	public function clear($check) {
		if (!$this->_init) {
			return false;
		}
		$dir = dir($this->settings['path']);
		if ($check) {
			$now = time();
			$threshold = $now - $this->settings['duration'];
		}
		$prefixLength = strlen($this->settings['prefix']);
		while (($entry = $dir->read()) !== false) {
			if (substr($entry, 0, $prefixLength) !== $this->settings['prefix']) {
				continue;
			}
			if ($this->_setKey($entry) === false) {
				continue;
			}
			if ($check) {
				$mtime = $this->_File->getMTime();

				if ($mtime > $threshold) {
					continue;
				}

				$expires = (int)$this->_File->current();

				if ($expires > $now) {
					continue;
				}
			}
			$path = $this->_File->getRealPath();
			$this->_File = null;
			unlink($path);
		}
		$dir->close();
		return true;
	}

/**
 * Not implemented
 *
 * @return void
 * @throws CacheException
 */
	public function decrement($key, $offset = 1) {
		throw new CacheException(__('Files cannot be atomically decremented.'));
	}

/**
 * Not implemented
 *
 * @return void
 * @throws CacheException
 */
	public function increment($key, $offset = 1) {
		throw new CacheException(__('Files cannot be atomically incremented.'));
	}

/**
 * Sets the current cache key this class is managing
 *
 * @param string $key The key
 * @param boolean $createKey Whether the key should be created if it doesn't exists, or not
 * @return boolean true if the cache key could be set, false otherwise
 * @access protected
 */
	protected function _setKey($key, $createKey = false) {
		$path = new SplFileInfo($this->settings['path'] . $key);

		if (!$createKey && !$path->isFile()) {
			return false;
		}
		$old = umask(0);
		if (empty($this->_File) || $this->_File->getBaseName() !== $key) {
			$this->_File = $path->openFile('a+');
		}
		umask($old);

		return true;
	}

/**
 * Determine is cache directory is writable
 *
 * @return boolean
 * @access protected
 */
	protected function _active() {
		$dir = new SplFileInfo($this->settings['path']);
		if ($this->_init && !($dir->isDir() && $dir->isWritable())) {
			$this->_init = false;
			trigger_error(__('%s is not writable', $this->settings['path']), E_USER_WARNING);
			return false;
		}
		return true;
	}
}
