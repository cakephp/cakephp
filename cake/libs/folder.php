<?php
/* SVN FILE: $Id$ */
/**
 * Convenience class for handling directories.
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
/**
 * Folder structure browser, lists folders and files.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Folder extends Object{
/**
 * Path to Folder.
 *
 * @var string
 */
	var $path = null;
/**
 * Sortedness.
 *
 * @var boolean
 */
	var $sort = false;
/**
 * Constructor.
 *
 * @param string $path
 * @param boolean $path
 */
	function __construct($path = false, $create = false, $mode = false) {
		parent::__construct();
		if (empty($path)) {
			$path = getcwd();
		}

		if (!file_exists($path) && $create == true) {
			$this->mkdirr($path, $mode);
		}
		$this->cd($path);
	}
/**
 * Return current path.
 *
 * @return string Current path
 */
	function pwd() {
		return $this->path;
	}
/**
 * Change directory to $desired_path.
 *
 * @param string $desired_path Path to the directory to change to
 * @return string The new path. Returns false on failure
 */
	function cd($desiredPath) {
		$desiredPath = realpath($desiredPath);
		$newPath = $this->isAbsolute($desiredPath) ? $desiredPath : $this->addPathElement($this->path, $desiredPath);
		$isDir = (is_dir($newPath) && file_exists($newPath)) ? $this->path = $newPath : false;
		return $isDir;
	 }
/**
 * Returns an array of the contents of the current directory, or false on failure.
 * The returned array holds two arrays: one of dirs and one of files.
 *
 * @param boolean $sort
 * @param boolean $noDotFiles
 * @return array
 */
	function ls($sort = true, $noDotFiles = false) {
		$dirs = $files = array();
		$dir = opendir($this->path);
		if ($dir) {
			while(false !== ($n = readdir($dir))) {
				if ((!preg_match('#^\.+$#', $n) && $noDotFiles == false) || ($noDotFiles == true && !preg_match('#^\.(.*)$#', $n))) {
					if (is_dir($this->addPathElement($this->path, $n))) {
						$dirs[] = $n;
					} else {
						$files[] = $n;
					}
				}
			}

			if ($sort || $this->sort) {
				sort ($dirs);
				sort ($files);
			}
			closedir ($dir);
		}
		return array($dirs,$files);
	}
/**
 * Returns an array of all matching files in current directory.
 *
 * @param string $pattern Preg_match pattern (Defaults to: .*)
 * @return array
 */
	function find($regexp_pattern = '.*') {
		$data = $this->ls();

		if (!is_array($data)) {
			return array();
		}

		list($dirs, $files) = $data;
		$found =  array();

		foreach($files as $file) {
			if (preg_match("/^{$regexp_pattern}$/i", $file)) {
				$found[] = $file;
			}
		}
		return $found;
	}
/**
 * Returns an array of all matching files in and below current directory.
 *
 * @param string $pattern Preg_match pattern (Defaults to: .*)
 * @return array Files matching $pattern
 */
	function findRecursive($pattern = '.*') {
		$startsOn = $this->path;
		$out = $this->_findRecursive($pattern);
		$this->cd($startsOn);
		return $out;
	}
/**
 * Private helper function for findRecursive.
 *
 * @param string $pattern
 * @return array Files matching pattern
 * @access private
 */
	function _findRecursive($pattern) {
		list($dirs, $files) = $this->ls();

		$found = array();
		foreach($files as $file) {
			if (preg_match("/^{$pattern}$/i", $file)) {
				$found[] = $this->addPathElement($this->path, $file);
			}
		}
		$start = $this->path;
		foreach($dirs as $dir) {
			$this->cd($this->addPathElement($start, $dir));
			$found = array_merge($found, $this->findRecursive($pattern));
		}
		return $found;
	}
/**
 * Returns true if given $path is a Windows path.
 *
 * @param string $path Path to check
 * @return boolean
 * @static
 */
	function isWindowsPath($path) {
		$match = preg_match('#^[A-Z]:\\\#i', $path) ? true : false;
		return $match;
	}
/**
 * Returns true if given $path is an absolute path.
 *
 * @param string $path Path to check
 * @return boolean
 * @static
 */
	function isAbsolute($path) {
		$match = preg_match('#^\/#', $path) || preg_match('#^[A-Z]:\\\#i', $path);
		return $match;
	}
/**
 * Returns true if given $path ends in a slash (i.e. is slash-terminated).
 *
 * @param string $path Path to check
 * @return boolean
 * @static
 */
	function isSlashTerm($path) {
		$match = preg_match('#[\\\/]$#', $path) ? true : false;
		return $match;
	}
