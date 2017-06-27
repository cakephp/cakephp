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
 * @since         CakePHP(tm) v 2.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Abstract password hashing class
 *
 * @package       Cake.Controller.Component.Auth
 */
abstract class AbstractPasswordHasher {

/**
 * Configurations for this object. Settings passed from authenticator class to
 * the constructor are merged with this property.
 *
 * @var array
 */
	protected $_config = array();

/**
 * Constructor
 *
 * @param array $config Array of config.
 */
	public function __construct($config = array()) {
		$this->config($config);
	}

/**
 * Get/Set the config
 *
 * @param array $config Sets config, if null returns existing config
 * @return array Returns configs
 */
	public function config($config = null) {
		if (is_array($config)) {
			$this->_config = array_merge($this->_config, $config);
		}
		return $this->_config;
	}

/**
 * Generates password hash.
 *
 * @param string|array $password Plain text password to hash or array of data
 *   required to generate password hash.
 * @return string Password hash
 */
	abstract public function hash($password);

/**
 * Check hash. Generate hash from user provided password string or data array
 * and check against existing hash.
 *
 * @param string|array $password Plain text password to hash or data array.
 * @param string $hashedPassword Existing hashed password.
 * @return bool True if hashes match else false.
 */
	abstract public function check($password, $hashedPassword);

}
