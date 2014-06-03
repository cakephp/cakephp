<?php
/**
 * BasicAuthenticateTest file
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

App::uses('AuthComponent', 'Controller/Component');
App::uses('BasicAuthenticate', 'Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

require_once CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * Test case for BasicAuthentication
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class BasicAuthenticateTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Collection = $this->getMock('ComponentCollection');
		$this->auth = new BasicAuthenticate($this->Collection, array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User',
			'realm' => 'localhost',
			'recursive' => 0
		));

		$password = Security::hash('password', null, true);
		$User = ClassRegistry::init('User');
		$User->updateAll(array('password' => $User->getDataSource()->value($password)));
		$this->response = $this->getMock('CakeResponse');
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	public function testConstructor() {
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
	public function testAuthenticateNoData() {
		$request = new CakeRequest('posts/index', false);

		$this->response->expects($this->never())
			->method('header');

		$this->assertFalse($this->auth->getUser($request));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoUsername() {
		$request = new CakeRequest('posts/index', false);
		$_SERVER['PHP_AUTH_PW'] = 'foobar';

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoPassword() {
		$request = new CakeRequest('posts/index', false);
		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = null;

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateInjection() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_USER'] = '> 1';
		$_SERVER['PHP_AUTH_PW'] = "' OR 1 = 1";

		$this->assertFalse($this->auth->getUser($request));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * Test that username of 0 works.
 *
 * @return void
 */
	public function testAuthenticateUsernameZero() {
		$User = ClassRegistry::init('User');
		$User->updateAll(array('user' => $User->getDataSource()->value('0')), array('user' => 'mariano'));

		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array(
			'user' => '0',
			'password' => 'password'
		));
		$_SERVER['PHP_AUTH_USER'] = '0';
		$_SERVER['PHP_AUTH_PW'] = 'password';

		$expected = array(
			'id' => 1,
			'user' => '0',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertEquals($expected, $this->auth->authenticate($request, $this->response));
	}

/**
 * test that challenge headers are sent when no credentials are found.
 *
 * @return void
 */
	public function testAuthenticateChallenge() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		try {
			$this->auth->unauthenticated($request, $this->response);
		} catch (UnauthorizedException $e) {
		}

		$this->assertNotEmpty($e);

		$expected = array('WWW-Authenticate: Basic realm="localhost"');
		$this->assertEquals($expected, $e->responseHeader());
	}

/**
 * test authenticate success
 *
 * @return void
 */
	public function testAuthenticateSuccess() {
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
 * @expectedException UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testAuthenticateFailReChallenge() {
		$this->auth->settings['scope'] = array('user' => 'nate');
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = 'password';

		$this->auth->unauthenticated($request, $this->response);
	}

/**
 * testAuthenticateWithBlowfish
 *
 * @return void
 */
	public function testAuthenticateWithBlowfish() {
		$hash = Security::hash('password', 'blowfish');
		$this->skipIf(strpos($hash, '$2a$') === false, 'Skipping blowfish tests as hashing is not working');

		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = 'password';

		$User = ClassRegistry::init('User');
		$User->updateAll(
			array('password' => $User->getDataSource()->value($hash)),
			array('User.user' => 'mariano')
		);

		$this->auth->settings['passwordHasher'] = 'Blowfish';

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'user' => 'mariano',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertEquals($expected, $result);
	}

}
