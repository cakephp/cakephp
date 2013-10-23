<?php
/**
 * PHP 5
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
 * @since         CakePHP(tm) v 2.4.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 */
	public function hash($password) {
		return Security::hash($password, $this->_config['hashType'], true);
	}

/**
 * Check hash. Generate hash for user provided password and check against existing hash.
 *
 * @param string $password Plain text password to hash.
 * @param string Existing hashed password.
 * @return boolean True if hashes match else false.
 */
	public function check($password, $hashedPassword) {
		return $hashedPassword === $this->hash($password);
	}

}
