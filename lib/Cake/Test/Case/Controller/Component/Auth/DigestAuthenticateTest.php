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

App::uses('DigestAuthenticate', 'Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

require_once  CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * Test case for DigestAuthentication
 *
 * @package cake.test.cases.controller.components.auth
 */
class DigestAuthenticateTest extends CakeTestCase {

	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->Collection = $this->getMock('ComponentCollection');
		$this->server = $_SERVER;
		$this->auth = new DigestAuthenticate($this->Collection, array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User',
			'realm' => 'localhost',
			'nonce' => 123,
			'opaque' => '123abc'
		));

		$password = DigestAuthenticate::password('mariano', 'cake', 'localhost');
		ClassRegistry::init('User')->updateAll(array('password' => '"' . $password . '"'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
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
		$object = new DigestAuthenticate($this->Collection, array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'password'),
			'nonce' => 123456
		));
		$this->assertEquals('AuthUser', $object->settings['userModel']);
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $object->settings['fields']);
		$this->assertEquals(123456, $object->settings['nonce']);
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
			->with('WWW-Authenticate: Digest realm="localhost",qop="auth",nonce="123",opaque="123abc"');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateWrongUsername() {
		$request = new CakeRequest('posts/index', false);
		$request->addParams(array('pass' => array(), 'named' => array()));

		$_SERVER['PHP_AUTH_DIGEST'] = <<<DIGEST
Digest username="incorrect_user",
realm="localhost",
nonce="123456",
uri="/dir/index.html",
qop=auth,
nc=00000001,
cnonce="0a4f113b",
response="6629fae49393a05397450978507c4ef1",
opaque="123abc"
DIGEST;

		$this->response->expects($this->at(0))
			->method('header')
			->with('WWW-Authenticate: Digest realm="localhost",qop="auth",nonce="123",opaque="123abc"');

		$this->response->expects($this->at(1))
			->method('statusCode')
			->with(401);

		$this->response->expects($this->at(2))
			->method('send');

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
			->with('WWW-Authenticate: Digest realm="localhost",qop="auth",nonce="123",opaque="123abc"');

		$this->response->expects($this->at(1))
			->method('statusCode')
			->with(401);

		$this->response->expects($this->at(2))
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

		$_SERVER['PHP_AUTH_DIGEST'] = <<<DIGEST
Digest username="mariano",
realm="localhost",
nonce="123",
uri="/dir/index.html",
qop=auth,
nc=1,
cnonce="123",
response="06b257a54befa2ddfb9bfa134224aa29",
opaque="123abc"
DIGEST;

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

		$_SERVER['PHP_AUTH_DIGEST'] = <<<DIGEST
Digest username="mariano",
realm="localhost",
nonce="123",
uri="/dir/index.html",
qop=auth,
nc=1,
cnonce="123",
response="6629fae49393a05397450978507c4ef1",
opaque="123abc"
DIGEST;

		$this->response->expects($this->at(0))
			->method('header')
			->with('WWW-Authenticate: Digest realm="localhost",qop="auth",nonce="123",opaque="123abc"');

		$this->response->expects($this->at(1))
			->method('statusCode')
			->with(401);

		$this->response->expects($this->at(2))
			->method('send');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testParseDigestAuthData method
 *
 * @access public
 * @return void
 */
	function testParseAuthData() {
		$digest = <<<DIGEST
			Digest username="Mufasa",
			realm="testrealm@host.com",
			nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
			uri="/dir/index.html",
			qop=auth,
			nc=00000001,
			cnonce="0a4f113b",
			response="6629fae49393a05397450978507c4ef1",
			opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
		$expected = array(
			'username' => 'Mufasa',
			'realm' => 'testrealm@host.com',
			'nonce' => 'dcd98b7102dd2f0e8b11d0f600bfb0c093',
			'uri' => '/dir/index.html',
			'qop' => 'auth',
			'nc' => '00000001',
			'cnonce' => '0a4f113b',
			'response' => '6629fae49393a05397450978507c4ef1',
			'opaque' => '5ccc069c403ebaf9f0171e9517f40e41'
		);
		$result = $this->auth->parseAuthData($digest);
		$this->assertSame($expected, $result);

		$result = $this->auth->parseAuthData('');
		$this->assertNull($result);
	}

/**
 * test parsing digest information with email addresses
 *
 * @return void
 */
	function testParseAuthEmailAddress() {
		$digest = <<<DIGEST
			Digest username="mark@example.com",
			realm="testrealm@host.com",
			nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093",
			uri="/dir/index.html",
			qop=auth,
			nc=00000001,
			cnonce="0a4f113b",
			response="6629fae49393a05397450978507c4ef1",
			opaque="5ccc069c403ebaf9f0171e9517f40e41"
DIGEST;
		$expected = array(
			'username' => 'mark@example.com',
			'realm' => 'testrealm@host.com',
			'nonce' => 'dcd98b7102dd2f0e8b11d0f600bfb0c093',
			'uri' => '/dir/index.html',
			'qop' => 'auth',
			'nc' => '00000001',
			'cnonce' => '0a4f113b',
			'response' => '6629fae49393a05397450978507c4ef1',
			'opaque' => '5ccc069c403ebaf9f0171e9517f40e41'
		);
		$result = $this->auth->parseAuthData($digest);
		$this->assertIdentical($expected, $result);
	}

/**
 * test password hashing
 *
 * @return void
 */
	function testPassword() {
		$result = DigestAuthenticate::password('mark', 'password', 'localhost');
		$expected = md5('mark:localhost:password');
		$this->assertEquals($expected, $result);
	}
}
