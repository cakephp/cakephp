<?php
/**
 * Test Suite Test Plugin Cache Engine class.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestPluginCacheEngine
 */
namespace TestPlugin\Cache\Engine;

use Cake\Cache\CacheEngine;

class TestPluginCacheEngine extends CacheEngine
{

    public function write($key, $value)
    {
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
