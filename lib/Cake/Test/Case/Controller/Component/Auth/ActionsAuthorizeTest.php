<?php
/**
 * ActionsAuthorizeTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Controller.Component.Auth
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ActionsAuthorize', 'Controller/Component/Auth');
App::uses('ComponentCollection', 'Controller');
App::uses('AclComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * ActionsAuthorizeTest
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class ActionsAuthorizeTest extends CakeTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
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
	public function testAuthorizeFailure() {
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
			->with($user, 'controllers/Posts/index')
			->will($this->returnValue(false));

		$this->assertFalse($this->auth->authorize($user['User'], $request));
	}

/**
 * test isAuthorized working.
 *
 * @return void
 */
	public function testAuthorizeSuccess() {
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
			->with($user, 'controllers/Posts/index')
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user['User'], $request));
	}

/**
 * testAuthorizeSettings
 *
 * @return void
 */
	public function testAuthorizeSettings() {
		$request = new CakeRequest('/posts/index', false);
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));

		$this->_mockAcl();

		$this->auth->settings['userModel'] = 'TestPlugin.TestPluginAuthUser';
		$user = array(
			'id' => 1,
			'user' => 'mariano'
		);

		$expected = array('TestPlugin.TestPluginAuthUser' => array('id' => 1, 'user' => 'mariano'));
		$this->Acl->expects($this->once())
			->method('check')
			->with($expected, 'controllers/Posts/index')
			->will($this->returnValue(true));

		$this->assertTrue($this->auth->authorize($user, $request));
	}

/**
 * test action()
 *
 * @return void
 */
	public function testActionMethod() {
		$request = new CakeRequest('/posts/index', false);
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));

		$result = $this->auth->action($request);
		$this->assertEquals('controllers/Posts/index', $result);
	}

/**
 * Make sure that action() doesn't create double slashes anywhere.
 *
 * @return void
 */
	public function testActionNoDoubleSlash() {
		$this->auth->settings['actionPath'] = '/controllers/';
		$request = new CakeRequest('/posts/index', false);
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));
		$result = $this->auth->action($request);
		$this->assertEquals('controllers/Posts/index', $result);
	}

/**
 * test action() and plugins
 *
 * @return void
 */
	public function testActionWithPlugin() {
		$request = new CakeRequest('/debug_kit/posts/index', false);
		$request->addParams(array(
			'plugin' => 'debug_kit',
			'controller' => 'posts',
			'action' => 'index'
		));

		$result = $this->auth->action($request);
		$this->assertEquals('controllers/DebugKit/Posts/index', $result);
	}
}
