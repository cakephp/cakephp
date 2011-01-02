<?php

App::import('Component', 'auth/actions_authorize');
App::import('Controller', 'ComponentCollection');
App::import('Component', 'Acl');
App::import('Core', 'CakeRequest');
App::import('Core', 'Controller');

class ActionsAuthorizeTest extends CakeTestCase {

/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->controller = $this->getMock('Controller', array(), array(), '', false);
		$this->Acl = $this->getMock('AclComponent', array(), array(), '', false);
		$this->controller->Components = $this->getMock('ComponentCollection');

		$this->auth = new ActionsAuthorize($this->controller);
		$this->auth->actionPath = '/controllers';
	}

/**
 * setup the mock acl.
 *
 * @return void
 */
	protected function _mockAcl() {
		$this->controller->Components->expects($this->any())
			->method('load')
			->with('Acl')
			->will($this->returnValue($this->Acl));
	}

/**
 * test failure
 *
 * @return void
 */
	function testAuthorizeFailure() {
		$user = array(
			'User' => array(
				'id' => 1,
				'user' => 'mariano'
			)
		);
		$request = new CakeRequest('/posts/index', false);
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));

		$this->_mockAcl();

		$this->Acl->expects($this->once())
			->method('check')
			->with($user, '/controllers/Posts/index')
			->will($this->returnValue(false));
	
		$this->assertFalse($this->auth->authorize($user, $request));
	}

/**
 * test isAuthorized working.
 *
 * @return void
 */
	function testAuthorizeSuccess() {
		$user = array(
			'User' => array(
				'id' => 1,
				'user' => 'mariano'
			)
		);
		$request = new CakeRequest('/posts/index', false);
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));

		$this->_mockAcl();

		$this->Acl->expects($this->once())
			->method('check')
			->with($user, '/controllers/Posts/index')
			->will($this->returnValue(true));
	
		$this->assertTrue($this->auth->authorize($user, $request));
	}

/**
 * test action()
 *
 * @return void
 */
	function testActionMethod() {
		$request = new CakeRequest('/posts/index', false);
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));

		$result = $this->auth->action($request);

		$this->assertEquals('/controllers/Posts/index', $result);
	}

/**
 * test action() and plugins
 *
 * @return void
 */
	function testActionWithPlugin() {
		$request = new CakeRequest('/debug_kit/posts/index', false);
		$request->addParams(array(
			'plugin' => 'debug_kit',
			'controller' => 'posts',
			'action' => 'index'
		));

		$result = $this->auth->action($request);
		$this->assertEquals('/controllers/DebugKit/Posts/index', $result);
	}
}
