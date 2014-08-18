<?php
/**
 * Cache Session save handler. Allows saving session information into Cache.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Session;

use Cake\Cache\Cache;
use SessionHandlerInterface;

/**
 * CacheSession provides method for saving sessions into a Cache engine. Used with Session
 *
 * @see \Cake\Model\Datasource\Session for configuration information.
 */
class CacheSession implements SessionHandlerInterface {

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
 * @throws \InvalidArgumentException if the 'config' jey is not provided
 */
	public function __construct(array $config = []) {
		if (empty($config['config'])) {
			throw new \InvalidArgumentException('The cache configuration name to use is required');
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
	public function open($savePath, $name) {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return bool Success
 */
	public function close() {
		return true;
	}

/**
 * Method used to read from a cache session.
 *
 * @param string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		return Cache::read($id, $this->_options['config']);
	}

/**
 * Helper function called on write for cache sessions.
 *
 * @param int $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return bool True for successful write, false otherwise.
 */
	public function write($id, $data) {
		return Cache::write($id, $data, $this->_options['config']);
	}

/**
 * Method called on the destruction of a cache session.
 *
 * @param int $id ID that uniquely identifies session in cache
 * @return bool True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return Cache::delete($id, $this->_options['config']);
	}

/**
 * Helper function called on gc for cache sessions.
 *
 * @param string $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
 * @return bool True on success, false on failure.
 */
	public function gc($maxlifetime) {
		return Cache::gc($this->_options['config'], time() - $maxlifetime);
	}

}
