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

use Psr\SimpleCache\CacheInterface;

/**
 * Decorates a schema collection and adds caching
 */
class CachedCollection implements CollectionInterface
{
    /**
     * Cacher instance.
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected CacheInterface $cacher;

    /**
     * The decorated schema collection
     *
     * @var \Cake\Database\Schema\CollectionInterface
     */
    protected CollectionInterface $collection;

    /**
     * The cache key prefix
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Constructor.
     *
     * @param \Cake\Database\Schema\CollectionInterface $collection The collection to wrap.
     * @param string $prefix The cache key prefix to use. Typically the connection name.
     * @param \Psr\SimpleCache\CacheInterface $cacher Cacher instance.
     */
    public function __construct(CollectionInterface $collection, string $prefix, CacheInterface $cacher)
    {
        $this->collection = $collection;
        $this->prefix = $prefix;
        $this->cacher = $cacher;
    }

    /**
     * @inheritDoc
     */
    public function listTablesWithoutViews(): array
    {
        return $this->collection->listTablesWithoutViews();
    }

    /**
     * @inheritDoc
     */
    public function listTables(): array
    {
        return $this->collection->listTables();
    }

    /**
     * @inheritDoc
     */
    public function describe(string $name, array $options = []): TableSchemaInterface
    {
        $options += ['forceRefresh' => false];
        $cacheKey = $this->cacheKey($name);

        if (!$options['forceRefresh']) {
            $cached = $this->cacher->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $table = $this->collection->describe($name, $options);
        $this->cacher->set($cacheKey, $table);

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
     * Set a cacher.
     *
     * @param \Psr\SimpleCache\CacheInterface $cacher Cacher object
     * @return $this
     */
    public function setCacher(CacheInterface $cacher)
    {
        $this->cacher = $cacher;

        return $this;
    }

    /**
     * Get a cacher.
     *
     * @return \Psr\SimpleCache\CacheInterface $cacher Cacher object
     */
    public function getCacher(): CacheInterface
    {
        return $this->cacher;
    }
}
