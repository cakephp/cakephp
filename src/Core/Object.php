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
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Log\LogTrait;
use Cake\Utility\Hash;

/**
 * Object class provides a few generic methods used in several subclasses.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 */
class Object {

	use LogTrait;

/**
 * Object-to-string conversion.
 * Each class can override this method as necessary.
 *
 * @return string The name of this class
 */
	public function toString() {
		return get_class($this);
	}

/**
 * Stop execution of the current script. Wraps exit() making
 * testing easier.
 *
 * @param int|string $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}

}
