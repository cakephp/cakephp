<?php
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
    public function write(string $key, $value): bool
    {
    }

    public function read(string $key)
    {
    }

    public function increment(string $key, int $offset = 1)
    {
    }

    public function decrement(string $key, int $offset = 1)
    {
    }

    public function delete(string $key): bool
    {
    }

    public function clear(bool $check): bool
    {
    }

    public function clearGroup(string $group): bool
    {
    }
}
