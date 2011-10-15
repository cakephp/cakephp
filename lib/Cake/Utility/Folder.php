<?php
/**
 * Convenience class for handling directories.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Folder structure browser, lists folders and files.
 * Provides an Object interface for Common directory related tasks.
 *
 * @package       Cake.Utility
 */
class Folder {

/**
 * Path to Folder.
 *
 * @var string
 */
	public $path = null;

/**
 * Sortedness. Whether or not list results
 * should be sorted by name.
 *
 * @var boolean
 */
	public $sort = false;

/**
 * Mode to be used on create. Does nothing on windows platforms.
 *
 * @var integer
 */
	public $mode = 0755;

/**
 * Holds messages from last method.
 *
 * @var array
 */
	protected $_messages = array();

/**
 * Holds errors from last method.
 *
 * @var array
 */
	protected $_errors = array();

/**
 * Holds array of complete directory paths.
 *
 * @var array
 */
	protected $_directories;

/**
 * Holds array of complete file paths.
 *
 * @var array
 */
	protected $_files;

/**
 * Constructor.
 *
 * @param string $path Path to folder
 * @param boolean $create Create folder if not found
 * @param mixed $mode Mode (CHMOD) to apply to created folder, false to ignore
 */
	public function __construct($path = false, $create = false, $mode = false) {
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
 */
	public function pwd() {
		return $this->path;
	}

/**
 * Change directory to $path.
 *
 * @param string $path Path to the directory to change to
 * @return string The new path. Returns false on failure
 */
	public function cd($path) {
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
 * @param boolean $sort Whether you want the results sorted, set this and the sort property
 *   to false to get unsorted results.
 * @param mixed $exceptions Either an array or boolean true will not grab dot files
 * @param boolean $fullPath True returns the full path
 * @return mixed Contents of current directory as an array, an empty array on failure
 */
	public function read($sort = true, $exceptions = false, $fullPath = false) {
		$dirs = $files = array();

		if (!$this->pwd()) {
			return array($dirs, $files);
		}
		if (is_array($exceptions)) {
			$exceptions = array_flip($exceptions);
		}
		$skipHidden = isset($exceptions['.']) || $exceptions === true;

		try {
			$iterator = new DirectoryIterator($this->path);
		} catch (UnexpectedValueException $e) {
			return array($dirs, $files);
		}

		foreach ($iterator as $item) {
			if ($item->isDot()) {
				continue;
			}
			$name = $item->getFileName();
			if ($skipHidden && $name[0] === '.' || isset($exceptions[$name])) {
				continue;
			}
			if ($fullPath) {
				$name = $item->getPathName();
			}
			if ($item->isDir()) {
				$dirs[] = $name;
			} else {
				$files[] = $name;
			}
		}
		if ($sort || $this->sort) {
			sort($dirs);
			sort($files);
		}
		return array($dirs, $files);
	}

/**
 * Returns an array of all matching files in current directory.
 *
 * @param string $regexpPattern Preg_match pattern (Defaults to: .*)
 * @param boolean $sort Whether results should be sorted.
 * @return array Files that match given pattern
 */
	public function find($regexpPattern = '.*', $sort = false) {
		list($dirs, $files) = $this->read($sort);
		return array_values(preg_grep('/^' . $regexpPattern . '$/i', $files)); ;
	}

/**
 * Returns an array of all matching files in and below current directory.
 *
 * @param string $pattern Preg_match pattern (Defaults to: .*)
 * @param boolean $sort Whether results should be sorted.
 * @return array Files matching $pattern
 */
	public function findRecursive($pattern = '.*', $sort = false) {
		if (!$this->pwd()) {
			return array();
		}
		$startsOn = $this->path;
		$out = $this->_findRecursive($pattern, $sort);
		$this->cd($startsOn);
		return $out;
	}

/**
 * Private helper function for findRecursive.
 *
 * @param string $pattern Pattern to match against
 * @param boolean $sort Whether results should be sorted.
 * @return array Files matching pattern
 */
	protected function _findRecursive($pattern, $sort = false) {
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
 */
	public static function isWindowsPath($path) {
		return (preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) == '\\\\');
	}

/**
 * Returns true if given $path is an absolute path.
 *
 * @param string $path Path to check
 * @return boolean true if path is absolute.
 */
	public static function isAbsolute($path) {
		return !empty($path) && ($path[0] === '/' || preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) == '\\\\');
	}

/**
 * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
 *
 * @param string $path Path to check
 * @return string Set of slashes ("\\" or "/")
 */
	public static function normalizePath($path) {
		return Folder::correctSlashFor($path);
	}

/**
 * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
 *
 * @param string $path Path to check
 * @return string Set of slashes ("\\" or "/")
 */
	public static function correctSlashFor($path) {
		return (Folder::isWindowsPath($path)) ? '\\' : '/';
	}

/**
 * Returns $path with added terminating slash (corrected for Windows or other OS).
 *
 * @param string $path Path to check
 * @return string Path with ending slash
 */
	public static function slashTerm($path) {
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
 */
	public static function addPathElement($path, $element) {
		return rtrim($path, DS) . DS . $element;
	}

/**
 * Returns true if the File is in a given CakePath.
 *
 * @param string $path The path to check.
 * @return boolean
 */
	public function inCakePath($path = '') {
		$dir = substr(Folder::slashTerm(ROOT), 0, -1);
		$newdir = $dir . $path;

		return $this->inPath($newdir);
	}

/**
 * Returns true if the File is in given path.
 *
 * @param string $path The path to check that the current pwd() resides with in.
 * @param boolean $reverse
 * @return boolean
 */
	public function inPath($path = '', $reverse = false) {
		$dir = Folder::slashTerm($path);
		$current = Folder::slashTerm($this->pwd());

		if (!$reverse) {
			$return = preg_match('/^(.*)' . preg_quote($dir, '/') . '(.*)/', $current);
		} else {
			$return = preg_match('/^(.*)' . preg_quote($current, '/') . '(.*)/', $dir);
		}
		return (bool)$return;
	}

/**
 * Change the mode on a directory structure recursively. This includes changing the mode on files as well.
 *
 * @param string $path The path to chmod
 * @param integer $mode octal value 0755
 * @param boolean $recursive chmod recursively, set to false to only change the current directory.
 * @param array $exceptions array of files, directories to skip
 * @return boolean Returns TRUE on success, FALSE on failure
 */
	public function chmod($path, $mode = false, $recursive = true, $exceptions = array()) {
		if (!$mode) {
			$mode = $this->mode;
		}

		if ($recursive === false && is_dir($path)) {
			if (@chmod($path, intval($mode, 8))) {
				$this->_messages[] = __d('cake_dev', '%s changed to %s', $path, $mode);
				return true;
			}

			$this->_errors[] = __d('cake_dev', '%s NOT changed to %s', $path, $mode);
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
						$this->_messages[] = __d('cake_dev', '%s changed to %s', $fullpath, $mode);
					} else {
						$this->_errors[] = __d('cake_dev', '%s NOT changed to %s', $fullpath, $mode);
					}
				}
			}

			if (empty($this->_errors)) {
				return true;
			}
		}
		return false;
	}

