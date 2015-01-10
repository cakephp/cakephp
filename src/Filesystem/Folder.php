<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Filesystem;

/**
 * Folder structure browser, lists folders and files.
 * Provides an Object interface for Common directory related tasks.
 *
 * @link http://book.cakephp.org/3.0/en/core-libraries/file-folder.html#folder-api
 */
class Folder
{

    /**
     * Default scheme for Folder::copy
     * Recursively merges subfolders with the same name
     *
     * @var string
     */
    const MERGE = 'merge';

    /**
     * Overwrite scheme for Folder::copy
     * subfolders with the same name will be replaced
     *
     * @var string
     */
    const OVERWRITE = 'overwrite';

    /**
     * Skip scheme for Folder::copy
     * if a subfolder with the same name exists it will be skipped
     *
     * @var string
     */
    const SKIP = 'skip';

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
     * @var bool
     */
    public $sort = false;

    /**
     * Mode to be used on create. Does nothing on windows platforms.
     *
     * @var int
     * http://book.cakephp.org/3.0/en/core-libraries/file-folder.html#Cake\Filesystem\Folder::$mode
     */
    public $mode = 0755;

    /**
     * Holds messages from last method.
     *
     * @var array
     */
    protected $_messages = [];

    /**
     * Holds errors from last method.
     *
     * @var array
     */
    protected $_errors = [];

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
     * @param string|null $path Path to folder
     * @param bool $create Create folder if not found
     * @param int|bool $mode Mode (CHMOD) to apply to created folder, false to ignore
     */
    public function __construct($path = null, $create = false, $mode = false)
    {
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
    public function pwd()
    {
        return $this->path;
    }

    /**
     * Change directory to $path.
     *
     * @param string $path Path to the directory to change to
     * @return string The new path. Returns false on failure
     */
    public function cd($path)
    {
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
     * @param bool $sort Whether you want the results sorted, set this and the sort property
     *   to false to get unsorted results.
     * @param array|bool $exceptions Either an array or boolean true will not grab dot files
     * @param bool $fullPath True returns the full path
     * @return mixed Contents of current directory as an array, an empty array on failure
     */
    public function read($sort = true, $exceptions = false, $fullPath = false)
    {
        $dirs = $files = [];

        if (!$this->pwd()) {
            return [$dirs, $files];
        }
        if (is_array($exceptions)) {
            $exceptions = array_flip($exceptions);
        }
        $skipHidden = isset($exceptions['.']) || $exceptions === true;

        try {
            $iterator = new \DirectoryIterator($this->path);
        } catch (\Exception $e) {
            return [$dirs, $files];
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
        return [$dirs, $files];
    }

    /**
     * Returns an array of all matching files in current directory.
     *
     * @param string $regexpPattern Preg_match pattern (Defaults to: .*)
     * @param bool $sort Whether results should be sorted.
     * @return array Files that match given pattern
     */
    public function find($regexpPattern = '.*', $sort = false)
    {
        list(, $files) = $this->read($sort);
        return array_values(preg_grep('/^' . $regexpPattern . '$/i', $files));
    }

    /**
     * Returns an array of all matching files in and below current directory.
     *
     * @param string $pattern Preg_match pattern (Defaults to: .*)
     * @param bool $sort Whether results should be sorted.
     * @return array Files matching $pattern
     */
    public function findRecursive($pattern = '.*', $sort = false)
    {
        if (!$this->pwd()) {
            return [];
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
     * @param bool $sort Whether results should be sorted.
     * @return array Files matching pattern
     */
    protected function _findRecursive($pattern, $sort = false)
    {
        list($dirs, $files) = $this->read($sort);
        $found = [];

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
     * @return bool true if windows path, false otherwise
     */
    public static function isWindowsPath($path)
    {
        return (preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\');
    }

    /**
     * Returns true if given $path is an absolute path.
     *
     * @param string $path Path to check
     * @return bool true if path is absolute.
     */
    public static function isAbsolute($path)
    {
        if (empty($path)) {
            return false;
        }

        return $path[0] === '/' ||
            preg_match('/^[A-Z]:\\\\/i', $path) ||
            substr($path, 0, 2) === '\\\\' ||
            self::isRegisteredStreamWrapper($path);
    }

    /**
     * Returns true if given $path is a registered stream wrapper.
     *
     * @param string $path Path to check
     * @return bool True if path is registered stream wrapper.
     */
    public static function isRegisteredStreamWrapper($path)
    {
        if (preg_match('/^[A-Z]+(?=:\/\/)/i', $path, $matches) &&
            in_array($matches[0], stream_get_wrappers())
        ) {
            return true;
        }
        return false;
    }

    /**
     * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
     *
     * @param string $path Path to check
     * @return string Set of slashes ("\\" or "/")
     */
    public static function normalizePath($path)
    {
        return Folder::correctSlashFor($path);
    }

    /**
     * Returns a correct set of slashes for given $path. (\\ for Windows paths and / for other paths.)
     *
     * @param string $path Path to check
     * @return string Set of slashes ("\\" or "/")
     */
    public static function correctSlashFor($path)
    {
        return (Folder::isWindowsPath($path)) ? '\\' : '/';
    }

    /**
     * Returns $path with added terminating slash (corrected for Windows or other OS).
     *
     * @param string $path Path to check
     * @return string Path with ending slash
     */
    public static function slashTerm($path)
    {
        if (Folder::isSlashTerm($path)) {
            return $path;
        }
        return $path . Folder::correctSlashFor($path);
    }

    /**
     * Returns $path with $element added, with correct slash in-between.
     *
     * @param string $path Path
     * @param string|array $element Element to add at end of path
     * @return string Combined path
     */
    public static function addPathElement($path, $element)
    {
        $element = (array)$element;
        array_unshift($element, rtrim($path, DS));
        return implode(DS, $element);
    }

    /**
     * Returns true if the File is in a given CakePath.
     *
     * @param string $path The path to check.
     * @return bool
     */
    public function inCakePath($path = '')
    {
        $dir = substr(Folder::slashTerm(ROOT), 0, -1);
        $newdir = $dir . $path;

        return $this->inPath($newdir);
    }

    /**
     * Returns true if the File is in given path.
     *
     * @param string $path The path to check that the current pwd() resides with in.
     * @param bool $reverse Reverse the search, check that pwd() resides within $path.
     * @return bool
     */
    public function inPath($path = '', $reverse = false)
    {
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
     * @param string $path The path to chmod.
     * @param int|bool $mode Octal value, e.g. 0755.
     * @param bool $recursive Chmod recursively, set to false to only change the current directory.
     * @param array $exceptions Array of files, directories to skip.
     * @return bool Success.
     */
    public function chmod($path, $mode = false, $recursive = true, array $exceptions = [])
    {
        if (!$mode) {
            $mode = $this->mode;
        }

        if ($recursive === false && is_dir($path)) {
            //@codingStandardsIgnoreStart
            if (@chmod($path, intval($mode, 8))) {
                //@codingStandardsIgnoreEnd
                $this->_messages[] = sprintf('%s changed to %s', $path, $mode);
                return true;
            }

            $this->_errors[] = sprintf('%s NOT changed to %s', $path, $mode);
            return false;
        }

        if (is_dir($path)) {
            $paths = $this->tree($path);

            foreach ($paths as $type) {
                foreach ($type as $fullpath) {
                    $check = explode(DS, $fullpath);
                    $count = count($check);

                    if (in_array($check[$count - 1], $exceptions)) {
                        continue;
                    }

                    //@codingStandardsIgnoreStart
                    if (@chmod($fullpath, intval($mode, 8))) {
                        //@codingStandardsIgnoreEnd
                        $this->_messages[] = sprintf('%s changed to %s', $fullpath, $mode);
                    } else {
                        $this->_errors[] = sprintf('%s NOT changed to %s', $fullpath, $mode);
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
     * @param string|null $path the directory path to build the tree from
     * @param array|bool $exceptions Either an array of files/folder to exclude
     *   or boolean true to not grab dot files/folders
     * @param string|null $type either 'file' or 'dir'. Null returns both files and directories
     * @return mixed array of nested directories and files in each directory
     */
    public function tree($path = null, $exceptions = false, $type = null)
    {
        if (!$path) {
            $path = $this->path;
        }
        $files = [];
        $directories = [$path];

        if (is_array($exceptions)) {
            $exceptions = array_flip($exceptions);
        }
        $skipHidden = false;
        if ($exceptions === true) {
            $skipHidden = true;
        } elseif (isset($exceptions['.'])) {
            $skipHidden = true;
            unset($exceptions['.']);
        }

        try {
            $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::KEY_AS_PATHNAME | \RecursiveDirectoryIterator::CURRENT_AS_SELF);
            $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        } catch (\Exception $e) {
            if ($type === null) {
                return [[], []];
            }
            return [];
        }

        foreach ($iterator as $itemPath => $fsIterator) {
            if ($skipHidden) {
                $subPathName = $fsIterator->getSubPathname();
                if ($subPathName{0} === '.' || strpos($subPathName, DS . '.') !== false) {
                    continue;
                }
            }
            $item = $fsIterator->current();
            if (!empty($exceptions) && isset($exceptions[$item->getFilename()])) {
                continue;
            }

            if ($item->isFile()) {
                $files[] = $itemPath;
            } elseif ($item->isDir() && !$item->isDot()) {
                $directories[] = $itemPath;
            }
        }
        if ($type === null) {
            return [$directories, $files];
        }
        if ($type === 'dir') {
            return $directories;
        }
        return $files;
    }

    /**
     * Create a directory structure recursively.
     *
     * Can be used to create deep path structures like `/foo/bar/baz/shoe/horn`
     *
     * @param string $pathname The directory structure to create. Either an absolute or relative
     *   path. If the path is relative and exists in the process' cwd it will not be created.
     *   Otherwise relative paths will be prefixed with the current pwd().
     * @param int $mode octal value 0755
     * @return bool Returns TRUE on success, FALSE on failure
     */
    public function create($pathname, $mode = false)
    {
        if (is_dir($pathname) || empty($pathname)) {
            return true;
        }

        if (!self::isAbsolute($pathname)) {
            $pathname = self::addPathElement($this->pwd(), $pathname);
        }

        if (!$mode) {
            $mode = $this->mode;
        }

        if (is_file($pathname)) {
            $this->_errors[] = sprintf('%s is a file', $pathname);
            return false;
        }
        $pathname = rtrim($pathname, DS);
        $nextPathname = substr($pathname, 0, strrpos($pathname, DS));

        if ($this->create($nextPathname, $mode)) {
            if (!file_exists($pathname)) {
                $old = umask(0);
                if (mkdir($pathname, $mode, true)) {
                    umask($old);
                    $this->_messages[] = sprintf('%s created', $pathname);
                    return true;
                }
                umask($old);
                $this->_errors[] = sprintf('%s NOT created', $pathname);
                return false;
            }
        }
        return false;
    }

    /**
     * Returns the size in bytes of this Folder and its contents.
     *
     * @return int size in bytes of current folder
     */
    public function dirsize()
    {
        $size = 0;
        $directory = Folder::slashTerm($this->path);
        $stack = [$directory];
        $count = count($stack);
        for ($i = 0, $j = $count; $i < $j; ++$i) {
            if (is_file($stack[$i])) {
                $size += filesize($stack[$i]);
            } elseif (is_dir($stack[$i])) {
                $dir = dir($stack[$i]);
                if ($dir) {
                    while (($entry = $dir->read()) !== false) {
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
     * @param string|null $path Path of directory to delete
     * @return bool Success
     */
    public function delete($path = null)
    {
        if (!$path) {
            $path = $this->pwd();
        }
        if (!$path) {
            return false;
        }
        $path = Folder::slashTerm($path);
        if (is_dir($path)) {
            try {
                $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::CURRENT_AS_SELF);
                $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
            } catch (\Exception $e) {
                return false;
            }

            foreach ($iterator as $item) {
                $filePath = $item->getPathname();
                if ($item->isFile() || $item->isLink()) {
                    //@codingStandardsIgnoreStart
                    if (@unlink($filePath)) {
                        //@codingStandardsIgnoreEnd
                        $this->_messages[] = sprintf('%s removed', $filePath);
                    } else {
                        $this->_errors[] = sprintf('%s NOT removed', $filePath);
                    }
                } elseif ($item->isDir() && !$item->isDot()) {
                    //@codingStandardsIgnoreStart
                    if (@rmdir($filePath)) {
                        //@codingStandardsIgnoreEnd
                        $this->_messages[] = sprintf('%s removed', $filePath);
                    } else {
                        $this->_errors[] = sprintf('%s NOT removed', $filePath);
                        return false;
                    }
                }
            }

            $path = rtrim($path, DS);
            //@codingStandardsIgnoreStart
            if (@rmdir($path)) {
                //@codingStandardsIgnoreEnd
                $this->_messages[] = sprintf('%s removed', $path);
            } else {
                $this->_errors[] = sprintf('%s NOT removed', $path);
                return false;
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
     * - `mode` The mode to copy the files/directories with as integer, e.g. 0775.
     * - `skip` Files/directories to skip.
     * - `scheme` Folder::MERGE, Folder::OVERWRITE, Folder::SKIP
     *
     * @param array|string $options Either an array of options (see above) or a string of the destination directory.
     * @return bool Success.
     */
    public function copy($options)
    {
        if (!$this->pwd()) {
            return false;
        }
        $to = null;
        if (is_string($options)) {
            $to = $options;
            $options = [];
        }
        $options += [
            'to' => $to,
            'from' => $this->path,
            'mode' => $this->mode,
            'skip' => [],
            'scheme' => Folder::MERGE
        ];

        $fromDir = $options['from'];
        $toDir = $options['to'];
        $mode = $options['mode'];

        if (!$this->cd($fromDir)) {
            $this->_errors[] = sprintf('%s not found', $fromDir);
            return false;
        }

        if (!is_dir($toDir)) {
            $this->create($toDir, $mode);
        }

        if (!is_writable($toDir)) {
            $this->_errors[] = sprintf('%s not writable', $toDir);
            return false;
        }

        $exceptions = array_merge(['.', '..', '.svn'], $options['skip']);
        //@codingStandardsIgnoreStart
        if ($handle = @opendir($fromDir)) {
            //@codingStandardsIgnoreEnd
            while (($item = readdir($handle)) !== false) {
                $to = Folder::addPathElement($toDir, $item);
                if (($options['scheme'] != Folder::SKIP || !is_dir($to)) && !in_array($item, $exceptions)) {
                    $from = Folder::addPathElement($fromDir, $item);
                    if (is_file($from) && (!is_file($to) || $options['scheme'] != Folder::SKIP)) {
                        if (copy($from, $to)) {
                            chmod($to, intval($mode, 8));
                            touch($to, filemtime($from));
                            $this->_messages[] = sprintf('%s copied to %s', $from, $to);
                        } else {
                            $this->_errors[] = sprintf('%s NOT copied to %s', $from, $to);
                        }
                    }

                    if (is_dir($from) && file_exists($to) && $options['scheme'] === Folder::OVERWRITE) {
                        $this->delete($to);
                    }

                    if (is_dir($from) && !file_exists($to)) {
                        $old = umask(0);
                        if (mkdir($to, $mode, true)) {
                            umask($old);
                            $old = umask(0);
                            chmod($to, $mode);
                            umask($old);
                            $this->_messages[] = sprintf('%s created', $to);
                            $options = ['to' => $to, 'from' => $from] + $options;
                            $this->copy($options);
                        } else {
                            $this->_errors[] = sprintf('%s not created', $to);
                        }
                    } elseif (is_dir($from) && $options['scheme'] === Folder::MERGE) {
                        $options = ['to' => $to, 'from' => $from] + $options;
                        $this->copy($options);
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
     * - `scheme` Folder::MERGE, Folder::OVERWRITE, Folder::SKIP
     *
     * @param array|string $options (to, from, chmod, skip, scheme)
     * @return bool Success
     */
    public function move($options)
    {
        $to = null;
        if (is_string($options)) {
            $to = $options;
            $options = (array)$options;
        }
        $options += ['to' => $to, 'from' => $this->path, 'mode' => $this->mode, 'skip' => []];

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
     * @param bool $reset Reset message stack after reading
     * @return array
     */
    public function messages($reset = true)
    {
        $messages = $this->_messages;
        if ($reset) {
            $this->_messages = [];
        }
        return $messages;
    }

    /**
     * get error from latest method
     *
     * @param bool $reset Reset error stack after reading
     * @return array
     */
    public function errors($reset = true)
    {
        $errors = $this->_errors;
        if ($reset) {
            $this->_errors = [];
        }
        return $errors;
    }

    /**
     * Get the real path (taking ".." and such into account)
     *
     * @param string $path Path to resolve
     * @return string The resolved path
     */
    public function realpath($path)
    {
        $path = str_replace('/', DS, trim($path));
        if (strpos($path, '..') === false) {
            if (!Folder::isAbsolute($path)) {
                $path = Folder::addPathElement($this->path, $path);
            }
            return $path;
        }
        $parts = explode(DS, $path);
        $newparts = [];
        $newpath = '';
        if ($path[0] === DS) {
            $newpath = DS;
        }

        while (($part = array_shift($parts)) !== null) {
            if ($part === '.' || $part === '') {
                continue;
            }
            if ($part === '..') {
                if (!empty($newparts)) {
                    array_pop($newparts);
                    continue;
                }
                return false;
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
     * @return bool true if path ends with slash, false otherwise
     */
    public static function isSlashTerm($path)
    {
        $lastChar = $path[strlen($path) - 1];
        return $lastChar === '/' || $lastChar === '\\';
    }
}
