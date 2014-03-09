<?php
/**
 * TestAuthComponent
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller\Component;

use Cake\Controller\Component\AuthComponent;

/**
 * TestAuthComponent class
 *
 */
class TestAuthComponent extends AuthComponent {

/**
 * testStop property
 *
 * @var bool false
 */
	public $testStop = false;

/**
 * Helper method to add/set an authenticate object instance
 *
 * @param integer $index The index at which to add/set the object
 * @param Object $object The object to add/set
 * @return void
 */
	public function setAuthenticateObject($index, $object) {
		$this->_authenticateObjects[$index] = $object;
	}

/**
 * Helper method to add/set an authorize object instance
 *
 * @param integer $index The index at which to add/set the object
 * @param Object $object The object to add/set
 * @return void
 */
	public function setAuthorizeObject($index, $object) {
		$this->_authorizeObjects[$index] = $object;
	}

/**
 * stop method
 *
 * @return void
 */
	protected function _stop($status = 0) {
		$this->testStop = true;
	}

	public static function clearUser() {
		static::$_user = array();
	}

}
