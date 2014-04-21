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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component\Auth;

use Cake\Controller\Component\Auth\BasicAuthenticate;
use Cake\Error;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\Utility\Time;

/**
 * Test case for BasicAuthentication
 *
 */
class BasicAuthenticateTest extends TestCase {

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
		$this->auth = new BasicAuthenticate($this->Collection, array(
			'userModel' => 'Users',
			'realm' => 'localhost'
		));

		$password = Security::hash('password', 'blowfish', false);
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
		$object = new BasicAuthenticate($this->Collection, array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'password')
		));
		$this->assertEquals('AuthUser', $object->config('userModel'));
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $object->config('fields'));
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

		$this->assertFalse($this->auth->getUser($request));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoUsername() {
		$request = new Request([
			'url' => 'posts/index',
			'environment' => ['PHP_AUTH_PW' => 'foobar']
		]);

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoPassword() {
		$request = new Request([
			'url' => 'posts/index',
			'environment' => ['PHP_AUTH_USER' => 'mariano']
		]);

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateInjection() {
		$request = new Request([
			'url' => 'posts/index',
			'environment' => [
				'PHP_AUTH_USER' => '> 1',
				'PHP_AUTH_PW' => "' OR 1 = 1"
			]
		]);
		$request->addParams(array('pass' => array()));

		$this->assertFalse($this->auth->getUser($request));

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test that challenge headers are sent when no credentials are found.
 *
 * @return void
 */
	public function testAuthenticateChallenge() {
		$request = new Request('posts/index');
		$request->addParams(array('pass' => array()));

		try {
			$this->auth->unauthenticated($request, $this->response);
		} catch (Error\UnauthorizedException $e) {
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
		$request = new Request([
			'url' => 'posts/index',
			'environment' => [
				'PHP_AUTH_USER' => 'mariano',
				'PHP_AUTH_PW' => 'password'
			]
		]);
		$request->addParams(array('pass' => array()));

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
			'environment' => [
				'PHP_AUTH_USER' => 'mariano',
				'PHP_AUTH_PW' => 'password'
			]
		]);
		$request->addParams(array('pass' => array()));

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

		$request = new Request([
			'url' => 'posts/index',
			'environment' => [
				'PHP_AUTH_USER' => 'mariano',
				'PHP_AUTH_PW' => 'password'
			]
		]);
		$request->addParams(array('pass' => array()));

		$User = TableRegistry::get('Users');
		$User->updateAll(
			array('password' => $hash),
			array('username' => 'mariano')
		);

		$this->auth->config('passwordHasher', 'Blowfish');

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'mariano',
			'created' => new Time('2007-03-17 01:16:23'),
			'updated' => new Time('2007-03-17 01:18:31')
		);
		$this->assertEquals($expected, $result);
	}

}
