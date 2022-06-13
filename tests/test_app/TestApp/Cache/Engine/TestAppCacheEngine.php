<?php
declare(strict_types=1);

/**
 * Test Suite Test App Cache Engine class.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestAppCacheEngine
 */
namespace TestApp\Cache\Engine;

use Cake\Cache\CacheEngine;

class TestAppCacheEngine extends CacheEngine
{
    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        if ($key === 'fail') {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $offset = 1)
    {
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $offset = 1)
    {
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
    }

    /**
     * @inheritDoc
     */
    public function clearGroup(string $group): bool
    {
    }

    /**
     * Return duration method result.
     *
     * @param mixed $ttl
     * @return int
     */
    public function getDuration($ttl): int
    {
        return $this->duration($ttl);
    }
}
