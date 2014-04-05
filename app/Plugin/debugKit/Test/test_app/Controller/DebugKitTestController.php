<?php
/**
 * DebugKit TestController of test_app
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
 **/

/**
 * Class DebugKitTestController
 *
 * @since         DebugKit 0.1
 */
class DebugKitTestController extends Controller {

/**
 * Mame of the Controller
 *
 * @var string
 */
	public $name = 'DebugKitTest';

/**
 * Uses no Models
 *
 * @var array
 */
	public $uses = array();

/**
 * Uses only DebugKit Toolbar Component
 *
 * @var array
 */
	public $components = array('DebugKit.Toolbar');

/**
 * Return Request Action Value
 *
 * @return string
 */
	public function request_action_return() {
		$this->autoRender = false;
		return 'I am some value from requestAction.';
	}

/**
 * Render Request Action
 */
	public function request_action_render() {
		$this->set('test', 'I have been rendered.');
	}
}
