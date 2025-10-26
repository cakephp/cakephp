<?php
declare(strict_types=1);

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
use Cake\Cache\Event\CacheAfterDeleteEvent;
use Cake\Cache\Event\CacheAfterGetEvent;
use Cake\Cache\Event\CacheAfterSetEvent;
use Cake\Cache\Event\CacheBeforeDeleteEvent;
use Cake\Cache\Event\CacheBeforeGetEvent;
use Cake\Cache\Event\CacheBeforeSetEvent;
use Cake\Cache\Event\CacheClearedEvent;
use Cake\Cache\Event\CacheGroupClearEvent;
use CallbackFilterIterator;
use DateInterval;
use Exception;
use FilesystemIterator;
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
     * @var \SplFileObject
     */
    protected SplFileObject $File;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `lock` Used by FileCache. Should files be locked before writing to them?
     * - `mask` The mask used for created files
     * - `dirMask` The mask used for created folders
     * - `path` Path to where cache files should be saved. Defaults to system's temp dir.
     * - `prefix` Prepended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `serialize` Should cache objects be serialized first.
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'lock' => true,
        'mask' => 0664,
        'dirMask' => 0777,
        'path' => null,
        'prefix' => 'cake_',
        'serialize' => true,
    ];

    /**
     * True unless FileEngine::__active(); fails
     *
     * @var bool
     */
    protected bool $init = true;

    /**
     * Initialize File Cache Engine
     *
     * Called automatically by the cache frontend.
     *
     * @param array<string, mixed> $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = []): bool
    {
        parent::init($config);

        $this->config['path'] ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cake_cache' . DIRECTORY_SEPARATOR;
        if (substr($this->config['path'], -1) !== DIRECTORY_SEPARATOR) {
            $this->config['path'] .= DIRECTORY_SEPARATOR;
        }
        if ($this->groupPrefix) {
            $this->groupPrefix = str_replace('_', DIRECTORY_SEPARATOR, $this->groupPrefix);
        }

        return $this->active();
    }

    /**
     * Write data for key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($value === '' || !$this->init) {
            return false;
        }

        $key = $this->key($key);
        $this->eventClass = CacheBeforeSetEvent::class;
        $this->dispatchEvent(CacheBeforeSetEvent::NAME, ['key' => $key, 'value' => $value, 'ttl' => $ttl]);

        $this->eventClass = CacheAfterSetEvent::class;
        if ($this->setKey($key, true) === false) {
            $this->dispatchEvent(CacheAfterSetEvent::NAME, [
                'key' => $key, 'value' => $value, 'success' => false,
            ]);

            return false;
        }

        $origValue = $value;
        if (!empty($this->config['serialize'])) {
            $value = serialize($value);
        }

        $expires = time() + $this->duration($ttl);
        $contents = implode('', [$expires, PHP_EOL, $value, PHP_EOL]);

        if ($this->config['lock']) {
            $this->File->flock(LOCK_EX);
        }

        $this->File->rewind();
        $success = $this->File->ftruncate(0) &&
            $this->File->fwrite($contents) &&
            $this->File->fflush();

        if ($this->config['lock']) {
            $this->File->flock(LOCK_UN);
        }
        unset($this->File);

        $this->dispatchEvent(CacheAfterSetEvent::NAME, [
            'key' => $key, 'value' => $origValue, 'success' => $success,
        ]);

        return $success;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or default value if the data doesn't exist, has
     *   expired, or if there was an error fetching it
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->key($key);
        $this->eventClass = CacheBeforeGetEvent::class;
        $this->dispatchEvent(CacheBeforeGetEvent::NAME, ['key' => $key, 'default' => $default]);

        $this->eventClass = CacheAfterGetEvent::class;
        if (!$this->init || $this->setKey($key) === false) {
            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        if ($this->config['lock']) {
            $this->File->flock(LOCK_SH);
        }

        $this->File->rewind();
        $time = time();
        $cachetime = (int)$this->File->current();

        if ($cachetime < $time) {
            if ($this->config['lock']) {
                $this->File->flock(LOCK_UN);
            }
            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        $data = '';
        $this->File->next();
        while ($this->File->valid()) {
            $data .= $this->File->current();
            $this->File->next();
        }

        if ($this->config['lock']) {
            $this->File->flock(LOCK_UN);
        }

        $data = trim($data);

        if ($data !== '' && !empty($this->config['serialize'])) {
            $data = unserialize($data);
            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => $data, 'success' => true]);

            return $data;
        }

        $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => $data, 'success' => true]);

        return $data;
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't
     *   exist or couldn't be removed
     */
    public function delete(string $key): bool
    {
        $key = $this->key($key);
        $this->eventClass = CacheBeforeDeleteEvent::class;
        $this->dispatchEvent(CacheBeforeDeleteEvent::NAME, ['key' => $key]);

        $this->eventClass = CacheAfterDeleteEvent::class;
        if ($this->setKey($key) === false || !$this->init) {
            $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => false]);

            return false;
        }

        $path = $this->File->getRealPath();
        unset($this->File);

        if ($path === false) {
            $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => false]);

            return false;
        }

        $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => true]);

        // phpcs:disable
        return @unlink($path);
        // phpcs:enable
    }

    /**
     * Delete all values from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear(): bool
    {
        if (!$this->init) {
            return false;
        }
        unset($this->File);

        $this->clearDirectory($this->config['path']);

        $directory = new RecursiveDirectoryIterator(
            $this->config['path'],
            FilesystemIterator::SKIP_DOTS,
        );
        $iterator = new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::SELF_FIRST,
        );
        $cleared = [];
        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                unset($fileInfo);
                continue;
            }

            $realPath = $fileInfo->getRealPath();
            if (!$realPath) {
                unset($fileInfo);
                continue;
            }

            $path = $realPath . DIRECTORY_SEPARATOR;
            if (!in_array($path, $cleared, true)) {
                $this->clearDirectory($path);
                $cleared[] = $path;
            }

            // possible inner iterators need to be unset too in order for locks on parents to be released
            unset($fileInfo);
        }

        // unsetting iterators helps releasing possible locks in certain environments,
        // which could otherwise make `rmdir()` fail
        unset($directory, $iterator);
        $this->eventClass = CacheClearedEvent::class;
        $this->dispatchEvent(CacheClearedEvent::NAME);

        return true;
    }

    /**
     * Used to clear a directory of matching files.
     *
     * @param string $path The path to search.
     * @return void
     */
    protected function clearDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $dir = dir($path);
        if (!$dir) {
            return;
        }

        $prefixLength = strlen($this->config['prefix']);

        while (($entry = $dir->read()) !== false) {
            if (substr($entry, 0, $prefixLength) !== $this->config['prefix']) {
                continue;
            }

            try {
                $file = new SplFileObject($path . $entry, 'r');
            } catch (Exception) {
                continue;
            }

            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                unset($file);

                // phpcs:disable
                @unlink($filePath);
                // phpcs:enable
            }
        }

        $dir->close();
    }

    /**
     * Not implemented
     *
     * @param string $key The key to decrement
     * @param int $offset The number to offset
     * @return int|false
     * @throws \LogicException
     */
    public function decrement(string $key, int $offset = 1): int|false
    {
        throw new LogicException('Files cannot be atomically decremented.');
    }

    /**
     * Not implemented
     *
     * @param string $key The key to increment
     * @param int $offset The number to offset
     * @return int|false
     * @throws \LogicException
     */
    public function increment(string $key, int $offset = 1): int|false
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
    protected function setKey(string $key, bool $createKey = false): bool
    {
        $groups = null;
        if ($this->groupPrefix) {
            $groups = vsprintf($this->groupPrefix, $this->groups());
        }
        $dir = $this->config['path'] . $groups;

        if (!is_dir($dir)) {
            mkdir($dir, $this->config['dirMask'] ^ umask(), true);
        }

        $path = new SplFileInfo($dir . $key);

        if (!$createKey && !$path->isFile()) {
            return false;
        }
        if (
            !isset($this->File) ||
            $this->File->getBasename() !== $key ||
            $this->File->valid() === false
        ) {
            $exists = is_file($path->getPathname());
            try {
                $this->File = $path->openFile('c+');
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);

                return false;
            }
            unset($path);

            if (!$exists && !chmod($this->File->getPathname(), (int)$this->config['mask'])) {
                trigger_error(sprintf(
                    'Could not apply permission mask `%s` on cache file `%s`',
                    $this->File->getPathname(),
                    $this->config['mask'],
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
    protected function active(): bool
    {
        $dir = new SplFileInfo($this->config['path']);
        $path = $dir->getPathname();
        $success = true;
        if (!is_dir($path)) {
            // phpcs:disable
            $success = @mkdir($path, $this->config['dirMask'] ^ umask(), true);
            // phpcs:enable
        }

        $isWritableDir = ($dir->isDir() && $dir->isWritable());
        if (!$success || ($this->init && !$isWritableDir)) {
            $this->init = false;
            trigger_error(sprintf(
                '%s is not writable',
                $this->config['path'],
            ), E_USER_WARNING);
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    protected function key(string $key): string
    {
        $key = parent::key($key);

        return rawurlencode($key);
    }

    /**
     * Recursively deletes all files under any directory named as $group
     *
     * @param string $group The group to clear.
     * @return bool success
     */
    public function clearGroup(string $group): bool
    {
        unset($this->File);

        $prefix = (string)$this->config['prefix'];

        $directoryIterator = new RecursiveDirectoryIterator($this->config['path']);
        $contents = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        $filtered = new CallbackFilterIterator(
            $contents,
            function (SplFileInfo $current) use ($group, $prefix) {
                if (!$current->isFile()) {
                    return false;
                }

                $hasPrefix = $prefix === '' || str_starts_with($current->getBasename(), $prefix);
                if ($hasPrefix === false) {
                    return false;
                }

                return str_contains(
                    $current->getPathname(),
                    DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR,
                );
            },
        );
        /** @var \SplFileInfo $object */
        foreach ($filtered as $object) {
            $path = $object->getPathname();
            unset($object);
            // phpcs:ignore
            @unlink($path);
        }

        // unsetting iterators helps releasing possible locks in certain environments,
        // which could otherwise make `rmdir()` fail
        unset($directoryIterator, $contents, $filtered);
        $this->eventClass = CacheGroupClearEvent::class;
        $this->dispatchEvent(CacheGroupClearEvent::NAME, ['group' => $group]);

        return true;
    }
}
