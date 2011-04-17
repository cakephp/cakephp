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

App::uses('ActionsAuthorize', 'Controller/Component/Auth');
App::uses('ComponentCollection', 'Controller');
App::uses('AclComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

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
		$this->Collection = $this->getMock('ComponentCollection');

		$this->auth = new ActionsAuthorize($this->Collection);
		$this->auth->settings['actionPath'] = '/controllers';
	}

/**
 * setup the mock acl.
 *
 * @return void
 */
	protected function _mockAcl() {
		$this->Collection->expects($this->any())
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
