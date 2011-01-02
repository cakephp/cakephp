<?php
/**
 * Convenience class for reading, writing and appending to files.
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
 * @package       cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Included libraries.
 *
 */
if (!class_exists('Folder')) {
	require LIBS . 'folder.php';
}

/**
 * Convenience class for reading, writing and appending to files.
 *
 * @package       cake.libs
 */
class File {

/**
 * Folder object of the File
 *
 * @var Folder
 * @access public
 */
	public $Folder = null;

/**
 * Filename
 *
 * @var string
 * @access public
 */
	public $name = null;

/**
 * file info
 *
 * @var string
 * @access public
 */
	public $info = array();

/**
 * Holds the file handler resource if the file is opened
 *
 * @var resource
 * @access public
 */
	public $handle = null;

/**
 * enable locking for file reading and writing
 *
 * @var boolean
 * @access public
 */
	public $lock = null;

/**
 * path property
 *
 * Current file's absolute path
 *
 * @var mixed null
 * @access public
 */
	public $path = null;

/**
 * Constructor
 *
 * @param string $path Path to file
 * @param boolean $create Create file if it does not exist (if true)
 * @param integer $mode Mode to apply to the folder holding the file
 */
	function __construct($path, $create = false, $mode = 0755) {
		$this->Folder = new Folder(dirname($path), $create, $mode);
		if (!is_dir($path)) {
			$this->name = basename($path);
		}
		$this->pwd();
		$create && !$this->exists() && $this->safe($path) && $this->create();
	}

/**
 * Closes the current file if it is opened
 *
 */
	function __destruct() {
		$this->close();
	}

/**
 * Creates the File.
 *
 * @return boolean Success
 */
	public function create() {
		$dir = $this->Folder->pwd();
		if (is_dir($dir) && is_writable($dir) && !$this->exists()) {
			$old = umask(0);
			if (touch($this->path)) {
				umask($old);
				return true;
			}
		}
		return false;
	}

/**
 * Opens the current file with a given $mode
 *
 * @param string $mode A valid 'fopen' mode string (r|w|a ...)
 * @param boolean $force If true then the file will be re-opened even if its already opened, otherwise it won't
 * @return boolean True on success, false on failure
 */
	public function open($mode = 'r', $force = false) {
		if (!$force && is_resource($this->handle)) {
			return true;
		}
		clearstatcache();
		if ($this->exists() === false) {
			if ($this->create() === false) {
				return false;
			}
		}

		$this->handle = fopen($this->path, $mode);
		if (is_resource($this->handle)) {
			return true;
		}
		return false;
	}

/**
 * Return the contents of this File as a string.
 *
 * @param string $bytes where to start
 * @param string $mode A `fread` compatible mode.
 * @param boolean $force If true then the file will be re-opened even if its already opened, otherwise it won't
 * @return mixed string on success, false on failure
 */
	public function read($bytes = false, $mode = 'rb', $force = false) {
		if ($bytes === false && $this->lock === null) {
			return file_get_contents($this->path);
		}
		if ($this->open($mode, $force) === false) {
			return false;
		}
		if ($this->lock !== null && flock($this->handle, LOCK_SH) === false) {
			return false;
		}
		if (is_int($bytes)) {
			return fread($this->handle, $bytes);
		}

		$data = '';
		while (!feof($this->handle)) {
			$data .= fgets($this->handle, 4096);
		}

		if ($this->lock !== null) {
			flock($this->handle, LOCK_UN);
		}
		if ($bytes === false) {
			$this->close();
		}
		return trim($data);
	}

/**
 * Sets or gets the offset for the currently opened file.
 *
 * @param mixed $offset The $offset in bytes to seek. If set to false then the current offset is returned.
 * @param integer $seek PHP Constant SEEK_SET | SEEK_CUR | SEEK_END determining what the $offset is relative to
 * @return mixed True on success, false on failure (set mode), false on failure or integer offset on success (get mode)
 */
	public function offset($offset = false, $seek = SEEK_SET) {
		if ($offset === false) {
			if (is_resource($this->handle)) {
				return ftell($this->handle);
			}
		} elseif ($this->open() === true) {
			return fseek($this->handle, $offset, $seek) === 0;
		}
		return false;
	}

/**
 * Prepares a ascii string for writing.  Converts line endings to the 
 * correct terminator for the current platform.  If windows "\r\n" will be used
 * all other platforms will use "\n"
 *
 * @param string $data Data to prepare for writing.
 * @return string The with converted line endings.
 */
	public static function prepare($data, $forceWindows = false) {
		$lineBreak = "\n";
		if (DIRECTORY_SEPARATOR == '\\' || $forceWindows === true) {
			$lineBreak = "\r\n";
		}
		return strtr($data, array("\r\n" => $lineBreak, "\n" => $lineBreak, "\r" => $lineBreak));
	}

/**
 * Write given data to this File.
 *
 * @param string $data Data to write to this File.
 * @param string $mode Mode of writing. {@link http://php.net/fwrite See fwrite()}.
 * @param string $force force the file to open
 * @return boolean Success
 */
	public function write($data, $mode = 'w', $force = false) {
		$success = false;
		if ($this->open($mode, $force) === true) {
			if ($this->lock !== null) {
				if (flock($this->handle, LOCK_EX) === false) {
					return false;
				}
			}

			if (fwrite($this->handle, $data) !== false) {
				$success = true;
			}
			if ($this->lock !== null) {
				flock($this->handle, LOCK_UN);
			}
		}
		return $success;
	}

/**
 * Append given data string to this File.
 *
 * @param string $data Data to write
 * @param string $force force the file to open
 * @return boolean Success
 */
	public function append($data, $force = false) {
		return $this->write($data, 'a', $force);
	}

/**
 * Closes the current file if it is opened.
 *
 * @return boolean True if closing was successful or file was already closed, otherwise false
 */
	public function close() {
		if (!is_resource($this->handle)) {
			return true;
		}
		return fclose($this->handle);
	}

/**
 * Deletes the File.
 *
 * @return boolean Success
 */
	public function delete() {
		clearstatcache();
		if ($this->exists()) {
			return unlink($this->path);
		}
		return false;
	}

/**
 * Returns the File info.
 *
 * @return string The File extension
 */
	public function info() {
		if ($this->info == null) {
			$this->info = pathinfo($this->path);
		}
		if (!isset($this->info['filename'])) {
			$this->info['filename'] = $this->name();
		}
		return $this->info;
	}

/**
 * Returns the File extension.
 *
 * @return string The File extension
 */
	public function ext() {
		if ($this->info == null) {
			$this->info();
		}
		if (isset($this->info['extension'])) {
			return $this->info['extension'];
		}
		return false;
	}

/**
 * Returns the File name without extension.
 *
 * @return string The File name without extension.
 */
	public function name() {
		if ($this->info == null) {
			$this->info();
		}
		if (isset($this->info['extension'])) {
			return basename($this->name, '.'.$this->info['extension']);
		} elseif ($this->name) {
			return $this->name;
		}
		return false;
	}

/**
 * makes filename safe for saving
 *
 * @param string $name The name of the file to make safe if different from $this->name
 * @param strin $ext The name of the extension to make safe if different from $this->ext
 * @return string $ext the extension of the file
 */
	public function safe($name = null, $ext = null) {
		if (!$name) {
			$name = $this->name;
		}
		if (!$ext) {
			$ext = $this->ext();
		}
		return preg_replace( "/(?:[^\w\.-]+)/", "_", basename($name, $ext));
	}

/**
 * Get md5 Checksum of file with previous check of Filesize
 *
 * @param mixed $maxsize in MB or true to force
 * @return string md5 Checksum {@link http://php.net/md5_file See md5_file()}
 */
	public function md5($maxsize = 5) {
		if ($maxsize === true) {
			return md5_file($this->path);
		}

		$size = $this->size();
		if ($size && $size < ($maxsize * 1024) * 1024) {
			return md5_file($this->path);
		}

		return false;
	}

/**
 * Returns the full path of the File.
 *
 * @return string Full path to file
 */
	public function pwd() {
		if (is_null($this->path)) {
			$this->path = $this->Folder->slashTerm($this->Folder->pwd()) . $this->name;
		}
		return $this->path;
	}

/**
 * Returns true if the File exists.
 *
 * @return boolean true if it exists, false otherwise
 */
	public function exists() {
		return (file_exists($this->path) && is_file($this->path));
	}

/**
 * Returns the "chmod" (permissions) of the File.
 *
 * @return string Permissions for the file
 */
	public function perms() {
		if ($this->exists()) {
			return substr(sprintf('%o', fileperms($this->path)), -4);
		}
		return false;
	}

/**
 * Returns the Filesize
 *
 * @return integer size of the file in bytes, or false in case of an error
 */
	public function size() {
		if ($this->exists()) {
			return filesize($this->path);
		}
		return false;
	}

/**
 * Returns true if the File is writable.
 *
 * @return boolean true if its writable, false otherwise
 */
	public function writable() {
		return is_writable($this->path);
	}

/**
 * Returns true if the File is executable.
 *
 * @return boolean true if its executable, false otherwise
 */
	public function executable() {
		return is_executable($this->path);
	}

/**
 * Returns true if the File is readable.
 *
 * @return boolean true if file is readable, false otherwise
 */
	public function readable() {
		return is_readable($this->path);
	}

/**
 * Returns the File's owner.
 *
 * @return integer the Fileowner
 */
	public function owner() {
		if ($this->exists()) {
			return fileowner($this->path);
		}
		return false;
	}

/**
 * Returns the File's group.
 *
 * @return integer the Filegroup
 */
	public function group() {
		if ($this->exists()) {
			return filegroup($this->path);
		}
		return false;
	}

/**
 * Returns last access time.
 *
 * @return integer timestamp Timestamp of last access time
 */
	public function lastAccess() {
		if ($this->exists()) {
			return fileatime($this->path);
		}
		return false;
	}

/**
 * Returns last modified time.
 *
 * @return integer timestamp Timestamp of last modification
 */
	public function lastChange() {
		if ($this->exists()) {
			return filemtime($this->path);
		}
		return false;
	}

/**
 * Returns the current folder.
 *
 * @return Folder Current folder
 */
	public function &Folder() {
		return $this->Folder;
	}

/**
 * Copy the File to $dest
 *
 * @param string $dest destination for the copy
 * @param boolean $overwrite Overwrite $dest if exists
 * @return boolean Succes
 */
	public function copy($dest, $overwrite = true) {
		if (!$this->exists() || is_file($dest) && !$overwrite) {
			return false;
		}
		return copy($this->path, $dest);
	}
}
