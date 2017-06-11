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

App::uses('AbstractPasswordHasher', 'Controller/Component/Auth');
App::uses('Security', 'Utility');

/**
 * Simple password hashing class.
 *
 * @package       Cake.Controller.Component.Auth
 */
class SimplePasswordHasher extends AbstractPasswordHasher {

/**
 * Config for this object.
 *
 * @var array
 */
	protected $_config = array('hashType' => null);

/**
 * Generates password hash.
 *
 * @param string $password Plain text password to hash.
 * @return string Password hash
 * @link https://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#hashing-passwords
 */
	public function hash($password) {
		return Security::hash($password, $this->_config['hashType'], true);
	}

/**
 * Check hash. Generate hash for user provided password and check against existing hash.
 *
 * @param string $password Plain text password to hash.
 * @param string $hashedPassword Existing hashed password.
 * @return bool True if hashes match else false.
 */
	public function check($password, $hashedPassword) {
		return $hashedPassword === $this->hash($password);
	}

}
