<?php
/**
 * DigestAuthenticateTest file
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

App::uses('DigestAuthenticate', 'Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

require_once CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * Test case for DigestAuthentication
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class DigestAuthenticateTest extends CakeTestCase {

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
		$this->server = $_SERVER;
		$this->auth = new DigestAuthenticate($this->Collection, array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User',
			'realm' => 'localhost',
			'nonce' => 123,
			'opaque' => '123abc'
		));

		$password = DigestAuthenticate::password('mariano', 'cake', 'localhost');
		$User = ClassRegistry::init('User');
		$User->updateAll(array('password' => $User->getDataSource()->value($password)));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->response = $this->getMock('CakeResponse');
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		$_SERVER = $this->server;
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	public function testConstructor() {
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
	public function testAuthenticateNoData() {
		$request = new CakeRequest('posts/index', false);

		$this->response->expects($this->never())
			->method('header');

		$this->assertFalse($this->auth->getUser($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @expectedException UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testAuthenticateWrongUsername() {
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

		$this->auth->unauthenticated($request, $this->response);
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

		$expected = array('WWW-Authenticate: Digest realm="localhost",qop="auth",nonce="123",opaque="123abc"');
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
 * @expectedException UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testAuthenticateFailReChallenge() {
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

		$this->auth->unauthenticated($request, $this->response);
	}

/**
 * testParseDigestAuthData method
 *
 * @return void
 */
	public function testParseAuthData() {
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
	public function testParseAuthEmailAddress() {
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
		$this->assertSame($expected, $result);
	}

/**
 * test password hashing
 *
 * @return void
 */
	public function testPassword() {
		$result = DigestAuthenticate::password('mark', 'password', 'localhost');
		$expected = md5('mark:localhost:password');
		$this->assertEquals($expected, $result);
	}
}
