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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Cache\Cache;
use Cake\Core\Exception\CakeException;
use Closure;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * Handles caching queries and loading results from the cache.
 *
 * Used by {@link \Cake\Datasource\QueryTrait} internally.
 *
 * @internal
 * @see \Cake\Datasource\QueryTrait::cache() for the public interface.
 */
class QueryCacher
{
    /**
     * The key or function to generate a key.
     *
     * @var \Closure|string
     */
    protected Closure|string $_key;

    /**
     * Config for cache engine.
     *
     * @var \Psr\SimpleCache\CacheInterface|string
     */
    protected CacheInterface|string $_config;

    /**
     * Constructor.
     *
     * @param \Closure|string $key The key or function to generate a key.
     * @param \Psr\SimpleCache\CacheInterface|string $config The cache config name or cache engine instance.
     * @throws \RuntimeException
     */
    public function __construct(Closure|string $key, CacheInterface|string $config)
    {
        $this->_key = $key;
        $this->_config = $config;
    }

    /**
     * Load the cached results from the cache or run the query.
     *
     * @param object $query The query the cache read is for.
     * @return mixed|null Either the cached results or null.
     */
    public function fetch(object $query): mixed
    {
        $key = $this->_resolveKey($query);
        $storage = $this->_resolveCacher();
        $result = $storage->get($key);
        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Store the result set into the cache.
     *
     * @param object $query The query the cache read is for.
     * @param \Traversable $results The result set to store.
     * @return bool True if the data was successfully cached, false on failure
     */
    public function store(object $query, Traversable $results): bool
    {
        $key = $this->_resolveKey($query);
        $storage = $this->_resolveCacher();

        return $storage->set($key, $results);
    }

    /**
     * Get/generate the cache key.
     *
     * @param object $query The query to generate a key for.
     * @return string
     * @throws \Cake\Core\Exception\CakeException
     */
    protected function _resolveKey(object $query): string
    {
        if (is_string($this->_key)) {
            return $this->_key;
        }
        $func = $this->_key;
        $key = $func($query);
        if (!is_string($key)) {
            $msg = sprintf('Cache key functions must return a string. Got %s.', var_export($key, true));
            throw new CakeException($msg);
        }

        return $key;
    }

    /**
     * Get the cache engine.
     *
     * @return \Psr\SimpleCache\CacheInterface
     */
    protected function _resolveCacher(): CacheInterface
    {
        if (is_string($this->_config)) {
            return Cache::pool($this->_config);
        }

        return $this->_config;
    }
}
