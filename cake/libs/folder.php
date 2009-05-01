<?php
/* SVN FILE: $Id$ */
/**
 * Convenience class for handling directories.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if (!class_exists('Object')) {
	uses('object');
}
/**
 * Folder structure browser, lists folders and files.
 *
 * Long description for class
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class Folder extends Object {
/**
 * Path to Folder.
 *
 * @var string
 * @access public
 */
	var $path = null;
/**
 * Sortedness.
 *
 * @var boolean
 * @access public
 */
	var $sort = false;
/**
 * mode to be used on create.
 *
 * @var boolean
 * @access public
 */
	var $mode = 0755;
/**
 * holds messages from last method.
 *
 * @var array
 * @access private
 */
	var $__messages = array();
/**
 * holds errors from last method.
 *
 * @var array
 * @access private
 */
	var $__errors = false;
/**
 * holds array of complete directory paths.
 *
 * @var array
 * @access private
 */
	var $__directories;
/**
 * holds array of complete file paths.
 *
 * @var array
 * @access private
 */
	var $__files;
/**
 * Constructor.
 *
 * @param string $path Path to folder
 * @param boolean $create Create folder if not found
 * @param mixed $mode Mode (CHMOD) to apply to created folder, false to ignore
 */
	function __construct($path = false, $create = false, $mode = false) {
		parent::__construct();
		if (empty($path)) {
			$path = TMP;
		}
		if ($mode) {
			$this->mode = $mode;
		}

		if (!file_exists($path) && $create === true) {
			$this->create($path, $this->mode);
		}
		if (!Folder::isAbsolute($path)) {
			$path = realpath($path);
		}
		if (!empty($path)) {
			$this->cd($path);
		}
	}
/**
 * Return current path.
 *
 * @return string Current path
 * @access public
 */
	function pwd() {
		return $this->path;
	}
/**
 * Change directory to $path.
 *
 * @param string $path Path to the directory to change to
 * @return string The new path. Returns false on failure
 * @access public
 */
	function cd($path) {
		$path = $this->realpath($path);
		if (is_dir($path)) {
			return $this->path = $path;
		}
		return false;
	}
/**
 * Returns an array of the contents of the current directory.
 * The returned array holds two arrays: One of directories and one of files.
 *
 * @param boolean $sort
 * @param mixed $exceptions Either an array or boolean true will not grab dot files
 * @param boolean $fullPath True returns the full path
 * @return mixed Contents of current directory as an array, an empty array on failure
 * @access public
 */
	function read($sort = true, $exceptions = false, $fullPath = false) {
		$dirs = $files = array();

		if (is_array($exceptions)) {
			$exceptions = array_flip($exceptions);
		}
		$skipHidden = isset($exceptions['.']) || $exceptions === true;

		if (false === ($dir = @opendir($this->path))) {
			return array($dirs, $files);
		}

		while (false !== ($item = readdir($dir))) {
			if ($item === '.' || $item === '..' || ($skipHidden && $item[0] === '.') || isset($exceptions[$item])) {
				continue;
			}

			$path = Folder::addPathElement($this->path, $item);
			if (is_dir($path)) {
				$dirs[] = $fullPath ? $path : $item;
			} else {
				$files[] = $fullPath ? $path : $item;
			}
		}

		if ($sort || $this->sort) {
			sort($dirs);
			sort($files);
		}

		closedir($dir);
		return array($dirs, $files);
	}
/**
 * Returns an array of all matching files in current directory.
 *
 * @param string $pattern Preg_match pattern (Defaults to: .*)
 * @return array Files that match given pattern
 * @access public
 */
	function find($regexpPattern = '.*', $sort = false) {
		list($dirs, $files) = $this->read($sort);
		return array_values(preg_grep('/^' . $regexpPattern . '$/i', $files)); ;
	}
/**
 * Returns an array of all matching files in and below current directory.
 *
 * @param string $pattern Preg_match pattern (Defaults to: .*)
 * @return array Files matching $pattern
 * @access public
 */
	function findRecursive($pattern = '.*', $sort = false) {
		$startsOn = $this->path;
		$out = $this->_findRecursive($pattern, $sort);
		$this->cd($startsOn);
		return $out;
	}
/**
 * Private helper function for findRecursive.
 *
 * @param string $pattern Pattern to match against
 * @return array Files matching pattern
 * @access private
 */
	function _findRecursive($pattern, $sort = false) {
		list($dirs, $files) = $this->read($sort);
		$found = array();

		foreach ($files as $file) {
			if (preg_match('/^' . $pattern . '$/i', $file)) {
				$found[] = Folder::addPathElement($this->path, $file);
			}
		}
		$start = $this->path;

		foreach ($dirs as $dir) {
			$this->cd(Folder::addPathElement($start, $dir));
			$found = array_merge($found, $this->findRecursive($pattern, $sort));
		}
		return $found;
	}
