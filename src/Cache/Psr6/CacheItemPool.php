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

use Cake\Cache\Cache;
use Cake\Cache\CacheEngineInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-6 CacheItemPool implementation.
 *
 * Wraps a CakePHP cache engine to provide PSR-6 compatibility.
 *
 * ### Usage
 *
 * ```php
 * // Using a configured cache pool
 * $pool = new CacheItemPool('default');
 *
 * // Get an item
 * $item = $pool->getItem('my_key');
 * if (!$item->isHit()) {
 *     $item->set('computed value');
 *     $pool->save($item);
 * }
 * $value = $item->get();
 * ```
 */
class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * The underlying cache engine.
     *
     * @var \Cake\Cache\CacheEngineInterface&\Psr\SimpleCache\CacheInterface
     */
    private CacheEngineInterface&CacheInterface $engine;

    /**
     * Deferred cache items waiting to be committed.
     *
     * @var array<string, \Cake\Cache\Psr6\CacheItem>
     */
    private array $deferred = [];

    /**
     * Constructor.
     *
     * @param (\Cake\Cache\CacheEngineInterface&\Psr\SimpleCache\CacheInterface)|string $cache Cache config name or engine instance.
     */
    public function __construct((CacheEngineInterface&CacheInterface)|string $cache = 'default')
    {
        if (is_string($cache)) {
            $this->engine = Cache::pool($cache);
        } else {
            $this->engine = $cache;
        }
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);

        // Check deferred items first
        if (isset($this->deferred[$key])) {
            return clone $this->deferred[$key];
        }

        $value = $this->engine->get($key);

        if ($value === null) {
            return new CacheItem($key, false);
        }

        $item = new CacheItem($key, true);
        $item->set($value);

        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem(string $key): bool
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            return true;
        }

        return $this->engine->get($key) !== null;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->deferred = [];

        return $this->engine->clear();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);

        unset($this->deferred[$key]);

        return $this->engine->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }

        $ttl = $item->getTtl();

        return $this->engine->set($item->getKey(), $item->get(), $ttl);
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        $success = true;

        foreach ($this->deferred as $key => $item) {
            if (!$this->save($item)) {
                $success = false;
            }
            unset($this->deferred[$key]);
        }

        return $success;
    }

    /**
     * Validate a cache key.
     *
     * @param string $key The key to validate.
     * @return void
     * @throws \Cake\Cache\Psr6\InvalidArgumentException If the key is invalid.
     */
    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key cannot be empty.');
        }

        // PSR-6 reserved characters
        if (preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new InvalidArgumentException(
                'Cache key contains reserved characters: {}()/\\@:',
            );
        }
    }

    /**
     * Commits any deferred items on destruction.
     */
    public function __destruct()
    {
        $this->commit();
    }
}
