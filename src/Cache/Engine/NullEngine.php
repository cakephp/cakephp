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
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;

/**
 * Null cache engine, all operations return false.
 *
 * This is used internally for when Cache::disable() has been called.
 */
class NullEngine extends CacheEngine
{

    /**
     * {@inheritDoc}
     */
    public function init(array $config = [])
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function gc($expires = null)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $value)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function writeMany($data)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function readMany($keys)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $offset = 1)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $offset = 1)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMany($keys)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function clear($check)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup($group)
    {
        return false;
    }
}
