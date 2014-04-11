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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component\Auth;

use Cake\Controller\Component\Auth\ActionsAuthorize;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;

/**
 * Class ActionsAuthorizeTest
 *
 */
class ActionsAuthorizeTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->controller = $this->getMock('Cake\Controller\Controller', array(), array(), '', false);
		$this->Acl = $this->getMock('Cake\Controller\Component\AclComponent', array(), array(), '', false);
		$this->Collection = $this->getMock('Cake\Controller\ComponentRegistry');

		$this->auth = new ActionsAuthorize($this->Collection);
		$this->auth->config('actionPath', '/controllers');
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
			'Users' => array(
				'id' => 1,
				'user' => 'mariano'
			)
		);
		$request = new Request('/posts/index');
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

		$this->assertFalse($this->auth->authorize($user['Users'], $request));
	}

/**
 * test isAuthorized working.
 *
 * @return void
 */
	public function testAuthorizeSuccess() {
		$user = array(
			'Users' => array(
				'id' => 1,
				'user' => 'mariano'
			)
		);
		$request = new Request('/posts/index');
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

		$this->assertTrue($this->auth->authorize($user['Users'], $request));
	}

/**
 * testAuthorizeSettings
 *
 * @return void
 */
	public function testAuthorizeSettings() {
		$request = new Request('/posts/index');
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		));

		$this->_mockAcl();

		$this->auth->config('userModel', 'TestPlugin.AuthUser');
		$user = array(
			'id' => 1,
			'username' => 'mariano'
		);

		$expected = array('TestPlugin.AuthUser' => array('id' => 1, 'username' => 'mariano'));
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
		$request = new Request('/posts/index');
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
		$this->auth->config('actionPath', '/controllers/');
		$request = new Request('/posts/index', false);
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
		$request = new Request('/debug_kit/posts/index');
		$request->addParams(array(
			'plugin' => 'debug_kit',
			'controller' => 'posts',
			'action' => 'index'
		));

		$result = $this->auth->action($request);
		$this->assertEquals('controllers/DebugKit/Posts/index', $result);
	}
}
