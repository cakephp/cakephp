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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Brick\VarExporter\VarExporter;
use Cake\Cache\CacheEngine;
use Cake\Cache\Event\CacheAfterDeleteEvent;
use Cake\Cache\Event\CacheAfterGetEvent;
use Cake\Cache\Event\CacheAfterSetEvent;
use Cake\Cache\Event\CacheBeforeDeleteEvent;
use Cake\Cache\Event\CacheBeforeGetEvent;
use Cake\Cache\Event\CacheBeforeSetEvent;
use Cake\Cache\Event\CacheClearedEvent;
use Cake\Cache\Event\CacheGroupClearEvent;
use DateInterval;
use LogicException;
use SplFileInfo;
use Throwable;

/**
 * PHP Cache Engine
 *
 * Stores cache data as executable PHP files using brick/varexporter.
 * This enables OPcache acceleration for extremely fast reads.
 *
 * Best suited for:
 * - Schema metadata
 * - Attribute resolver cache
 * - Route cache
 * - Configuration data
 *
 * Not recommended for frequently changing data due to slower writes.
 *
 * Note: When caching complex objects (custom classes, closures, resources),
 * consult https://github.com/brick/varexporter for supported types and limitations.
 * Most common data structures (arrays, scalars, stdClass, enums) are fully supported.
 */
