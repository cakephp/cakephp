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

/**
 * Represents an acquired lock.
 */
class AcquiredLock
{
    /**
     * Whether the lock has already been released.
     *
     * @var bool
     */
    protected bool $released = false;

    /**
     * Constructor.
     *
     * @param string $resource The locked resource identifier.
     * @param string $token Unique token identifying the lock owner.
     * @param int $ttl Time-to-live in seconds.
     * @param float $acquiredAt Timestamp when the lock was acquired.
     * @param \Cake\Lock\LockInterface|null $engine The engine that acquired the lock.
     */
    public function __construct(
        protected readonly string $resource,
        protected readonly string $token,
        protected readonly int $ttl,
        protected readonly float $acquiredAt,
        protected readonly ?LockInterface $engine = null,
    ) {
    }

    /**
     * Get the locked resource identifier.
     *
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * Get the unique token identifying the lock owner.
     *
     * This token is used to verify ownership when releasing
     * or refreshing the lock.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get the time-to-live in seconds.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Get the timestamp when the lock was acquired.
     *
     * @return float
     */
    public function getAcquiredAt(): float
    {
        return $this->acquiredAt;
    }

    /**
     * Release the lock with the engine that acquired it.
     *
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(): bool
    {
        if ($this->released || $this->engine === null) {
            return false;
        }

        $released = $this->engine->release($this);
        if ($released) {
            $this->released = true;
        }

        return $released;
    }

    /**
     * Refresh the lock with the engine that acquired it.
     *
     * @param int|null $ttl New TTL in seconds. If null, uses the original TTL.
     * @return bool True if the lock was refreshed, false otherwise.
     */
    public function refresh(?int $ttl = null): bool
    {
        if ($this->released || $this->engine === null) {
            return false;
        }

        return $this->engine->refresh($this, $ttl);
    }

    /**
     * Check whether the lock has already been released.
     *
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * Check if the lock has expired based on its TTL.
     *
     * Note: This is a local check based on the original TTL.
     * The actual lock state in the backend may differ due to
     * clock skew or manual intervention.
     *
     * @return bool True if the lock has likely expired.
     */
    public function isExpired(): bool
    {
        return microtime(true) - $this->acquiredAt >= $this->ttl;
    }

    /**
     * Get remaining time until expiration in seconds.
     *
     * @return float Remaining seconds, may be negative if expired.
     */
    public function getRemainingTtl(): float
    {
        return $this->ttl - (microtime(true) - $this->acquiredAt);
    }

    /**
     * Attempt to release the lock when it falls out of scope.
     */
    public function __destruct()
    {
        $this->release();
    }
}