/**
 * Returns true if given $path is a Windows path.
 *
 * @param string $path Path to check
 * @return boolean true if windows path, false otherwise
 * @access public
 * @static
 */
	function isWindowsPath($path) {
		if (preg_match('/^[A-Z]:\\\\/i', $path)) {
			return true;
		}
		return false;
	}
/**
 * Returns true if given $path is an absolute path.
 *
 * @param string $path Path to check
 * @return bool
 * @access public
 * @static
 */
	function isAbsolute($path) {
		$match = preg_match('/^\\//', $path) || preg_match('/^[A-Z]:\\\\/i', $path);
		return $match;
	}
/**
 * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
 *
 * @param string $path Path to check
 * @return string Set of slashes ("\\" or "/")
 * @access public
 * @static
 */
	function normalizePath($path) {
		return Folder::correctSlashFor($path);
	}
/**
 * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
 *
 * @param string $path Path to check
 * @return string Set of slashes ("\\" or "/")
 * @access public
 * @static
 */
	function correctSlashFor($path) {
		if (Folder::isWindowsPath($path)) {
			return '\\';
		}
		return '/';
	}
/**
 * Returns $path with added terminating slash (corrected for Windows or other OS).
 *
 * @param string $path Path to check
 * @return string Path with ending slash
 * @access public
 * @static
 */
	function slashTerm($path) {
		if (Folder::isSlashTerm($path)) {
			return $path;
		}
		return $path . Folder::correctSlashFor($path);
	}
/**
 * Returns $path with $element added, with correct slash in-between.
 *
 * @param string $path Path
 * @param string $element Element to and at end of path
 * @return string Combined path
 * @access public
 * @static
 */
	function addPathElement($path, $element) {
		return Folder::slashTerm($path) . $element;
	}
/**
 * Returns true if the File is in a given CakePath.
 *
 * @return bool
 * @access public
 */
	function inCakePath($path = '') {
		$dir = substr(Folder::slashTerm(ROOT), 0, -1);
		$newdir = $dir . $path;

		return $this->inPath($newdir);
	}
/**
 * Returns true if the File is in given path.
 *
 * @return bool
 * @access public
 */
	function inPath($path = '', $reverse = false) {
		$dir = Folder::slashTerm($path);
		$current = Folder::slashTerm($this->pwd());

		if (!$reverse) {
			$return = preg_match('/^(.*)' . preg_quote($dir, '/') . '(.*)/', $current);
		} else {
			$return = preg_match('/^(.*)' . preg_quote($current, '/') . '(.*)/', $dir);
		}
		if ($return == 1) {
			return true;
		} else {
			return false;
		}
	}
/**
 * Change the mode on a directory structure recursively. This includes changing the mode on files as well.
 *
 * @param string $path The path to chmod
 * @param integer $mode octal value 0755
 * @param boolean $recursive chmod recursively
 * @param array $exceptions array of files, directories to skip
 * @return boolean Returns TRUE on success, FALSE on failure
 * @access public
 */
	function chmod($path, $mode = false, $recursive = true, $exceptions = array()) {
		if (!$mode) {
			$mode = $this->mode;
		}

		if ($recursive === false && is_dir($path)) {
			if (@chmod($path, intval($mode, 8))) {
				$this->__messages[] = sprintf(__('%s changed to %s', true), $path, $mode);
				return true;
			}

			$this->__errors[] = sprintf(__('%s NOT changed to %s', true), $path, $mode);
			return false;
		}

		if (is_dir($path)) {
			$paths = $this->tree($path);

			foreach ($paths as $type) {
				foreach ($type as $key => $fullpath) {
					$check = explode(DS, $fullpath);
					$count = count($check);

					if (in_array($check[$count - 1], $exceptions)) {
						continue;
					}

					if (@chmod($fullpath, intval($mode, 8))) {
						$this->__messages[] = sprintf(__('%s changed to %s', true), $fullpath, $mode);
					} else {
						$this->__errors[] = sprintf(__('%s NOT changed to %s', true), $fullpath, $mode);
					}
				}
			}

			if (empty($this->__errors)) {
				return true;
			}
		}
		return false;
	}
