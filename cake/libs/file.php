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
 * Constructor
 *
 * @param string $path Path to file
 * @param boolean $create Create file if it does not exist (if true)
 * @param int $mode Mode to apply to the folder holding the file
 */
	function __construct($path, $create = false, $mode = 0755) {
		parent::__construct();
		$this->Folder = new Folder(dirname($path), $create, $mode);
		$this->name = basename($path);
		if (!$this->exists()) {
			if ($create === true) {
				if (!$this->create()) {
					return false;
				}
			} else {
				return false;
			}
		}
	}
/**
 * Return the contents of this File as a string.
 *
 * @return string Contents
 * @access public
 */
	function read() {
		$contents = file_get_contents($this->getFullPath());
		return $contents;
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
	function write($data, $mode = 'w') {
		$file = $this->getFullPath();
		if (!($handle = fopen($file, $mode))) {
			trigger_error(sprintf(__("[File] Could not open %s with mode %s!", true), $file, $mode), E_USER_WARNING);
			return false;
		}

		if (!fwrite($handle, $data)) {
			return false;
		}

		if (!fclose($handle)) {
			return false;
		}
		return true;
	}
/**
 * Get md5 Checksum of file with previous check of Filesize
 *
 * @param string $force	Data to write to this File.
 * @return string md5 Checksum {@link http://php.net/md5_file See md5_file()}
 * @access public
 */
	function getMd5($force = false) {
		$md5 = '';
		if ($force == true || $this->getSize(false) < MAX_MD5SIZE) {
			$md5 = md5_file($this->getFullPath());
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
	function getSize() {
		$size = filesize($this->getFullPath());
		return $size;
	}
/**
 * Returns the File extension.
 *
 * @return string The File extension
 * @access public
 */
	function getExt() {
		$ext = '';
		$parts = explode('.', $this->getName());

		if (count($parts) > 1) {
			$ext = array_pop($parts);
		} else {
			$ext = '';
		}
		return $ext;
	}
/**
 * Returns the filename.
 *
 * @return string The Filename
 * @access public
 */
	function getName() {
		return $this->name;
	}
/**
 * Returns the File's owner.
 *
 * @return int the Fileowner
 */
	function getOwner() {
		$fileowner = fileowner($this->getFullPath());
		return $fileowner;
	 }
/**
 * Returns the File group.
 *
 * @return int the Filegroup
 * @access public
 */
	function getGroup() {
		$filegroup = filegroup($this->getFullPath());
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
			if (!touch($this->getFullPath())) {
				print (sprintf(__('[File] Could not create %s', true), $this->getName()));
				return false;
			} else {
				return true;
			}
		} else {
			print (sprintf(__('[File] Could not create %s', true), $this->getName()));
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
		$exists = file_exists($this->getFullPath());
		return $exists;
	}
/**
 * Deletes the File.
 *
 * @return boolean Success
 * @access public
 */
	function delete() {
		$unlink = unlink($this->getFullPath());
		return $unlink;
	 }
/**
 * Returns true if the File is writable.
 *
 * @return boolean true if its writable, false otherwise
 * @access public
 */
	function writable() {
		$writable = is_writable($this->getFullPath());
		return $writable;
	}
/**
 * Returns true if the File is executable.
 *
 * @return boolean true if its executable, false otherwise
 * @access public
 */
	function executable() {
		$executable = is_executable($this->getFullPath());
		return $executable;
	}
/**
 * Returns true if the File is readable.
 *
 * @return boolean true if file is readable, false otherwise
 * @access public
 */
	function readable() {
		$readable = is_readable($this->getFullPath());
		return $readable;
	}
/**
 * Returns last access time.
 *
 * @return int timestamp Timestamp of last access time
 * @access public
 */
	function lastAccess() {
		$fileatime = fileatime($this->getFullPath());
		return $fileatime;
	 }
/**
 * Returns last modified time.
 *
 * @return int timestamp Timestamp of last modification
 * @access public
 */
	function lastChange() {
		$filemtime = filemtime($this->getFullPath());
		return $filemtime;
	}
/**
 * Returns the current folder.
 *
 * @return Folder Current folder
 * @access public
 */
	function getFolder() {
		return $this->Folder;
	}
/**
 * Returns the "chmod" (permissions) of the File.
 *
 * @return string Permissions for the file
 * @access public
 */
	function getChmod() {
		$substr = substr(sprintf('%o', fileperms($this->getFullPath())), -4);
		return $substr;
	}
/**
 * Returns the full path of the File.
 *
 * @return string Full path to file
 * @access public
 */
	function getFullPath() {
		return $this->Folder->slashTerm($this->Folder->pwd()) . $this->getName();
	}
}
?>