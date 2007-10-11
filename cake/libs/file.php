<?php
/* SVN FILE: $Id$ */
/**
 * Convenience class for reading, writing and appending to files.
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
	if (!class_exists('Object')) {
		 uses ('object');
	}

	if (!class_exists('Folder')) {
		 uses ('folder');
	}
/**
 * Convenience class for reading, writing and appending to files.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class File extends Object{
/**
 * Folder object of the File
 *
 * @var object
 * @access public
 */
	var $Folder = null;
/**
 * Filename
 *
 * @var string
 * @access public
 */
	var $name = null;
/**
 * file info
 *
 * @var string
 * @access public
 */
	var $info = array();

/**
 * Holds the file handler resource if the file is opened
 *
 * @var resource
 * @access public
 */
	var $handle = null;
/**
 * Constructor
 *
 * @param string $path Path to file
 * @param boolean $create Create file if it does not exist (if true)
 * @param int $mode Mode to apply to the folder holding the file
 */
	function __construct($path, $create = false, $mode = 0755) {
		parent::__construct();
		$this->Folder =& new Folder(dirname($path), $create, $mode);
		if (!is_dir($path)) {
			$this->name = basename($path);
		}
		if (!$this->exists()) {
			if ($create === true) {
				$this->safe();
				if (!$this->create()) {
					return false;
				}
			} else {
				return false;
			}
		}
	}
/**
 * Closes the current file if it is opened
 *
 * @return void
 * @access public
 */
	function __destruct() {
		$this->close();
	}
/**
 * Opens the current file with a given $mode
 *
 * @param string $mode A valid 'fopen' mode string (r|w|a ...)
 * @param boolean $force If true then the file will be re-opened even if its already opened, otherwise it won't
 * @return boolean True on success, false on failure
 * @access public
 */
	function open($mode = 'r', $force = false) {
		if (!$force && $this->opened()) {
			return true;
		}
		
		$file = $this->pwd();
		$this->handle = @fopen($file, $mode);
		if (!$this->opened()) {
			trigger_error(sprintf(__("[File] Could not open %s with mode %s!", true), $file, $mode), E_USER_WARNING);
			return false;
		}
		return true;
	}
/**
 * Closes the current file if it is opened.
 *
 * @return boolean True if closing was successful or file was already closed, otherwise false
 * @access public
 */
	function close() {
		if (!$this->opened()) {
			return true;
		}
		return fclose($this->handle);
	}
/**
 * Return the contents of this File as a string.
 *
 * @return string Contents
 * @access public
 */
	function read($bytes = false, $mode = 'rb', $forceMode = false) {
		if (!is_int($bytes)) {
			$contents = file_get_contents($this->pwd());
			return $contents;
		}
		
		$this->open($mode, $forceMode);
		return fread($this->handle, $bytes);
	}
/**
 * Sets or gets the offset for the currently opened file.
 *
 * @param mixed $offset The $offset in bytes to seek. If set to false then the current offset is returned.
 * @param integer $whence PHP Constant SEEK_SET | SEEK_CUR | SEEK_END determining what the $offset is relative to
 * @return mixed True on success, false on failure (set mode), false on failure or integer offset on success (get mode)
 * @access public
 */
	function offset($offset = false, $whence = SEEK_SET) {
		if ($offset === false) {
			if (!$this->opened()) {
				return false;
			}
			return ftell($this->handle);
		}
		
		if (!$this->open()) {
			return false;
		}
		return fseek($this->handle, $offset, $whence) === 0;
	}
/**
 * Append given data string to this File.
 *
 * @param string $data Data to write
 * @return boolean Success
 * @access public
 */
	function append($data) {
		return $this->write($data, 'a');
	}
/**
 * Write given data to this File.
 *
 * @param string $data	Data to write to this File.
 * @param string $mode	Mode of writing. {@link http://php.net/fwrite See fwrite()}.
 * @return boolean Success
 * @access public
 */
	function write($data, $mode = 'w', $forceMode = false) {
		if (!$this->open($mode, $forceMode)) {
			return false;
		}
		if (false === fwrite($this->handle, $data)) {
			return false;
		}
		return true;
	}
/**
 * makes filename safe for saving
 *
 * @param string $name the name of the file to make safe if different from $this->name
 * @return string $ext the extension of the file
 * @access public
 */
	function safe($name = null, $ext = null) {
		if (!$name) {
			$name = $this->name;
		}
		if (!$ext) {
			$ext = $this->ext();
		}
		return preg_replace( "/[^\w\.-]+/", "_", basename($name, $ext));
	}
/**
 * Get md5 Checksum of file with previous check of Filesize
 *
 * @param string $force	Data to write to this File.
 * @return string md5 Checksum {@link http://php.net/md5_file See md5_file()}
 * @access public
 */
	function md5($force = false) {
		$md5 = '';
		if ($force == true || $this->size(false) < MAX_MD5SIZE) {
			$md5 = md5_file($this->pwd());
		}
		return $md5;
	}
/**
 * Returns the Filesize, either in bytes or in human-readable format.
 *
 * @param boolean $humanReadeble	Data to write to this File.
 * @return string|int filesize as int or as a human-readable string
 * @access public
 */
	function size() {
		$size = filesize($this->pwd());
		return $size;
	}
