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
namespace Cake\Cache\Psr6;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

/**
 * PSR-6 CacheItem implementation.
 *
 * Wraps cache data with metadata for PSR-6 compatibility.
 */
class CacheItem implements CacheItemInterface
{
    /**
     * Whether the item was found in the cache.
     */
    private bool $isHit;

    /**
     * The cached value.
     */
    private mixed $value = null;

    /**
     * Expiration time for this item.
     */
    private ?DateTimeInterface $expiration = null;

    /**
     * Constructor.
     *
     * @param string $key The cache key.
     * @param bool $isHit Whether the item was found in cache.
     */
    public function __construct(
        private string $key,
        bool $isHit = false,
    ) {
        $this->isHit = $isHit;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @inheritDoc
     */
    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiration = null;
        } elseif ($time instanceof DateInterval) {
            $this->expiration = (new DateTimeImmutable())->add($time);
        } else {
            $this->expiration = (new DateTimeImmutable())->modify("+{$time} seconds");
        }

        return $this;
    }

    /**
     * Get the expiration time.
     *
     * @return \DateTimeInterface|null The expiration time or null for no expiration.
     */
    public function getExpiration(): ?DateTimeInterface
    {
        return $this->expiration;
    }

    /**
     * Get the TTL in seconds.
     *
     * @return int|null The TTL in seconds, or null for default/no expiration.
     */
    public function getTtl(): ?int
    {
        if ($this->expiration === null) {
            return null;
        }

        $now = new DateTimeImmutable();
        $diff = $this->expiration->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }
}
