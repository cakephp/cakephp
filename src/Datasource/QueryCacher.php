<?php
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
use Cake\Cache\CacheEngine;
use RuntimeException;
use Traversable;

/**
 * Handles caching queries and loading results from the cache.
 *
 * Used by Cake\Datasource\QueryTrait internally.
 *
 * @see \Cake\Datasource\QueryTrait::cache() for the public interface.
 */
class QueryCacher
{

    /**
     * The key or function to generate a key.
     *
     * @var string|callable
     */
    protected $_key;

    /**
     * Config for cache engine.
     *
     * @var string|\Cake\Cache\CacheEngine
     */
    protected $_config;

    /**
     * Constructor.
     *
     * @param string|\Closure $key The key or function to generate a key.
     * @param string|\Cake\Cache\CacheEngine $config The cache config name or cache engine instance.
     * @throws \RuntimeException
     */
    public function __construct($key, $config)
    {
        if (!is_string($key) && !is_callable($key)) {
            throw new RuntimeException('Cache keys must be strings or callables.');
        }
        $this->_key = $key;

        if (!is_string($config) && !($config instanceof CacheEngine)) {
            throw new RuntimeException('Cache configs must be strings or CacheEngine instances.');
        }
        $this->_config = $config;
    }

    /**
     * Load the cached results from the cache or run the query.
     *
     * @param object $query The query the cache read is for.
     * @return \Cake\Datasource\ResultSetInterface|null Either the cached results or null.
     */
    public function fetch($query)
    {
        $key = $this->_resolveKey($query);
        $storage = $this->_resolveCacher();
        $result = $storage->read($key);
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
    public function store($query, Traversable $results)
    {
        $key = $this->_resolveKey($query);
        $storage = $this->_resolveCacher();

        return $storage->write($key, $results);
    }

    /**
     * Get/generate the cache key.
     *
     * @param object $query The query to generate a key for.
     * @return string
     * @throws \RuntimeException
     */
    protected function _resolveKey($query)
    {
        if (is_string($this->_key)) {
            return $this->_key;
        }
        $func = $this->_key;
        $key = $func($query);
        if (!is_string($key)) {
            $msg = sprintf('Cache key functions must return a string. Got %s.', var_export($key, true));
            throw new RuntimeException($msg);
        }

        return $key;
    }

    /**
     * Get the cache engine.
     *
     * @return \Cake\Cache\CacheEngine
     */
    protected function _resolveCacher()
    {
        if (is_string($this->_config)) {
            return Cache::engine($this->_config);
        }

        return $this->_config;
    }
}
