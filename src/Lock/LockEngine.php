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
namespace Cake\Lock;

use Cake\Core\InstanceConfigTrait;
use Cake\Lock\Exception\InvalidArgumentException;

/**
 * Abstract base class for lock engines.
 *
 * Provides common functionality for all lock engine implementations,
 * including configuration management and key generation.
 */
abstract class LockEngine implements LockInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * - `prefix`: Prefix for all lock keys. Useful for namespacing.
     * - `ttl`: Default time-to-live in seconds for locks.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'prefix' => 'lock_',
        'ttl' => 300,
    ];

    /**
     * Initialize the lock engine.
     *
     * @param array<string, mixed> $config Configuration options.
     * @return bool True if initialization was successful.
     */
    public function init(array $config = []): bool
    {
        $this->setConfig($config);

        return true;
    }

    /**
     * Generate a unique token for lock ownership.
     *
     * @return string A unique token string.
     */
    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate the full key for a resource.
     *
     * @param string $resource The resource identifier.
     * @return string The prefixed key.
     * @throws \Cake\Lock\Exception\InvalidArgumentException If resource is invalid.
     */
    protected function key(string $resource): string
    {
        $this->ensureValidResource($resource);

        $key = preg_replace('/[\s]+/', '_', $resource);

        return $this->getConfig('prefix') . $key;
    }

    /**
     * Ensure the resource identifier is valid.
     *
     * @param string $resource The resource to validate.
     * @return void
     * @throws \Cake\Lock\Exception\InvalidArgumentException If resource is invalid.
     */
    protected function ensureValidResource(string $resource): void
    {
        if ($resource === '') {
            throw new InvalidArgumentException('Lock resource must be a non-empty string.');
        }
    }

    /**
     * Acquire a lock, waiting up to $timeout seconds if necessary.
     *
     * This is a default blocking implementation that repeatedly
     * attempts to acquire the lock. Engines may override this
     * with more efficient implementations.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds for the lock.
     * @param int $timeout Maximum time in seconds to wait for the lock.
     * @param int $retryInterval Milliseconds to wait between retry attempts.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on timeout.
     */
    public function acquireBlocking(
        string $resource,
        int $ttl = 300,
        int $timeout = 10,
        int $retryInterval = 100,
    ): ?AcquiredLock {
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $lock = $this->acquire($resource, $ttl);
            if ($lock !== null) {
                return $lock;
            }

            usleep($retryInterval * 1000);
        }

        return null;
    }
}
