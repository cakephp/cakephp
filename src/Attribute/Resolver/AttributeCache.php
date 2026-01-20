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
namespace Cake\Attribute\Resolver;

use Cake\Attribute\Resolver\ValueObject\AttributeInfo;
use Cake\Cache\Cache;
use Cake\Log\Log;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * Cache manager for attribute resolver data.
 *
 * Provides caching with optional file validation to ensure cached
 * attribute metadata is still fresh.
 */
class AttributeCache
{
    /**
     * Cache interface instance
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * Constructor.
     *
     * @param string $cacheConfig Name of the cache configuration to use
     * @param bool $validateFiles Whether to validate source file modification times
     */
    public function __construct(
        string $cacheConfig,
        protected bool $validateFiles = false,
    ) {
        $this->cache = Cache::pool($cacheConfig);
    }

    /**
     * Generate cache key for a resolver configuration.
     *
     * @param string $name Resolver configuration name
     * @return string Cache key
     */
    protected function cacheKey(string $name): string
    {
        return 'attribute_resolver_' . $name;
    }

    /**
     * Read attribute data from cache.
     *
     * @param string $name Resolver configuration name
     * @return array<\Cake\Attribute\Resolver\ValueObject\AttributeInfo>|null Array of AttributeInfo objects or null if not found/invalid
     */
    public function read(string $name): ?array
    {
        try {
            $data = $this->cache->get($this->cacheKey($name));

            if ($data === null || !is_array($data)) {
                return null;
            }

            // Validate first item to ensure proper unserialization occurred.
            // If the cache contains invalid data, reject it gracefully.
            if ($data !== [] && !reset($data) instanceof AttributeInfo) {
                Log::warning(sprintf(
                    'Invalid cached attribute data structure for key: %s',
                    $name,
                ));

                return null;
            }

            // Validate file modification times if enabled
            if ($this->validateFiles && !$this->isValid($data)) {
                $this->delete($name);

                return null;
            }

            return $data;
        } catch (Throwable $e) {
            Log::warning(sprintf(
                'Failed to read cached attributes for key %s: %s',
                $name,
                $e->getMessage(),
            ));

            return null;
        }
    }

    /**
     * Write attribute data to cache.
     *
     * Stores AttributeInfo objects directly. The cache engine handles
     * serialization using the objects' __serialize() methods.
     *
     * @param string $name Resolver configuration name
     * @param array<\Cake\Attribute\Resolver\ValueObject\AttributeInfo> $data AttributeInfo objects to cache
     * @return bool Success
     */
    public function write(string $name, array $data): bool
    {
        try {
            return $this->cache->set($this->cacheKey($name), $data);
        } catch (Throwable $e) {
            Log::warning(sprintf(
                'Failed to write cached attributes for key %s: %s',
                $name,
                $e->getMessage(),
            ));

            return false;
        }
    }

    /**
     * Delete cached attribute data.
     *
     * @param string $name Resolver configuration name
     * @return bool Success
     */
    public function delete(string $name): bool
    {
        try {
            return $this->cache->delete($this->cacheKey($name));
        } catch (Throwable $e) {
            Log::warning(sprintf(
                'Failed to delete cached attributes for key %s: %s',
                $name,
                $e->getMessage(),
            ));

            return false;
        }
    }

    /**
     * Validate that cached data is still fresh by checking source file modification times.
     *
     * Compares each source file's current modification time against the stored fileTime
     * from when the cache was created. If any source file is newer, the cache is stale.
     *
     * @param array<\Cake\Attribute\Resolver\ValueObject\AttributeInfo> $data Cached AttributeInfo objects
     * @return bool True if valid, false if any source file has changed
     */
    protected function isValid(array $data): bool
    {
        // Clear stat cache once for accurate modification times
        clearstatcache();

        // Track checked files to avoid rechecking same file multiple times
        $checked = [];

        foreach ($data as $item) {
            $filePath = $item->filePath;

            // Skip if already checked this file
            if (isset($checked[$filePath])) {
                continue;
            }
            $checked[$filePath] = true;

            // If file was modified after cache was created, cache is stale
            if (is_file($filePath) && filemtime($filePath) > $item->fileTime) {
                return false;
            }
        }

        return true;
    }
}
