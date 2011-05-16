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

App::uses('AuthComponent', 'Controller/Component');
App::uses('BasicAuthenticate', 'Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');


require_once  CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * Test case for BasicAuthentication
 *
 * @package cake.test.cases.controller.components.auth
 */
class BasicAuthenticateTest extends CakeTestCase {

	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->Collection = $this->getMock('ComponentCollection');
		$this->auth = new BasicAuthenticate($this->Collection, array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User',
			'realm' => 'localhost',
		));

		$password = Security::hash('password', null, true);
		ClassRegistry::init('User')->updateAll(array('password' => '"' . $password . '"'));
		$this->server = $_SERVER;
		$this->response = $this->getMock('CakeResponse');
	}

/**
 * teardown
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		$_SERVER = $this->server;
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	function testConstructor() {
		$object = new BasicAuthenticate($this->Collection, array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'password')
		));
		$this->assertEquals('AuthUser', $object->settings['userModel']);
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $object->settings['fields']);
		$this->assertEquals(env('SERVER_NAME'), $object->settings['realm']);
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateNoData() {
		$request = new CakeRequest('posts/index', false);

		$this->response->expects($this->once())
			->method('header')
			->with('WWW-Authenticate: Basic realm="localhost"');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateNoUsername() {
		$request = new CakeRequest('posts/index', false);
		$_SERVER['PHP_AUTH_PW'] = 'foobar';

		$this->response->expects($this->once())
			->method('header')
			->with('WWW-Authenticate: Basic realm="localhost"');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateNoPassword() {
		$request = new CakeRequest('posts/index', false);
		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = null;

		$this->response->expects($this->once())
			->method('header')
			->with('WWW-Authenticate: Basic realm="localhost"');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateInjection() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_USER'] = '> 1';
		$_SERVER['PHP_AUTH_PW'] = "' OR 1 = 1";

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test that challenge headers are sent when no credentials are found.
 *
 * @return void
 */
	function testAuthenticateChallenge() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$this->response->expects($this->at(0))
			->method('header')
			->with('WWW-Authenticate: Basic realm="localhost"');

		$this->response->expects($this->at(1))
			->method('send');

		$result = $this->auth->authenticate($request, $this->response);
		$this->assertFalse($result);
	}
/**
 * test authenticate sucesss
 *
 * @return void
 */
	function testAuthenticateSuccess() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = 'password';

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'user' => 'mariano',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test scope failure.
 *
 * @return void
 */
	function testAuthenticateFailReChallenge() {
		$this->auth->settings['scope'] = array('user' => 'nate');
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = 'password';

		$this->response->expects($this->at(0))
			->method('header')
			->with('WWW-Authenticate: Basic realm="localhost"');

		$this->response->expects($this->at(1))
			->method('statusCode')
			->with(401);

		$this->response->expects($this->at(2))
			->method('send');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

}