/**
 * Returns the File extension.
 *
 * @return string The File extension
 * @access public
 */
	function info() {
		if ($this->info == null) {
			$this->info = pathinfo($this->pwd());
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
 * @access public
 */
	function ext() {
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
 * @access public
 */
	function name() {
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
 * Returns the File's owner.
 *
 * @return int the Fileowner
 */
	function owner() {
		$fileowner = fileowner($this->pwd());
		return $fileowner;
	 }
/**
 * Returns the File group.
 *
 * @return int the Filegroup
 * @access public
 */
	function group() {
		$filegroup = filegroup($this->pwd());
		return $filegroup;
	 }
/**
 * Creates the File.
 *
 * @return boolean Success
 * @access public
 */
	function create() {
		$dir = $this->Folder->pwd();

		if (file_exists($dir) && is_dir($dir) && is_writable($dir) && !$this->exists()) {
			if (!touch($this->pwd())) {
				print (sprintf(__('[File] Could not create %s', true), $this->name));
				return false;
			} else {
				return true;
			}
		} else {
			print (sprintf(__('[File] Could not create %s', true), $this->name));
			return false;
		}
	}
/**
 * Returns true if the File exists.
 *
 * @return boolean true if it exists, false otherwise
 * @access public
 */
	function exists() {
		$exists = (file_exists($this->pwd()) && is_file($this->pwd()));
		return $exists;
	}
/**
 * Deletes the File.
 *
 * @return boolean Success
 * @access public
 */
	function delete() {
		$unlink = unlink($this->pwd());
		return $unlink;
	 }
/**
 * Returns true if the File is writable.
 *
 * @return boolean true if its writable, false otherwise
 * @access public
 */
	function writable() {
		$writable = is_writable($this->pwd());
		return $writable;
	}
/**
 * Returns true if the File is executable.
 *
 * @return boolean true if its executable, false otherwise
 * @access public
 */
	function executable() {
		$executable = is_executable($this->pwd());
		return $executable;
	}
/**
 * Returns true if the current file is currently opened by this class instance.
 *
 * @return boolean True if file is opened, false otherwise
 * @access public
 */
	function opened() {
		return is_resource($this->handle);
	}

/**
 * Returns true if the File is readable.
 *
 * @return boolean true if file is readable, false otherwise
 * @access public
 */
	function readable() {
		$readable = is_readable($this->pwd());
		return $readable;
	}
/**
 * Returns last access time.
 *
 * @return int timestamp Timestamp of last access time
 * @access public
 */
	function lastAccess() {
		$fileatime = fileatime($this->pwd());
		return $fileatime;
	 }
/**
 * Returns last modified time.
 *
 * @return int timestamp Timestamp of last modification
 * @access public
 */
	function lastChange() {
		$filemtime = filemtime($this->pwd());
		return $filemtime;
	}
/**
 * Returns the current folder.
 *
 * @return Folder Current folder
 * @access public
 */
	function &Folder() {
		return $this->Folder;
	}
/**
 * Returns the "chmod" (permissions) of the File.
 *
 * @return string Permissions for the file
 * @access public
 */
	function perms() {
		$substr = substr(sprintf('%o', fileperms($this->pwd())), -4);
		return $substr;
	}
/**
* Returns the full path of the File.
*
* @return string Full path to file
* @access public
*/
	function pwd() {
		return $this->Folder->slashTerm($this->Folder->pwd()) . $this->name;
	}

/* Deprecated methods */
/**
 * @deprecated
 * @see File::pwd
 */
	function getFullPath() {
		trigger_error('Deprecated: Use File::pwd() instead.', E_USER_WARNING);
		return $this->pwd();
	}
/**
 * @deprecated
 * @see File::name
 */
	function getName() {
		trigger_error('Deprecated: Use File::name() instead.', E_USER_WARNING);
		return $this->name;
	}
/**
 * @deprecated
 * @see File::name()
 */
	function filename() {
		trigger_error('Deprecated: Use File::name() instead.', E_USER_WARNING);
		return $this->name();
	}
/**
 * @deprecated
 * @see File::ext()
 */
	function getExt() {
		trigger_error('Deprecated: Use File::ext() instead.', E_USER_WARNING);
		return $this->ext();
	}
/**
 * @deprecated
 * @see File::md5()
 */
	function getMd5() {
		trigger_error('Deprecated: Use File::md5() instead.', E_USER_WARNING);
		return $this->md5();
	}
/**
 * @deprecated
 * @see File::size()
 */
	function getSize() {
		trigger_error('Deprecated: Use File::size() instead.', E_USER_WARNING);
		return $this->size();
	}
/**
 * @deprecated
 * @see File::owner()
 */
	function getOwner() {
		trigger_error('Deprecated: Use File::owner() instead.', E_USER_WARNING);
		return $this->owner();
	}
/**
 * @deprecated
 * @see File::group()
 */
	function getGroup() {
		trigger_error('Deprecated: Use File::group() instead.', E_USER_WARNING);
		return $this->group();
	}
/**
 * @deprecated
 * @see File::perms()
 */
	function getChmod() {
		trigger_error('Deprecated: Use File::perms() instead.', E_USER_WARNING);
		return $this->perms();
	}
/**
 * @deprecated
 * @see File::Folder()
 */
	function getFolder() {
		trigger_error('Deprecated: Use File::Folder() instead.', E_USER_WARNING);
		return $this->Folder();
	}
}
?>