<?php

App::import('Component', 'auth/crud_authorize');
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

		$this->auth = new CrudAuthorize($this->controller);
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
 * test authorize() without a mapped action, ensure an error is generated.
 *
 * @expectedException Exception
 * @return void
 */
	function testAuthorizeNoMappedAction() {
		$request = new CakeRequest('/posts/foobar', false);
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'foobar'
		));
		$user = array('User' => array('user' => 'mark'));

		$this->auth->authorize($user, $request);
	}

/**
 * test check() passing
 *
 * @return void
 */
	function testAuthorizeCheckSuccess() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index'
		));
		$user = array('User' => array('user' => 'mark'));

		$this->_mockAcl();
		$this->Acl->expects($this->once())
			->method('check')
			->with($user, 'Posts', 'read')
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user, $request));
	}

/**
 * test check() failing
 *
 * @return void
 */
	function testAuthorizeCheckFailure() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index'
		));
		$user = array('User' => array('user' => 'mark'));

		$this->_mockAcl();
		$this->Acl->expects($this->once())
			->method('check')
			->with($user, 'Posts', 'read')
			->will($this->returnValue(false));

		$this->assertFalse($this->auth->authorize($user, $request));
	}


/**
 * test getting actionMap
 *
 * @return void
 */
	function testMapActionsGet() {
		$result = $this->auth->mapActions();
		$expected = array(
			'index' => 'read',
			'add' => 'create',
			'edit' => 'update',
			'view' => 'read',
			'delete' => 'delete',
			'remove' => 'delete'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test adding into mapActions
 *
 * @return void
 */
	function testMapActionsSet() {
		$map = array(
			'create' => array('generate'),
			'read' => array('listing', 'show'),
			'update' => array('update'),
			'random' => 'custom'
		);
		$result = $this->auth->mapActions($map);
		$this->assertNull($result);

		$result = $this->auth->mapActions();
		$expected = array(
			'index' => 'read',
			'add' => 'create',
			'edit' => 'update',
			'view' => 'read',
			'delete' => 'delete',
			'remove' => 'delete',
			'generate' => 'create',
			'listing' => 'read',
			'show' => 'read',
			'update' => 'update',
			'random' => 'custom'
		);
		$this->assertEquals($expected, $result);
	}

}
