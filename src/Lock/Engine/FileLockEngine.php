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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Lock\Engine;

use Cake\Lock\AcquiredLock;
use Cake\Lock\LockEngine;

/**
 * File-based lock engine using flock().
 *
 * This engine uses PHP's flock() function for locking, which provides
 * advisory locking on the filesystem. This is suitable for single-server
 * deployments but NOT for distributed systems.
 *
 * Note: This engine does not support TTL-based automatic expiration.
 * Locks are held until explicitly released, the acquired lock is destroyed,
 * or the process terminates. A cleanup mechanism is provided for stale lock files.
 *
 * ### Configuration options:
 *
 * - `path`: Directory path for lock files (default: system temp directory)
 * - `prefix`: Prefix for lock file names (default: 'lock_')
 * - `ttl`: Default TTL for stale file cleanup (default: 300)
 */
class FileLockEngine extends LockEngine
{
    /**
     * Active lock file handles.
     *
     * @var array<string, resource>
     */
    protected array $_handles = [];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'path' => '',
        'prefix' => 'lock_',
        'ttl' => 300,
    ];

    /**
     * Initialize the file lock engine.
     *
     * @param array<string, mixed> $config Configuration options.
     * @return bool True if initialization was successful.
     */
    public function init(array $config = []): bool
    {
        parent::init($config);

        if (empty($this->_config['path'])) {
            $this->_config['path'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cake_locks';
        }

        // Ensure lock directory exists
        if (!is_dir($this->_config['path'])) {
            mkdir($this->_config['path'], 0777, true);
        }

        return true;
    }

    /**
     * Get the file path for a lock.
     *
     * @param string $resource The resource identifier.
     * @return string The lock file path.
     */
    protected function getLockFile(string $resource): string
    {
        $key = $this->key($resource);
        // Make filename safe
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

        return $this->_config['path'] . DIRECTORY_SEPARATOR . $safeKey . '.lock';
    }

    /**
     * Acquire a lock for the given resource.
     *
     * Uses flock() with LOCK_EX | LOCK_NB for non-blocking exclusive lock.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds (used for stale file cleanup).
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on failure.
     */
    public function acquire(string $resource, int $ttl = 300): ?AcquiredLock
    {
        $file = $this->getLockFile($resource);
        $token = $this->generateToken();

        // Clean up stale lock file if it exists and is old
        $this->cleanupStaleLock($file, $ttl);

        $handle = fopen($file, 'c+');
        if ($handle === false) {
            return null;
        }

        // Try to acquire exclusive lock (non-blocking)
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        // Write lock metadata
        ftruncate($handle, 0);
        rewind($handle);
        $data = json_encode([
            'token' => $token,
            'ttl' => $ttl,
            'acquired_at' => microtime(true),
        ]);
        assert($data !== false);
        fwrite($handle, $data);
        fflush($handle);

        // Store handle for later release
        $this->_handles[$resource] = $handle;

        return new AcquiredLock($resource, $token, $ttl, microtime(true), $this);
    }

    /**
     * Clean up a stale lock file if it's older than TTL.
     *
     * @param string $file The lock file path.
     * @param int $ttl TTL in seconds.
     * @return void
     */
    protected function cleanupStaleLock(string $file, int $ttl): void
    {
        if (!file_exists($file)) {
            return;
        }

        $mtime = filemtime($file);
        if ($mtime !== false && (time() - $mtime) > $ttl) {
            @unlink($file);
        }
    }

    /**
     * Release a lock.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(AcquiredLock $lock): bool
    {
        $resource = $lock->getResource();

        if (!isset($this->_handles[$resource])) {
            return false;
        }

        $handle = $this->_handles[$resource];

        // Verify ownership
        rewind($handle);
        $content = stream_get_contents($handle);
        if ($content !== false) {
            $data = json_decode($content, true);
            if (isset($data['token']) && $data['token'] !== $lock->getToken()) {
                fclose($handle);
                unset($this->_handles[$resource]);

                return false;
            }
        }

        // Close handle to release the advisory lock.
        fclose($handle);
        unset($this->_handles[$resource]);

        // Remove lock file
        $file = $this->getLockFile($resource);
        @unlink($file);

        return true;
    }

    /**
     * Check if a resource is currently locked.
     *
     * @param string $resource The resource identifier to check.
     * @return bool True if the resource is locked, false otherwise.
     */
    public function isLocked(string $resource): bool
    {
        $file = $this->getLockFile($resource);

        if (!file_exists($file)) {
            return false;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            return false;
        }

        // Try to acquire lock - if it fails, the resource is locked
        $locked = !flock($handle, LOCK_EX | LOCK_NB);
        fclose($handle);

        return $locked;
    }

    /**
     * Refresh a lock's TTL.
     *
     * Updates the lock file's metadata with a new TTL.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to refresh.
     * @param int|null $ttl New TTL in seconds. If null, uses the original TTL.
     * @return bool True if the lock was refreshed, false otherwise.
     */
    public function refresh(AcquiredLock $lock, ?int $ttl = null): bool
    {
        $resource = $lock->getResource();

        if (!isset($this->_handles[$resource])) {
            return false;
        }

        $handle = $this->_handles[$resource];
        $ttl ??= $lock->getTtl();

        // Update lock metadata
        ftruncate($handle, 0);
        rewind($handle);
        $data = json_encode([
            'token' => $lock->getToken(),
            'ttl' => $ttl,
            'acquired_at' => microtime(true),
        ]);
        assert($data !== false);
        fwrite($handle, $data);
        fflush($handle);

        // Touch the file to update mtime for stale detection
        touch($this->getLockFile($resource));

        return true;
    }

    /**
     * Force release a lock without ownership verification.
     *
     * @param string $resource The resource identifier to force release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function forceRelease(string $resource): bool
    {
        $file = $this->getLockFile($resource);

        // Close handle if we have one
        if (isset($this->_handles[$resource])) {
            fclose($this->_handles[$resource]);
            unset($this->_handles[$resource]);
        }

        // Remove lock file
        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Destructor to clean up open handles.
     */
    public function __destruct()
    {
        foreach ($this->_handles as $handle) {
            fclose($handle);
        }
        $this->_handles = [];
    }
}
