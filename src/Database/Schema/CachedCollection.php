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
namespace Cake\Database\Schema;

use Cake\Cache\Cache;

/**
 * Decorates a schema collection and adds caching
 */
class CachedCollection implements CollectionInterface
{
    /**
     * The name of the cache config key to use for caching table metadata,
     * of false if disabled.
     *
     * @var string|bool
     */
    protected $_cache = false;

    /**
     * The decorated schema collection
     *
     * @var \Cake\Database\Schema\CollectionInterface
     */
    protected $collection;

    /**
     * The cache key prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * Constructor.
     *
     * @param \Cake\Database\Schema\CollectionInterface $collection The collection to wrap.
     * @param string $prefix The cache key prefix to use. Typically the connection name.
     * @param string|bool $cacheKey The cache key or boolean false to disable caching.
     */
    public function __construct(CollectionInterface $collection, string $prefix = '', $cacheKey = true)
    {
        $this->collection = $collection;
        $this->prefix = $prefix;
        $this->setCacheMetadata($cacheKey);
    }

    /**
     * {@inheritDoc}
     */
    public function listTables(): array
    {
        return $this->collection->listTables();
    }

    /**
     * {@inheritDoc}
     */
    public function describe(string $name, array $options = []): TableSchemaInterface
    {
        $options += ['forceRefresh' => false];
        $cacheConfig = $this->getCacheMetadata();
        $cacheKey = $this->cacheKey($name);

        if (!empty($cacheConfig) && !$options['forceRefresh']) {
            $cached = Cache::read($cacheKey, $cacheConfig);
            if ($cached) {
                return $cached;
            }
        }

        $table = $this->collection->describe($name, $options);

        if (!empty($cacheConfig)) {
            Cache::write($cacheKey, $table, $cacheConfig);
        }

        return $table;
    }

    /**
     * Get the cache key for a given name.
     *
     * @param string $name The name to get a cache key for.
     * @return string The cache key.
     */
    public function cacheKey(string $name): string
    {
        return $this->prefix . '_' . $name;
    }

    /**
     * Sets the cache config name to use for caching table metadata, or
     * disables it if false is passed.
     *
     * @param string|bool $enable Whether or not to enable caching
     * @return $this
     */
    public function setCacheMetadata($enable)
    {
        if ($enable === true) {
            $enable = '_cake_model_';
        }

        $this->_cache = $enable;

        return $this;
    }

    /**
     * Gets the cache config name to use for caching table metadata, false means disabled.
     *
     * @return string|bool
     */
    public function getCacheMetadata()
    {
        return $this->_cache;
    }
}