/**
 * Returns an array of nested directories and files in each directory
 *
 * @param string $path the directory path to build the tree from
 * @param boolean $hidden return hidden files and directories
 * @param string $type either file or dir. null returns both files and directories
 * @return mixed array of nested directories and files in each directory
 * @access public
 */
	function tree($path, $exceptions = true, $type = null) {
		$original = $this->path;
		$path = rtrim($path, DS);
		$this->__files = array();
		$this->__directories = array($path);
		$directories = array();

		if ($exceptions === false) {
			$exceptions = true;
		}
		while (count($this->__directories)) {
			$dir = array_pop($this->__directories);
			$this->__tree($dir, $exceptions);
			$directories[] = $dir;
		}

		if ($type === null) {
			return array($directories, $this->__files);
		}
		if ($type === 'dir') {
			return $directories;
		}
		$this->cd($original);

		return $this->__files;
	}
/**
 * Private method to list directories and files in each directory
 *
 * @param string $path
 * @param = boolean $hidden
 * @access private
 */
	function __tree($path, $exceptions) {
		if ($this->cd($path)) {
			list($dirs, $files) = $this->read(false, $exceptions, true);
			$this->__directories = array_merge($this->__directories, $dirs);
			$this->__files = array_merge($this->__files, $files);
		}
	}
/**
 * Create a directory structure recursively.
 *
 * @param string $pathname The directory structure to create
 * @param integer $mode octal value 0755
 * @return boolean Returns TRUE on success, FALSE on failure
 * @access public
 */
	function create($pathname, $mode = false) {
		if (is_dir($pathname) || empty($pathname)) {
			return true;
		}

		if (!$mode) {
			$mode = $this->mode;
		}

		if (is_file($pathname)) {
			$this->__errors[] = sprintf(__('%s is a file', true), $pathname);
			return false;
		}
		$nextPathname = substr($pathname, 0, strrpos($pathname, DS));

		if ($this->create($nextPathname, $mode)) {
			if (!file_exists($pathname)) {
				$old = umask(0);
				if (mkdir($pathname, $mode)) {
					umask($old);
					$this->__messages[] = sprintf(__('%s created', true), $pathname);
					return true;
				} else {
					umask($old);
					$this->__errors[] = sprintf(__('%s NOT created', true), $pathname);
					return false;
				}
			}
		}
		return true;
	}
/**
 * Returns the size in bytes of this Folder.
 *
 * @param string $directory Path to directory
 * @return int size in bytes of current folder
 * @access public
 */
	function dirsize() {
		$size = 0;
		$directory = Folder::slashTerm($this->path);
		$stack = array($directory);
		$count = count($stack);
		for ($i = 0, $j = $count; $i < $j; ++$i) {
			if (is_file($stack[$i])) {
				$size += filesize($stack[$i]);
			} elseif (is_dir($stack[$i])) {
				$dir = dir($stack[$i]);
				if ($dir) {
					while (false !== ($entry = $dir->read())) {
						if ($entry === '.' || $entry === '..') {
							continue;
						}
						$add = $stack[$i] . $entry;

						if (is_dir($stack[$i] . $entry)) {
							$add = Folder::slashTerm($add);
						}
						$stack[] = $add;
					}
					$dir->close();
				}
			}
			$j = count($stack);
		}
		return $size;
	}
/**
 * Recursively Remove directories if system allow.
 *
 * @param string $path Path of directory to delete
 * @return boolean Success
 * @access public
 */
	function delete($path = null) {
		if (!$path) {
			$path = $this->pwd();
		}
		$path = Folder::slashTerm($path);
		if (is_dir($path) === true) {
			$normalFiles = glob($path . '*');
			$hiddenFiles = glob($path . '\.?*');

			$normalFiles = $normalFiles ? $normalFiles : array();
			$hiddenFiles = $hiddenFiles ? $hiddenFiles : array();

			$files = array_merge($normalFiles, $hiddenFiles);
			if (is_array($files)) {
				foreach ($files as $file) {
					if (preg_match('/(\.|\.\.)$/', $file)) {
						continue;
					}
					if (is_file($file) === true) {
						if (@unlink($file)) {
							$this->__messages[] = sprintf(__('%s removed', true), $file);
						} else {
							$this->__errors[] = sprintf(__('%s NOT removed', true), $file);
						}
					} elseif (is_dir($file) === true && $this->delete($file) === false) {
						return false;
					}
				}
			}
			$path = substr($path, 0, strlen($path) - 1);
			if (rmdir($path) === false) {
				$this->__errors[] = sprintf(__('%s NOT removed', true), $path);
				return false;
			} else {
				$this->__messages[] = sprintf(__('%s removed', true), $path);
			}
		}
		return true;
	}
