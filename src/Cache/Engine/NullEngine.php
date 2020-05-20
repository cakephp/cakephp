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
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;

/**
 * Null cache engine, all operations appear to work, but do nothing.
 *
 * This is used internally for when Cache::disable() has been called.
 */
class NullEngine extends CacheEngine
{
    /**
     * @inheritDoc
     */
    public function init(array $config = []): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $offset = 1)
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $offset = 1)
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clearGroup(string $group): bool
    {
        return true;
    }
}
