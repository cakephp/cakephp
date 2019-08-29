<?php
/**
 * Cache Session save handler. Allows saving session information into Cache.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Session;

use Cake\Cache\Cache;
use InvalidArgumentException;
use SessionHandlerInterface;

/**
 * CacheSession provides method for saving sessions into a Cache engine. Used with Session
 *
 * @see \Cake\Http\Session for configuration information.
 */
class CacheSession implements SessionHandlerInterface
{

    /**
     * Options for this session engine
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor.
     *
     * @param array $config The configuration to use for this engine
     * It requires the key 'config' which is the name of the Cache config to use for
     * storing the session
     *
     * @throws \InvalidArgumentException if the 'config' key is not provided
     */
    public function __construct(array $config = [])
    {
        if (empty($config['config'])) {
            throw new InvalidArgumentException('The cache configuration name to use is required');
        }
        $this->_options = $config;
    }

    /**
     * Method called on open of a database session.
     *
     * @param string $savePath The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool Success
     */
    public function open($savePath, $name)
    {
        return true;
    }

    /**
     * Method called on close of a database session.
     *
     * @return bool Success
     */
    public function close()
    {
        return true;
    }

    /**
     * Method used to read from a cache session.
     *
     * @param string|int $id ID that uniquely identifies session in cache.
     * @return string Session data or empty string if it does not exist.
     */
    public function read($id)
    {
        $value = Cache::read($id, $this->_options['config']);

        if (empty($value)) {
            return '';
        }

        return $value;
    }

    /**
     * Helper function called on write for cache sessions.
     *
     * @param string|int $id ID that uniquely identifies session in cache.
     * @param mixed $data The data to be saved.
     * @return bool True for successful write, false otherwise.
     */
    public function write($id, $data)
    {
        if (!$id) {
            return false;
        }

        return (bool)Cache::write($id, $data, $this->_options['config']);
    }

    /**
     * Method called on the destruction of a cache session.
     *
     * @param string|int $id ID that uniquely identifies session in cache.
     * @return bool Always true.
     */
    public function destroy($id)
    {
        Cache::delete($id, $this->_options['config']);

        return true;
    }

    /**
     * Helper function called on gc for cache sessions.
     *
     * @param int $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
     * @return bool Always true.
     */
    public function gc($maxlifetime)
    {
        Cache::gc($this->_options['config'], time() - $maxlifetime);

        return true;
    }
}
