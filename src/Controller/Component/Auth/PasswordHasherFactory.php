<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Auth;

use Cake\Core\App;

/**
 * Builds password hashing objects
 *
 */
class PasswordHasherFactory {

/**
 * Return password hasher object out of a hasher name or a configuration array
 *
 * @param string|array $passwordHasher name of the password hasher or an array with
 * at least the key `className` set to the name of the class to use
 * @return AbstractPasswordHasher Password hasher instance
 * @throws RuntimeException If password hasher class not found or
 *   it does not extend AbstractPasswordHasher
 */
	public static function build($passwordHasher) {
		$config = [];
		if (is_string($passwordHasher)) {
			$class = $passwordHasher;
		} else {
			$class = $passwordHasher['className'];
			$config = $passwordHasher;
			unset($config['className']);
		}

		list($plugin, $class) = pluginSplit($class, true);
		$className = App::className($class, 'Controller/Component/Auth', 'PasswordHasher');
		if (!class_exists($className)) {
			throw new Error\Exception(sprintf('Password hasher class "%s" was not found.', $class));
		}

		$hasher = new $className($config);
		if (!($hasher instanceof AbstractPasswordHasher)) {
			throw new \RuntimeException('Password hasher must extend AbstractPasswordHasher class.');
		}

		return $hasher;
	}

}
