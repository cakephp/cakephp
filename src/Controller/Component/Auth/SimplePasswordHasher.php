<?php
/**
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
 * @since         2.4.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Auth;

use Cake\Controller\Component\Auth\AbstractPasswordHasher;
use Cake\Utility\Security;

/**
 * Simple password hashing class.
 *
 */
class SimplePasswordHasher extends AbstractPasswordHasher {

/**
 * Default config for this object.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'hashType' => null
	];

/**
 * Generates password hash.
 *
 * @param string $password Plain text password to hash.
 * @return string Password hash
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#hashing-passwords
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