class PhpEngine extends CacheEngine
{
    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `duration` Specify how long items in this cache configuration last.
     *    0 means indefinite (recommended for deploy-time caches).
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     * - `mask` The mask used for created files
     * - `dirMask` The mask used for created folders
     * - `path` Path to where cache files should be saved. Defaults to system's temp dir.
     * - `prefix` Prepended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
        'duration' => 0,
        'groups' => [],
        'mask' => 0664,
        'dirMask' => 0777,
        'path' => null,
        'prefix' => 'cake_',
    ];

    /**
     * True unless PhpEngine::active() fails
     *
     * @var bool
     */
    protected bool $init = true;

    /**
     * Initialize the cache engine
     *
     * @param array<string, mixed> $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = []): bool
    {
        parent::init($config);

        $this->config['path'] ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cake_php_cache' . DIRECTORY_SEPARATOR;
        if (!str_ends_with($this->config['path'], DIRECTORY_SEPARATOR)) {
            $this->config['path'] .= DIRECTORY_SEPARATOR;
        }

        if ($this->groupPrefix) {
            $this->groupPrefix = str_replace('_', DIRECTORY_SEPARATOR, $this->groupPrefix);
        }

        return $this->active();
    }

    /**
     * Write data for key into cache as a PHP file
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item.
     * @return bool True on success and false on failure.
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if (!$this->init) {
            return false;
        }

        $duration = $this->duration($ttl);
        $key = $this->key($key);

        $this->eventClass = CacheBeforeSetEvent::class;
        $this->dispatchEvent(CacheBeforeSetEvent::NAME, ['key' => $key, 'value' => $value, 'ttl' => $duration]);

        $this->eventClass = CacheAfterSetEvent::class;

        $path = $this->path($key);

        try {
            $exported = VarExporter::export($value);
        } catch (Throwable $e) {
            trigger_error(sprintf(
                'PhpEngine failed to export value for key `%s`: %s',
                $key,
                $e->getMessage(),
            ), E_USER_WARNING);

            $this->dispatchEvent(CacheAfterSetEvent::NAME, [
                'key' => $key, 'value' => $value, 'success' => false, 'ttl' => $duration,
            ]);

            return false;
        }

        // Generate PHP code with optional expiration
        if ($duration > 0) {
            $expires = time() + $duration;
            $code = "<?php\n// Expires: {$expires}\nreturn time() > {$expires} ? null : {$exported};\n";
        } else {
            $code = "<?php\nreturn {$exported};\n";
        }

        $success = $this->writeFile($path, $code);

        $this->dispatchEvent(CacheAfterSetEvent::NAME, [
            'key' => $key, 'value' => $value, 'success' => $success, 'ttl' => $duration,
        ]);

        return $success;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or default value if the data doesn't exist or has expired
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->key($key);

        $this->eventClass = CacheBeforeGetEvent::class;
        $this->dispatchEvent(CacheBeforeGetEvent::NAME, ['key' => $key, 'default' => $default]);

        $this->eventClass = CacheAfterGetEvent::class;

        if (!$this->init) {
            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        $path = $this->path($key);

        if (!is_file($path)) {
            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        try {
            $value = require $path;
        } catch (Throwable $e) {
            // Corrupt cache file
            trigger_error(sprintf(
                'PhpEngine failed to read cache file `%s`: %s',
                $path,
                $e->getMessage(),
            ), E_USER_WARNING);
            $this->deleteFile($path);

            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        // null means expired (from the generated code)
        if ($value === null) {
            $this->deleteFile($path);

            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => $value, 'success' => true]);

        return $value;
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public function delete(string $key): bool
    {
        $key = $this->key($key);

        $this->eventClass = CacheBeforeDeleteEvent::class;
        $this->dispatchEvent(CacheBeforeDeleteEvent::NAME, ['key' => $key]);

        $this->eventClass = CacheAfterDeleteEvent::class;

        if (!$this->init) {
            $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => false]);

            return false;
        }

        $path = $this->path($key);
        $success = $this->deleteFile($path);

        $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => $success]);

        return $success;
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

        $this->clearDirectory($this->config['path']);

        $this->eventClass = CacheClearedEvent::class;
        $this->dispatchEvent(CacheClearedEvent::NAME);

        return true;
    }

    /**
     * Not implemented - PHP files cannot be atomically incremented
     *
     * @param string $key The key to increment
     * @param int $offset The number to offset
     * @return int|false
     * @throws \LogicException
     */
    public function increment(string $key, int $offset = 1): int|false
    {
        throw new LogicException('PhpEngine does not support atomic increment.');
    }

    /**
     * Not implemented - PHP files cannot be atomically decremented
     *
     * @param string $key The key to decrement
     * @param int $offset The number to offset
     * @return int|false
     * @throws \LogicException
     */
    public function decrement(string $key, int $offset = 1): int|false
    {
        throw new LogicException('PhpEngine does not support atomic decrement.');
    }

    /**
     * Recursively deletes all files under any directory named as $group
     *
     * @param string $group The group to clear.
     * @return bool success
     */
    public function clearGroup(string $group): bool
    {
        $path = $this->config['path'] . $group . DIRECTORY_SEPARATOR;

        if (is_dir($path)) {
            $this->clearDirectory($path);
        }

        $this->eventClass = CacheGroupClearEvent::class;
        $this->dispatchEvent(CacheGroupClearEvent::NAME, ['group' => $group]);

        return true;
    }

    /**
     * Generate the file path for a cache key
     *
     * @param string $key The cache key
     * @return string The file path
     */
    protected function path(string $key): string
    {
        $groups = null;
        if ($this->groupPrefix) {
            $groups = vsprintf($this->groupPrefix, $this->groups());
        }

        $dir = $this->config['path'] . $groups;

        if (!is_dir($dir)) {
            mkdir($dir, $this->config['dirMask'] ^ umask(), true);
        }

        return $dir . $key . '.php';
    }

    /**
     * Write content to file atomically
     *
     * @param string $path File path
     * @param string $content File content
     * @return bool Success
     */
    protected function writeFile(string $path, string $content): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, $this->config['dirMask'] ^ umask(), true);
        }

        // Write to temp file first for atomic operation
        $tmpFile = $path . '.tmp.' . uniqid('', true);

        if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
            return false;
        }

        chmod($tmpFile, $this->config['mask']);

        // Atomic rename
        if (!rename($tmpFile, $path)) {
            // phpcs:ignore
            @unlink($tmpFile);

            return false;
        }

        // Invalidate OPcache if available
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        return true;
    }

    /**
     * Delete a cache file
     *
     * @param string $path File path
     * @return bool Success
     */
    protected function deleteFile(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        // Invalidate OPcache before deletion
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        // phpcs:ignore
        return @unlink($path);
    }

    /**
     * Clear all cache files in a directory matching the prefix
     *
     * @param string $path Directory path
     * @return void
     */
    protected function clearDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $prefix = $this->config['prefix'];
        $prefixLength = strlen($prefix);

        $dir = dir($path);
        if (!$dir) {
            return;
        }

        while (($entry = $dir->read()) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $path . $entry;

            if (is_dir($fullPath)) {
                $this->clearDirectory($fullPath . DIRECTORY_SEPARATOR);
                // phpcs:ignore
                @rmdir($fullPath);
                continue;
            }

            // Only delete files matching prefix
            if (substr($entry, 0, $prefixLength) !== $prefix) {
                continue;
            }

            $this->deleteFile($fullPath);
        }

        $dir->close();
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
            // phpcs:ignore
            $success = @mkdir($path, $this->config['dirMask'] ^ umask(), true);
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
}