/**
 * Returns an array of nested directories and files in each directory
 *
 * @param string $path the directory path to build the tree from
 * @param mixed $exceptions Array of files to exclude, defaults to excluding hidden files.
 * @param string $type either file or dir. null returns both files and directories
 * @return mixed array of nested directories and files in each directory
 */
	public function tree($path = null, $exceptions = true, $type = null) {
		if ($path == null) {
			$path = $this->path;
		}
		$files = array();
		$directories = array($path);
		$skipHidden = false;

		if ($exceptions === false) {
			$skipHidden = true;
		}
		if (is_array($exceptions)) {
			$exceptions = array_flip($exceptions);
		}

		try {
			$directory = new RecursiveDirectoryIterator($path);
			$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
		} catch (UnexpectedValueException $e) {
			if ($type === null) {
				return array(array(), array());
			}
			return array();
		}
		foreach ($iterator as $item) {
			$name = $item->getFileName();
			if ($skipHidden && $name[0] === '.' || isset($exceptions[$name])) {
				continue;
			}
			if ($item->isFile()) {
				$files[] = $item->getPathName();
			} else if ($item->isDir() && !in_array($name, array('.', '..'))) {
				$directories[] = $item->getPathName();
			}
		}
		if ($type === null) {
			return array($directories, $files);
		}
		if ($type === 'dir') {
			return $directories;
		}
		return $files;
	}

/**
 * Private method to list directories and files in each directory
 *
 * @param string $path The Path to read.
 * @param mixed $exceptions Array of files to exclude from the read that will be performed.
 * @return void
 */
	protected function _tree($path, $exceptions) {
		$this->path = $path;
		list($dirs, $files) = $this->read(false, $exceptions, true);
		$this->_directories = array_merge($this->_directories, $dirs);
		$this->_files = array_merge($this->_files, $files);
	}

