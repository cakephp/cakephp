<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.libs.controller.components.auth
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('ControllerAuthorize', 'Controller/Component/Auth');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

class ControllerAuthorizeTest extends CakeTestCase {

/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->controller = $this->getMock('Controller', array('isAuthorized'), array(), '', false);
		$this->components = $this->getMock('ComponentCollection');
		$this->components->expects($this->any())
			->method('getController')
			->will($this->returnValue($this->controller));
		
		$this->auth = new ControllerAuthorize($this->components);
	}

/**
 * 
 * @expectedException CakeException
 */
	function testControllerTypeError() {
		$this->auth->controller(new StdClass());
	}

/**
 * @expectedException CakeException
 */
	function testControllerErrorOnMissingMethod() {
		$this->auth->controller(new Controller());
	}

/**
 * test failure
 *
 * @return void
 */
	function testAuthorizeFailure() {
		$user = array();
		$request = new CakeRequest('/posts/index', false);
		$this->assertFalse($this->auth->authorize($user, $request));
	}

/**
 * test isAuthorized working.
 *
 * @return void
 */
	function testAuthorizeSuccess() {
		$user = array('User' => array('username' => 'mark'));
		$request = new CakeRequest('/posts/index', false);
		
		$this->controller->expects($this->once())
			->method('isAuthorized')
			->with($user)
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user, $request));
	}
}
