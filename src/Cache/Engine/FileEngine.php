<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Utility\Inflector;
use Exception;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SplFileObject;

/**
 * File Storage engine for cache. Filestorage is the slowest cache storage
 * to read and write. However, it is good for servers that don't have other storage
 * engine available, or have content which is not performance sensitive.
 *
 * You can configure a FileEngine cache, using Cache::config()
 */
class FileEngine extends CacheEngine
{

    /**
     * Instance of SplFileObject class
     *
     * @var \SplFileObject|null
     */
    protected $_File;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `isWindows` Automatically populated with whether the host is windows or not
     * - `lock` Used by FileCache. Should files be locked before writing to them?
     * - `mask` The mask used for created files
     * - `path` Path to where cachefiles should be saved. Defaults to system's temp dir.
     * - `prefix` Prepended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     * - `serialize` Should cache objects be serialized first.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'isWindows' => false,
        'lock' => true,
        'mask' => 0664,
        'path' => null,
        'prefix' => 'cake_',
        'probability' => 100,
        'serialize' => true
    ];

    /**
     * True unless FileEngine::__active(); fails
     *
     * @var bool
     */
    protected $_init = true;

    /**
     * Initialize File Cache Engine
     *
     * Called automatically by the cache frontend.
     *
     * @param array $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = [])
    {
        parent::init($config);

        if ($this->_config['path'] === null) {
            $this->_config['path'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cake_cache' . DIRECTORY_SEPARATOR;
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->_config['isWindows'] = true;
        }
        if (substr($this->_config['path'], -1) !== DIRECTORY_SEPARATOR) {
            $this->_config['path'] .= DIRECTORY_SEPARATOR;
        }
        if ($this->_groupPrefix) {
            $this->_groupPrefix = str_replace('_', DIRECTORY_SEPARATOR, $this->_groupPrefix);
        }

        return $this->_active();
    }

    /**
     * Garbage collection. Permanently remove all expired and deleted data
     *
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return bool True if garbage collection was successful, false on failure
     */
    public function gc($expires = null)
    {
        return $this->clear(true);
    }

    /**
     * Write data for key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $data Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $data)
    {
        if ($data === '' || !$this->_init) {
            return false;
        }

        $key = $this->_key($key);

        if ($this->_setKey($key, true) === false) {
            return false;
        }

        $lineBreak = "\n";

        if ($this->_config['isWindows']) {
            $lineBreak = "\r\n";
        }

        if (!empty($this->_config['serialize'])) {
            if ($this->_config['isWindows']) {
                $data = str_replace('\\', '\\\\\\\\', serialize($data));
            } else {
                $data = serialize($data);
            }
        }

        $duration = $this->_config['duration'];
        $expires = time() + $duration;
        $contents = implode([$expires, $lineBreak, $data, $lineBreak]);

        if ($this->_config['lock']) {
            $this->_File->flock(LOCK_EX);
        }

        $this->_File->rewind();
        $success = $this->_File->ftruncate(0) &&
            $this->_File->fwrite($contents) &&
            $this->_File->fflush();

        if ($this->_config['lock']) {
            $this->_File->flock(LOCK_UN);
        }
        $this->_File = null;

        return $success;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has
     *   expired, or if there was an error fetching it
     */
    public function read($key)
    {
        $key = $this->_key($key);

        if (!$this->_init || $this->_setKey($key) === false) {
            return false;
        }

        if ($this->_config['lock']) {
            $this->_File->flock(LOCK_SH);
        }

        $this->_File->rewind();
        $time = time();
        $cachetime = (int)$this->_File->current();

        if ($cachetime < $time || ($time + $this->_config['duration']) < $cachetime) {
            if ($this->_config['lock']) {
                $this->_File->flock(LOCK_UN);
            }

            return false;
        }

        $data = '';
        $this->_File->next();
        while ($this->_File->valid()) {
            $data .= $this->_File->current();
            $this->_File->next();
        }

        if ($this->_config['lock']) {
            $this->_File->flock(LOCK_UN);
        }

        $data = trim($data);

        if ($data !== '' && !empty($this->_config['serialize'])) {
            if ($this->_config['isWindows']) {
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
     * @return bool True if the value was successfully deleted, false if it didn't
     *   exist or couldn't be removed
     */
    public function delete($key)
    {
        $key = $this->_key($key);

        if ($this->_setKey($key) === false || !$this->_init) {
            return false;
        }

        $path = $this->_File->getRealPath();
        $this->_File = null;

        //@codingStandardsIgnoreStart
        return @unlink($path);
        //@codingStandardsIgnoreEnd
    }

    /**
     * Delete all values from the cache
     *
     * @param bool $check Optional - only delete expired cache items
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear($check)
    {
        if (!$this->_init) {
            return false;
        }
        $this->_File = null;

        $threshold = $now = false;
        if ($check) {
            $now = time();
            $threshold = $now - $this->_config['duration'];
        }

        $this->_clearDirectory($this->_config['path'], $now, $threshold);

        $directory = new RecursiveDirectoryIterator($this->_config['path']);
        $contents = new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::SELF_FIRST
        );
        $cleared = [];
        foreach ($contents as $path) {
            if ($path->isFile()) {
                continue;
            }

            $path = $path->getRealPath() . DIRECTORY_SEPARATOR;
            if (!in_array($path, $cleared)) {
                $this->_clearDirectory($path, $now, $threshold);
                $cleared[] = $path;
            }
        }

        return true;
    }

    /**
     * Used to clear a directory of matching files.
     *
     * @param string $path The path to search.
     * @param int $now The current timestamp
     * @param int $threshold Any file not modified after this value will be deleted.
     * @return void
     */
    protected function _clearDirectory($path, $now, $threshold)
    {
        if (!is_dir($path)) {
            return;
        }
        $prefixLength = strlen($this->_config['prefix']);

        $dir = dir($path);
        while (($entry = $dir->read()) !== false) {
            if (substr($entry, 0, $prefixLength) !== $this->_config['prefix']) {
                continue;
            }

            try {
                $file = new SplFileObject($path . $entry, 'r');
            } catch (Exception $e) {
                continue;
            }

            if ($threshold) {
                $mtime = $file->getMTime();
                if ($mtime > $threshold) {
                    continue;
                }

                $expires = (int)$file->current();
                if ($expires > $now) {
                    continue;
                }
            }
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $file = null;

                //@codingStandardsIgnoreStart
                @unlink($filePath);
                //@codingStandardsIgnoreEnd
            }
        }
    }

    /**
     * Not implemented
     *
     * @param string $key The key to decrement
     * @param int $offset The number to offset
     * @return void
     * @throws \LogicException
     */
    public function decrement($key, $offset = 1)
    {
        throw new LogicException('Files cannot be atomically decremented.');
    }

    /**
     * Not implemented
     *
     * @param string $key The key to increment
     * @param int $offset The number to offset
     * @return void
     * @throws \LogicException
     */
    public function increment($key, $offset = 1)
    {
        throw new LogicException('Files cannot be atomically incremented.');
    }

    /**
     * Sets the current cache key this class is managing, and creates a writable SplFileObject
     * for the cache file the key is referring to.
     *
     * @param string $key The key
     * @param bool $createKey Whether the key should be created if it doesn't exists, or not
     * @return bool true if the cache key could be set, false otherwise
     */
    protected function _setKey($key, $createKey = false)
    {
        $groups = null;
        if ($this->_groupPrefix) {
            $groups = vsprintf($this->_groupPrefix, $this->groups());
        }
        $dir = $this->_config['path'] . $groups;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = new SplFileInfo($dir . $key);

        if (!$createKey && !$path->isFile()) {
            return false;
        }
        if (empty($this->_File) || $this->_File->getBasename() !== $key) {
            $exists = file_exists($path->getPathname());
            try {
                $this->_File = $path->openFile('c+');
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);

                return false;
            }
            unset($path);

            if (!$exists && !chmod($this->_File->getPathname(), (int)$this->_config['mask'])) {
                trigger_error(sprintf(
                    'Could not apply permission mask "%s" on cache file "%s"',
                    $this->_File->getPathname(),
                    $this->_config['mask']
                ), E_USER_WARNING);
            }
        }

        return true;
    }

    /**
     * Determine if cache directory is writable
     *
     * @return bool
     */
    protected function _active()
    {
        $dir = new SplFileInfo($this->_config['path']);
        $path = $dir->getPathname();
        $success = true;
        if (!is_dir($path)) {
            //@codingStandardsIgnoreStart
            $success = @mkdir($path, 0775, true);
            //@codingStandardsIgnoreEnd
        }

        $isWritableDir = ($dir->isDir() && $dir->isWritable());
        if (!$success || ($this->_init && !$isWritableDir)) {
            $this->_init = false;
            trigger_error(sprintf(
                '%s is not writable',
                $this->_config['path']
            ), E_USER_WARNING);
        }

        return $success;
    }

    /**
     * Generates a safe key for use with cache engine storage engines.
     *
     * @param string $key the key passed over
     * @return mixed string $key or false
     */
    public function key($key)
    {
        if (empty($key)) {
            return false;
        }

        $key = Inflector::underscore(str_replace(
            [DIRECTORY_SEPARATOR, '/', '.', '<', '>', '?', ':', '|', '*', '"'],
            '_',
            (string)$key
        ));

        return $key;
    }

    /**
     * Recursively deletes all files under any directory named as $group
     *
     * @param string $group The group to clear.
     * @return bool success
     */
    public function clearGroup($group)
    {
        $this->_File = null;
        $directoryIterator = new RecursiveDirectoryIterator($this->_config['path']);
        $contents = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($contents as $object) {
            $containsGroup = strpos($object->getPathname(), DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR) !== false;
            $hasPrefix = true;
            if (strlen($this->_config['prefix']) !== 0) {
                $hasPrefix = strpos($object->getBasename(), $this->_config['prefix']) === 0;
            }
            if ($object->isFile() && $containsGroup && $hasPrefix) {
                $path = $object->getPathname();
                $object = null;
                //@codingStandardsIgnoreStart
                @unlink($path);
                //@codingStandardsIgnoreEnd
            }
        }

        return true;
    }
}
