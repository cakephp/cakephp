<?php
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

    public function write($key, $value)
    {
        if ($key === 'fail') {
            return false;
        }
    }

    public function read($key)
    {
    }

    public function increment($key, $offset = 1)
    {
    }

    public function decrement($key, $offset = 1)
    {
    }

    public function delete($key)
    {
    }

    public function clear($check)
    {
    }

    public function clearGroup($group)
    {
    }
}
