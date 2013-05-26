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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('AbstractPasswordHasher', 'Controller/Component/Auth');
App::uses('Security', 'Utility');

/**
 * Blowfish password hashing class.
 *
 * @package       Cake.Controller.Component.Auth
 */
class BlowfishPasswordHasher extends AbstractPasswordHasher {

/**
 * Generates password hash.
 *
 * @param string $password Plain text password to hash.
 * @return string Password hash
 */
	public function hash($password) {
		return Security::hash($password, 'blowfish', false);
	}

/**
 * Check hash. Generate hash for user provided password and check against existing hash.
 *
 * @param string $password Plain text password to hash.
 * @param string Existing hashed password.
 * @return boolean True if hashes match else false.
 */
	public function check($password, $hashedPassword) {
		return $hashedPassword === Security::hash($password, 'blowfish', $hashedPassword);
	}

}
