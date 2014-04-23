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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component\Auth;

use Cake\Controller\Component\Auth\DigestAuthenticate;
use Cake\Error;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Time;

/**
 * Test case for DigestAuthentication
 *
 */
class DigestAuthenticateTest extends TestCase {

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

		$this->Collection = $this->getMock('Cake\Controller\ComponentRegistry');
		$this->auth = new DigestAuthenticate($this->Collection, array(
			'realm' => 'localhost',
			'nonce' => 123,
			'opaque' => '123abc'
		));

		$password = DigestAuthenticate::password('mariano', 'cake', 'localhost');
		$User = TableRegistry::get('Users');
		$User->updateAll(['password' => $password], []);

		$this->response = $this->getMock('Cake\Network\Response');
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	public function testConstructor() {
		$object = new DigestAuthenticate($this->Collection, array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'pass'),
			'nonce' => 123456
		));
		$this->assertEquals('AuthUser', $object->config('userModel'));
		$this->assertEquals(array('username' => 'user', 'password' => 'pass'), $object->config('fields'));
		$this->assertEquals(123456, $object->config('nonce'));
		$this->assertEquals(env('SERVER_NAME'), $object->config('realm'));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoData() {
		$request = new Request('posts/index');

		$this->response->expects($this->never())
			->method('header');

		$this->assertFalse($this->auth->getUser($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @expectedException \Cake\Error\UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testAuthenticateWrongUsername() {
		$request = new Request('posts/index');
		$request->addParams(array('pass' => array()));

		$digest = <<<DIGEST
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
		$request->env('PHP_AUTH_DIGEST', $digest);

		$this->auth->unauthenticated($request, $this->response);
	}

/**
 * test that challenge headers are sent when no credentials are found.
 *
 * @return void
 */
	public function testAuthenticateChallenge() {
		$request = new Request([
			'url' => 'posts/index',
			'environment' => ['REQUEST_METHOD' => 'GET']
		]);
		$request->addParams(array('pass' => array()));

		try {
			$this->auth->unauthenticated($request, $this->response);
		} catch (Error\UnauthorizedException $e) {
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
		$request = new Request([
			'url' => 'posts/index',
			'environment' => ['REQUEST_METHOD' => 'GET']
		]);
		$request->addParams(array('pass' => array()));

		$digest = <<<DIGEST
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
		$request->env('PHP_AUTH_DIGEST', $digest);

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'mariano',
			'created' => new Time('2007-03-17 01:16:23'),
			'updated' => new Time('2007-03-17 01:18:31')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test scope failure.
 *
 * @expectedException \Cake\Error\UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testAuthenticateFailReChallenge() {
		$this->auth->config('scope.username', 'nate');
		$request = new Request([
			'url' => 'posts/index',
			'environment' => ['REQUEST_METHOD' => 'GET']
		]);
		$request->addParams(array('pass' => array()));

		$digest = <<<DIGEST
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
		$request->env('PHP_AUTH_DIGEST', $digest);

		$this->auth->unauthenticated($request, $this->response);
	}

/**
 * testLoginHeaders method
 *
 * @return void
 */
	public function testLoginHeaders() {
		$request = new Request([
			'environment' => ['SERVER_NAME' => 'localhost']
		]);
		$this->auth = new DigestAuthenticate($this->Collection, array(
			'realm' => 'localhost',
			'nonce' => '123'
		));
		$expected = 'WWW-Authenticate: Digest realm="localhost",qop="auth",nonce="123",opaque="421aa90e079fa326b6494f812ad13e79"';
		$result = $this->auth->loginHeaders($request);
		$this->assertEquals($expected, $result);
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
