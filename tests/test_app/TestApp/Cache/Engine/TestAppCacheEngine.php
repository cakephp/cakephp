<?php
/**
 * Test Suite Test App Cache Engine class.
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestAppCacheEngine
 *
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
