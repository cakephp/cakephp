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

use Cake\Core\StaticConfigTrait;
use Cake\Lock\Engine\NullLockEngine;
use Cake\Lock\Exception\InvalidArgumentException;
use Closure;
use RuntimeException;

/**
 * Lock provides a consistent interface to distributed locking.
 *
 * ### Configuring Lock engines
 *
 * You can configure Lock engines in your application's bootstrap:
 *
 * ```
 * Lock::setConfig('default', [
 *    'className' => \Cake\Lock\Engine\RedisLockEngine::class,
 *    'host' => '127.0.0.1',
 *    'port' => 6379,
 * ]);
 * ```
 *
 * ### Usage examples
 *
 * Prefer `synchronized()` when you can, as it guarantees prompt release:
 *
 * ```
 * $result = Lock::synchronized('my-resource', function () {
 *     // Critical section
 *     return $computedValue;
 * });
 * ```
 *
 * Acquiring and releasing a lock:
 *
 * ```
 * $lock = Lock::acquire('my-resource');
 * if ($lock !== null) {
 *     try {
 *         // Critical section
 *     } finally {
 *         $lock->release();
 *     }
 * }
 * ```
 */
class Lock
{
    use StaticConfigTrait;

    /**
     * DSN class map for lock engines.
     *
     * @var array<string, string>
     * @phpstan-var array<string, class-string>
     */
    protected static array $_dsnClassMap = [
        'file' => Engine\FileLockEngine::class,
        'memcached' => Engine\MemcachedLockEngine::class,
        'null' => Engine\NullLockEngine::class,
        'redis' => Engine\RedisLockEngine::class,
    ];

    /**
     * Lock Registry for managing engine instances.
     *
     * @var \Cake\Lock\LockRegistry<\Cake\Lock\LockEngine>
     */
    protected static LockRegistry $_registry;

    /**
     * Returns the Lock Registry instance.
     *
     * @return \Cake\Lock\LockRegistry<\Cake\Lock\LockEngine>
     */
    public static function getRegistry(): LockRegistry
    {
        return static::$_registry ??= new LockRegistry();
    }

    /**
     * Sets the Lock Registry instance.
     *
     * @param \Cake\Lock\LockRegistry<\Cake\Lock\LockEngine> $registry Injectable registry object.
     * @return void
     */
    public static function setRegistry(LockRegistry $registry): void
    {
        static::$_registry = $registry;
    }

    /**
     * Build and get a lock engine instance.
     *
     * @param string $name Name of the configuration.
     * @throws \Cake\Lock\Exception\InvalidArgumentException When configuration doesn't exist.
     * @throws \RuntimeException If engine loading fails.
     * @return void
     */
    protected static function _buildEngine(string $name): void
    {
        $registry = static::getRegistry();

        if (empty(static::$_config[$name]['className'])) {
            throw new InvalidArgumentException(
                sprintf('The `%s` lock configuration does not exist.', $name),
            );
        }

        $config = static::$_config[$name];

        try {
            $registry->load($name, $config);
        } catch (RuntimeException $e) {
            $registry->set($name, new NullLockEngine());
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * Get a lock engine instance.
     *
     * @param string $config The name of the configured lock backend.
     * @return \Cake\Lock\LockInterface
     */
    public static function engine(string $config): LockInterface
    {
        $registry = static::getRegistry();

        if ($registry->has($config)) {
            return $registry->get($config);
        }

        static::_buildEngine($config);

        return $registry->get($config);
    }

    /**
     * Acquire a lock for the given resource.
     *
     * Prefer `synchronized()` when possible. The returned lock can be released
     * directly and will make a best-effort attempt to release itself on destruction.
     *
     * @param string $resource The resource identifier to lock.
     * @param int|null $ttl Time-to-live in seconds. Null uses engine default.
     * @param string $config Configuration name. Defaults to 'default'.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on failure.
     */
    public static function acquire(string $resource, ?int $ttl = null, string $config = 'default'): ?AcquiredLock
    {
        $ttl ??= static::getConfig($config)['ttl'] ?? 300;

        return static::engine($config)->acquire($resource, $ttl);
    }

    /**
     * Acquire a lock, waiting up to $timeout seconds if necessary.
     *
     * @param string $resource The resource identifier to lock.
     * @param int|null $ttl Time-to-live in seconds for the lock.
     * @param int $timeout Maximum time in seconds to wait for the lock.
     * @param int $retryInterval Milliseconds to wait between retry attempts.
     * @param string $config Configuration name. Defaults to 'default'.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on timeout.
     */
    public static function acquireBlocking(
        string $resource,
        ?int $ttl = null,
        int $timeout = 10,
        int $retryInterval = 100,
        string $config = 'default',
    ): ?AcquiredLock {
        $ttl ??= static::getConfig($config)['ttl'] ?? 300;

        return static::engine($config)->acquireBlocking($resource, $ttl, $timeout, $retryInterval);
    }

    /**
     * Release a lock.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to release.
     * @return bool True if the lock was released, false otherwise.
     */
    public static function release(AcquiredLock $lock): bool
    {
        return $lock->release();
    }

    /**
     * Check if a resource is currently locked.
     *
     * @param string $resource The resource identifier to check.
     * @param string $config Configuration name. Defaults to 'default'.
     * @return bool True if the resource is locked, false otherwise.
     */
    public static function isLocked(string $resource, string $config = 'default'): bool
    {
        return static::engine($config)->isLocked($resource);
    }

    /**
     * Refresh a lock's TTL.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to refresh.
     * @param int|null $ttl New TTL in seconds. If null, uses the original TTL.
     * @return bool True if the lock was refreshed, false otherwise.
     */
    public static function refresh(AcquiredLock $lock, ?int $ttl = null): bool
    {
        return $lock->refresh($ttl);
    }

    /**
     * Force release a lock without ownership verification.
     *
     * @param string $resource The resource identifier to force release.
     * @param string $config Configuration name. Defaults to 'default'.
     * @return bool True if the lock was released, false otherwise.
     */
    public static function forceRelease(string $resource, string $config = 'default'): bool
    {
        return static::engine($config)->forceRelease($resource);
    }

    /**
     * Execute a callback with an acquired lock.
     *
     * This method provides a convenient way to execute code within a lock,
     * automatically releasing the lock when the callback completes or throws.
     *
     * @template T
     * @param string $resource The resource identifier to lock.
     * @param \Closure $callback The callback to execute while holding the lock.
     * @param int|null $ttl Time-to-live in seconds for the lock.
     * @param int $timeout Maximum time in seconds to wait for the lock.
     * @param string $config Configuration name. Defaults to 'default'.
     * @return T|null Returns the callback result, or null if lock couldn't be acquired.
     * @phpstan-param \Closure(): T $callback
     * @phpstan-return T|null
     */
    public static function synchronized(
        string $resource,
        Closure $callback,
        ?int $ttl = null,
        int $timeout = 10,
        string $config = 'default',
    ): mixed {
        $lock = static::acquireBlocking($resource, $ttl, $timeout, config: $config);
        if ($lock === null) {
            return null;
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
