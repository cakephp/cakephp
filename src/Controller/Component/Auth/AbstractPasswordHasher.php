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

use Cake\Core\InstanceConfigTrait;

/**
 * Abstract password hashing class
 *
 */
abstract class AbstractPasswordHasher {

	use InstanceConfigTrait;

/**
 * Default config
 *
 * These are merged with user-provided config when the object is used.
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * Constructor
 *
 * @param array $config Array of config.
 */
	public function __construct(array $config = array()) {
		$this->config($config);
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
