<?php
/**
 * Common test objects used in DebugKit tests
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('FireCake', 'DebugKit.Lib');

/**
 * TestFireCake class allows for testing of FireCake
 *
 * @since         DebugKit 0.1
 */
class TestFireCake extends FireCake {

/**
 * Headers that were sent
 *
 * @var array
 */
	public $sentHeaders = array();

/**
 * Send header
 *
 * @param $name
 * @param $value
 */
	protected function _sendHeader($name, $value) {
		$_this = FireCake::getInstance();
		$_this->sentHeaders[$name] = $value;
	}

/**
 * Skip client detection as headers are not being sent.
 *
 * @return boolean Always true
 */
	public static function detectClientExtension() {
		return true;
	}

/**
 * Reset FireCake
 *
 * @return void
 */
	public static function reset() {
		$_this = FireCake::getInstance();
		$_this->sentHeaders = array();
		$_this->_messageIndex = 1;
	}
}