/**
 * Recursive directory copy.
 *
 * @param array $options (to, from, chmod, skip)
 * @return bool
 * @access public
 */
	function copy($options = array()) {
		$to = null;
		if (is_string($options)) {
			$to = $options;
			$options = array();
		}
		$options = array_merge(array('to' => $to, 'from' => $this->path, 'mode' => $this->mode, 'skip' => array()), $options);

		$fromDir = $options['from'];
		$toDir = $options['to'];
		$mode = $options['mode'];

		if (!$this->cd($fromDir)) {
			$this->__errors[] = sprintf(__('%s not found', true), $fromDir);
			return false;
		}

		if (!is_dir($toDir)) {
			$this->mkdir($toDir, $mode);
		}

		if (!is_writable($toDir)) {
			$this->__errors[] = sprintf(__('%s not writable', true), $toDir);
			return false;
		}

		$exceptions = array_merge(array('.', '..', '.svn'), $options['skip']);
		if ($handle = @opendir($fromDir)) {
			while (false !== ($item = readdir($handle))) {
				if (!in_array($item, $exceptions)) {
					$from = Folder::addPathElement($fromDir, $item);
					$to = Folder::addPathElement($toDir, $item);
					if (is_file($from)) {
						if (copy($from, $to)) {
							chmod($to, intval($mode, 8));
							touch($to, filemtime($from));
							$this->__messages[] = sprintf(__('%s copied to %s', true), $from, $to);
						} else {
							$this->__errors[] = sprintf(__('%s NOT copied to %s', true), $from, $to);
						}
					}

					if (is_dir($from) && !file_exists($to)) {
						$old = umask(0);
						if (mkdir($to, $mode)) {
							umask($old);
							$old = umask(0);
							chmod($to, $mode);
							umask($old);
							$this->__messages[] = sprintf(__('%s created', true), $to);
							$options = array_merge($options, array('to'=> $to, 'from'=> $from));
							$this->copy($options);
						} else {
							$this->__errors[] = sprintf(__('%s not created', true), $to);
						}
					}
				}
			}
			closedir($handle);
		} else {
			return false;
		}

		if (!empty($this->__errors)) {
			return false;
		}
		return true;
	}
/**
 * Recursive directory move.
 *
 * @param array $options (to, from, chmod, skip)
 * @return boolean Success
 * @access public
 */
	function move($options) {
		$to = null;
		if (is_string($options)) {
			$to = $options;
			$options = (array)$options;
		}
		$options = array_merge(array('to' => $to, 'from' => $this->path, 'mode' => $this->mode, 'skip' => array()), $options);

		if ($this->copy($options)) {
			if ($this->delete($options['from'])) {
				return $this->cd($options['to']);
			}
		}
		return false;
	}
/**
 * get messages from latest method
 *
 * @return array
 * @access public
 */
	function messages() {
		return $this->__messages;
	}
/**
 * get error from latest method
 *
 * @return array
 * @access public
 */
	function errors() {
		return $this->__errors;
	}
/**
 * nix flavored alias
 *
 * @see read
 * @access public
 */
	function ls($sort = true, $exceptions = false) {
		return $this->read($sort, $exceptions);
	}
/**
 * nix flavored alias
 *
 * @see create
 * @access public
 */
	function mkdir($pathname, $mode = 0755) {
		return $this->create($pathname, $mode);
	}
/**
 * nix flavored alias
 *
 * @see copy
 * @access public
 */
	function cp($options) {
		return $this->copy($options);
	}
/**
 * nix flavored alias
 *
 * @see move
 * @access public
 */
	function mv($options) {
		return $this->move($options);
	}
/**
 * nix flavored alias
 *
 * @see delete
 * @access public
 */
	function rm($path) {
		return $this->delete($path);
	}
/**
 * Get the real path (taking ".." and such into account)
 *
 * @param string $path Path to resolve
 * @return string The resolved path
 */
	function realpath($path) {
		$path = str_replace('/', DS, trim($path));
		if (strpos($path, '..') === false) {
			if (!Folder::isAbsolute($path)) {
				$path = Folder::addPathElement($this->path, $path);
			}
			return $path;
		}
		$parts = explode(DS, $path);
		$newparts = array();
		$newpath = '';
		if ($path[0] === DS) {
			$newpath = DS;
		}

		while (($part = array_shift($parts)) !== NULL) {
			if ($part === '.' || $part === '') {
				continue;
			}
			if ($part === '..') {
				if (count($newparts) > 0) {
					array_pop($newparts);
					continue;
				} else {
					return false;
				}
			}
			$newparts[] = $part;
		}
		$newpath .= implode(DS, $newparts);

		return Folder::slashTerm($newpath);
	}
/**
 * Returns true if given $path ends in a slash (i.e. is slash-terminated).
 *
 * @param string $path Path to check
 * @return boolean true if path ends with slash, false otherwise
 * @access public
 * @static
 */
	function isSlashTerm($path) {
		$lastChar = $path[strlen($path) - 1];
		return $lastChar === '/' || $lastChar === '\\';
	}
}
?>