/**
 * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
 *
 * @param string $path Path to check
 * @return string Set of slashes ("\\" or "/")
 * @static
 */
	function correctSlashFor($path) {
		return $this->isWindowsPath($path) ? '\\' : '/';
	}
/**
 * Returns $path with added terminating slash (corrected for Windows or other OS).
 *
 * @param string $path Path to check
 * @return string
 * @static
 */
function slashTerm($path) {
		  return $path . ($this->isSlashTerm($path) ? null : $this->correctSlashFor($path));
	 }
/**
 * Returns $path with $element added, with correct slash in-between.
 *
 * @param string $path
 * @param string $element
 * @return string
 * @static
 */
	function addPathElement($path, $element) {
		return $this->slashTerm($path) . $element;
	}
/**
 * Returns true if the File is in a given CakePath.
 *
 * @return boolean
 */
	function inCakePath($path = '') {
		$dir = substr($this->slashTerm(ROOT), 0, -1);
		$newdir = $this->slashTerm($dir . $path);
		return $this->inPath($newdir);
	 }
/**
 * Returns true if the File is in given path.
 *
 * @return boolean
 */
	function inPath($path = '') {
		$dir = substr($this->slashTerm($path), 0, -1);
		$return = preg_match('/^' . preg_quote($this->slashTerm($dir), '/') . '(.*)/', $this->slashTerm($this->pwd()));
		if ($return == 1) {
			return true;
		} else {
			return false;
		}
	}
/**
 * Change the mode on a directory structure recursively.
 *
 * @param string $pathname The directory structure to create
 * @return bool Returns TRUE on success, FALSE on failure
 */
	function chmodr($pathname, $mode = 0755) {
		if (empty($pathname)) {
			return false;
		}

		if (is_file($pathname)) {
			trigger_error(__('chmodr() File exists'), E_USER_WARNING);
			return false;
		}

		$nextPathname = substr($pathname, 0, strrpos($pathname, DS));

		if ($this->chmodr($nextPathname, $mode)) {
			if(file_exists($pathname)) {
				umask (0);
				$chmod = @chmod($pathname, $mode);
				return true;
			}
		}
		return true;
	}
/**
 * Create a directory structure recursively.
 *
 * @param string $pathname The directory structure to create
 * @return bool Returns TRUE on success, FALSE on failure
 */
	function mkdirr($pathname, $mode = 0755) {
		if (is_dir($pathname) || empty($pathname)) {
			return true;
		}

		if (is_file($pathname)) {
			trigger_error(__('mkdirr() File exists'), E_USER_WARNING);
			return false;
		}
		$nextPathname = substr($pathname, 0, strrpos($pathname, DS));

		if ($this->mkdirr($nextPathname, $mode)) {
			if (!file_exists($pathname)) {
				umask (0);
				$mkdir = mkdir($pathname, $mode);
				return $mkdir;
			}
		}
		return true;
	}
/**
 * Returns the size in bytes of this Folder.
 *
 * @param string $directory Path to directory
 */
	function dirsize() {
		$size = 0;
		$directory = $this->slashTerm($this->path);
		$stack = array($directory);
		$count = count($stack);
		for($i = 0, $j = $count; $i < $j; ++$i) {
			if (is_file($stack[$i])) {
				$size += filesize($stack[$i]);
			} elseif (is_dir($stack[$i])) {
				$dir = dir($stack[$i]);

				while(false !== ($entry = $dir->read())) {
					if ($entry == '.' || $entry == '..') {
						continue;
					}
					$add = $stack[$i] . $entry;

					if (is_dir($stack[$i] . $entry)) {
						$add = $this->slashTerm($add);
					}
					$stack[ ]= $add;
				}
				$dir->close();
			}
			$j = count($stack);
		}
		return $size;
	}
/**
 * Recursively Remove directories if system allow.
 *
 * @param string $path
 * @return boolean
 */
	function rmdir($path) {
		if (substr($path, -1, 1) != "/") {
			$path .= "/";
		}
		foreach (glob($path . "*") as $file) {
			if (is_file($file) === true) {
				@unlink($file);
			}
			elseif (is_dir($file) === true) {
				if($this->rmdir($file) === false) {
					return false;
				}
			}
		}
		if (is_dir($path) === true) {
			if(rmdir($path) === false) {
				return false;
			}
		}
		return true;
	}
}
?>