/**
 * Create a directory structure recursively. Can be used to create
 * deep path structures like `/foo/bar/baz/shoe/horn`
 *
 * @param string $pathname The directory structure to create
 * @param integer $mode octal value 0755
 * @return boolean Returns TRUE on success, FALSE on failure
 */
	public function create($pathname, $mode = false) {
		if (is_dir($pathname) || empty($pathname)) {
			return true;
		}

		if (!$mode) {
			$mode = $this->mode;
		}

		if (is_file($pathname)) {
			$this->_errors[] = __d('cake_dev', '%s is a file', $pathname);
			return false;
		}
		$pathname = rtrim($pathname, DS);
		$nextPathname = substr($pathname, 0, strrpos($pathname, DS));

		if ($this->create($nextPathname, $mode)) {
			if (!file_exists($pathname)) {
				$old = umask(0);
				if (mkdir($pathname, $mode)) {
					umask($old);
					$this->_messages[] = __d('cake_dev', '%s created', $pathname);
					return true;
				} else {
					umask($old);
					$this->_errors[] = __d('cake_dev', '%s NOT created', $pathname);
					return false;
				}
			}
		}
		return false;
	}

/**
 * Returns the size in bytes of this Folder and its contents.
 *
 * @return integer size in bytes of current folder
 */
	public function dirsize() {
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
 * Recursively Remove directories if the system allows.
 *
 * @param string $path Path of directory to delete
 * @return boolean Success
 */
	public function delete($path = null) {
		if (!$path) {
			$path = $this->pwd();
		}
		if (!$path) {
			return null;
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
							$this->_messages[] = __d('cake_dev', '%s removed', $file);
						} else {
							$this->_errors[] = __d('cake_dev', '%s NOT removed', $file);
						}
					} elseif (is_dir($file) === true && $this->delete($file) === false) {
						return false;
					}
				}
			}
			$path = substr($path, 0, strlen($path) - 1);
			if (rmdir($path) === false) {
				$this->_errors[] = __d('cake_dev', '%s NOT removed', $path);
				return false;
			} else {
				$this->_messages[] = __d('cake_dev', '%s removed', $path);
			}
		}
		return true;
	}

/**
 * Recursive directory copy.
 *
 * ### Options
 *
 * - `to` The directory to copy to.
 * - `from` The directory to copy from, this will cause a cd() to occur, changing the results of pwd().
 * - `mode` The mode to copy the files/directories with.
 * - `skip` Files/directories to skip.
 *
 * @param mixed $options Either an array of options (see above) or a string of the destination directory.
 * @return boolean Success
 */
	public function copy($options = array()) {
		if (!$this->pwd()) {
			return false;
		}
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
			$this->_errors[] = __d('cake_dev', '%s not found', $fromDir);
			return false;
		}

		if (!is_dir($toDir)) {
			$this->create($toDir, $mode);
		}

		if (!is_writable($toDir)) {
			$this->_errors[] = __d('cake_dev', '%s not writable', $toDir);
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
							$this->_messages[] = __d('cake_dev', '%s copied to %s', $from, $to);
						} else {
							$this->_errors[] = __d('cake_dev', '%s NOT copied to %s', $from, $to);
						}
					}

					if (is_dir($from) && !file_exists($to)) {
						$old = umask(0);
						if (mkdir($to, $mode)) {
							umask($old);
							$old = umask(0);
							chmod($to, $mode);
							umask($old);
							$this->_messages[] = __d('cake_dev', '%s created', $to);
							$options = array_merge($options, array('to'=> $to, 'from'=> $from));
							$this->copy($options);
						} else {
							$this->_errors[] = __d('cake_dev', '%s not created', $to);
						}
					}
				}
			}
			closedir($handle);
		} else {
			return false;
		}

		if (!empty($this->_errors)) {
			return false;
		}
		return true;
	}

/**
 * Recursive directory move.
 *
 * ### Options
 *
 * - `to` The directory to copy to.
 * - `from` The directory to copy from, this will cause a cd() to occur, changing the results of pwd().
 * - `chmod` The mode to copy the files/directories with.
 * - `skip` Files/directories to skip.
 *
 * @param array $options (to, from, chmod, skip)
 * @return boolean Success
 */
	public function move($options) {
		$to = null;
		if (is_string($options)) {
			$to = $options;
			$options = (array)$options;
		}
		$options = array_merge(
			array('to' => $to, 'from' => $this->path, 'mode' => $this->mode, 'skip' => array()),
			$options
		);

		if ($this->copy($options)) {
			if ($this->delete($options['from'])) {
				return (bool)$this->cd($options['to']);
			}
		}
		return false;
	}

/**
 * get messages from latest method
 *
 * @return array
 */
	public function messages() {
		return $this->_messages;
	}

/**
 * get error from latest method
 *
 * @return array
 */
	public function errors() {
		return $this->_errors;
	}

/**
 * Get the real path (taking ".." and such into account)
 *
 * @param string $path Path to resolve
 * @return string The resolved path
 */
	public function realpath($path) {
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
				if (!empty($newparts)) {
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
 */
	public static function isSlashTerm($path) {
		$lastChar = $path[strlen($path) - 1];
		return $lastChar === '/' || $lastChar === '\\';
	}
}
