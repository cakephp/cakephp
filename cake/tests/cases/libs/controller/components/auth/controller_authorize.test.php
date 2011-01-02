<?php

App::import('Component', 'auth/controller_authorize');
App::import('Core', 'CakeRequest');
App::import('Core', 'Controller');

class ControllerAuthorizeTest extends CakeTestCase {

/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->controller = $this->getMock('Controller', array('isAuthorized'), array(), '', false);
		$this->auth = new ControllerAuthorize($this->controller);
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
