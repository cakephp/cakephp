<?php
declare(strict_types=1);

/**
 * Test Suite Test Plugin Cache Engine class.
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
 * TestPluginCacheEngine
 */
namespace TestPlugin\Cache\Engine;

use Cake\Cache\CacheEngine;

class TestPluginCacheEngine extends CacheEngine
{
    public function set($key, $value, $ttl = null): bool
    {
    }

    public function get($key, $default = null)
    {
    }

    public function increment(string $key, int $offset = 1)
    {
    }

    public function decrement(string $key, int $offset = 1)
    {
    }

    public function delete($key): bool
    {
    }

    public function clear(): bool
    {
    }

    public function clearGroup(string $group): bool
    {
    }
}
