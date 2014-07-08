<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Network\Session;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use TestApp\Controller\AuthTestController;
use TestApp\Controller\Component\TestAuthComponent;

/**
 * AuthComponentTest class
 *
 */
class AuthComponentTest extends TestCase {

/**
 * name property
 *
 * @var string
 */
	public $name = 'Auth';

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = ['core.user', 'core.auth_user'];

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('Security.salt', 'YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
		Configure::write('App.namespace', 'TestApp');

		Router::scope('/', function($routes) {
			$routes->fallbacks();
		});

		$request = new Request();
		$response = $this->getMock('Cake\Network\Response', array('stop'));

		$this->Controller = new AuthTestController($request, $response);
		$this->Controller->constructClasses();

		$this->Auth = new TestAuthComponent($this->Controller->components());

		$Users = TableRegistry::get('AuthUsers');
		$Users->updateAll(['password' => password_hash('cake', PASSWORD_BCRYPT)], []);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		$_SESSION = [];
		unset($this->Controller, $this->Auth);
	}

/**
 * testNoAuth method
 *
 * @return void
 */
	public function testNoAuth() {
		$this->assertFalse($this->Auth->isAuthorized());
	}

/**
 * testIsErrorOrTests
 *
 * @return void
 */
	public function testIsErrorOrTests() {
		$event = new Event('Controller.startup', $this->Controller);

		$this->Controller->name = 'Error';
		$this->assertNull($this->Controller->Auth->startup($event));

		$this->Controller->name = 'Post';
		$this->Controller->request['action'] = 'thisdoesnotexist';
		$this->assertNull($this->Controller->Auth->startup($event));
	}

/**
 * testIdentify method
 *
 * @return void
 */
	public function testIdentify() {
		$AuthLoginFormAuthenticate = $this->getMock(
			'Cake\Controller\Component\Auth\FormAuthenticate',
			array('authenticate'), array(), '', false
		);
		$this->Auth->authenticate = array(
			'AuthLoginForm' => array(
				'userModel' => 'AuthUsers'
			)
		);

		$this->Auth->setAuthenticateObject(0, $AuthLoginFormAuthenticate);

		$this->Auth->request->data = array(
			'AuthUsers' => array(
				'username' => 'mark',
				'password' => Security::hash('cake', null, true)
			)
		);

		$user = array(
			'id' => 1,
			'username' => 'mark'
		);

		$AuthLoginFormAuthenticate->expects($this->once())
			->method('authenticate')
			->with($this->Auth->request)
			->will($this->returnValue($user));

		$result = $this->Auth->identify();
		$this->assertEquals($user, $result);
		$this->assertSame($AuthLoginFormAuthenticate, $this->Auth->authenticationProvider());
	}

/**
 * testRedirectVarClearing method
 *
 * @return void
 */
	public function testRedirectVarClearing() {
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'admin_add';
		$this->Controller->request->here = '/auth_test/admin_add';
		$this->assertNull($this->Auth->session->read('Auth.redirect'));

		$this->Auth->config('authenticate', ['Form']);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$this->assertEquals('/auth_test/admin_add', $this->Auth->session->read('Auth.redirect'));

		$this->Auth->session->write('Auth.User', array('username' => 'admad'));
		$this->Auth->startup($event, $this->Controller);
		$this->assertNull($this->Auth->session->read('Auth.redirect'));
	}

/**
 * testAuthorizeFalse method
 *
 * @return void
 */
	public function testAuthorizeFalse() {
		$event = new Event('Controller.startup', $this->Controller);
		$Users = TableRegistry::get('Users');
		$user = $Users->find('all')->hydrate(false)->first();
		$this->Auth->session->write('Auth.User', $user);
		$this->Controller->Auth->config('userModel', 'Users');
		$this->Controller->Auth->config('authorize', false);
		$this->Controller->request->addParams(Router::parse('auth_test/add'));
		$result = $this->Controller->Auth->startup($event);
		$this->assertNull($result);

		$this->Auth->session->delete('Auth');
		$result = $this->Controller->Auth->startup($event);
		$this->assertTrue($event->isStopped());
		$this->assertInstanceOf('Cake\Network\Response', $result);
		$this->assertTrue($this->Auth->session->check('Flash.auth'));

		$this->Controller->request->addParams(Router::parse('auth_test/camelCase'));
		$result = $this->Controller->Auth->startup($event);
		$this->assertInstanceOf('Cake\Network\Response', $result);
	}

/**
 * testIsAuthorizedMissingFile function
 *
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testIsAuthorizedMissingFile() {
		$this->Controller->Auth->config('authorize', 'Missing');
		$this->Controller->Auth->isAuthorized(array('User' => array('id' => 1)));
	}

/**
 * test that isAuthorized calls methods correctly
 *
 * @return void
 */
	public function testIsAuthorizedDelegation() {
		$AuthMockOneAuthorize = $this->getMock(
			'Cake\Controller\Component\BaseAuthorize',
			array('authorize'), array(), '', false
		);
		$AuthMockTwoAuthorize = $this->getMock(
			'Cake\Controller\Component\Auth\BaseAuthorize',
			array('authorize'), array(), '', false
		);
		$AuthMockThreeAuthorize = $this->getMock(
			'Cake\Controller\Component\Auth\BaseAuthorize',
			array('authorize'), array(), '', false
		);

		$this->Auth->setAuthorizeObject(0, $AuthMockOneAuthorize);
		$this->Auth->setAuthorizeObject(1, $AuthMockTwoAuthorize);
		$this->Auth->setAuthorizeObject(2, $AuthMockThreeAuthorize);
		$request = $this->Auth->request;

		$AuthMockOneAuthorize->expects($this->once())
			->method('authorize')
			->with(array('User'), $request)
			->will($this->returnValue(false));

		$AuthMockTwoAuthorize->expects($this->once())
			->method('authorize')
			->with(array('User'), $request)
			->will($this->returnValue(true));

		$AuthMockThreeAuthorize->expects($this->never())
			->method('authorize');

		$this->assertTrue($this->Auth->isAuthorized(array('User'), $request));
		$this->assertSame($AuthMockTwoAuthorize, $this->Auth->authorizationProvider());
	}

/**
 * test that isAuthorized will use the session user if none is given.
 *
 * @return void
 */
	public function testIsAuthorizedUsingUserInSession() {
		$AuthMockFourAuthorize = $this->getMock(
			'Cake\Controller\Component\Auth\BaseAuthorize',
			array('authorize'), array(), '', false
		);
		$this->Auth->config('authorize', ['AuthMockFour']);
		$this->Auth->setAuthorizeObject(0, $AuthMockFourAuthorize);

		$user = array('user' => 'mark');
		$this->Auth->session->write('Auth.User', $user);
		$request = $this->Controller->request;

		$AuthMockFourAuthorize->expects($this->once())
			->method('authorize')
			->with($user, $request)
			->will($this->returnValue(true));

		$this->assertTrue($this->Auth->isAuthorized(null, $request));
	}

/**
 * test that loadAuthorize resets the loaded objects each time.
 *
 * @return void
 */
	public function testLoadAuthorizeResets() {
		$this->Controller->Auth->config('authorize', ['Controller']);
		$result = $this->Controller->Auth->constructAuthorize();
		$this->assertEquals(1, count($result));

		$result = $this->Controller->Auth->constructAuthorize();
		$this->assertEquals(1, count($result));
	}

/**
 * testLoadAuthenticateNoFile function
 *
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testLoadAuthenticateNoFile() {
		$this->Controller->Auth->config('authenticate', 'Missing');
		$this->Controller->Auth->identify($this->Controller->request, $this->Controller->response);
	}

/**
 * test the * key with authenticate
 *
 * @return void
 */
	public function testAllConfigWithAuthorize() {
		$this->Controller->Auth->config('authorize', [
			AuthComponent::ALL => array('actionPath' => 'controllers/'),
			'Controller',
		]);
		$objects = $this->Controller->Auth->constructAuthorize();
		$result = $objects[0];
		$this->assertEquals('controllers/', $result->config('actionPath'));
	}

/**
 * test that loadAuthorize resets the loaded objects each time.
 *
 * @return void
 */
	public function testLoadAuthenticateResets() {
		$this->Controller->Auth->config('authenticate', ['Form']);
		$result = $this->Controller->Auth->constructAuthenticate();
		$this->assertEquals(1, count($result));

		$result = $this->Controller->Auth->constructAuthenticate();
		$this->assertEquals(1, count($result));
	}

/**
 * test the * key with authenticate
 *
 * @return void
 */
	public function testAllConfigWithAuthenticate() {
		$this->Controller->Auth->config('authenticate', [
			AuthComponent::ALL => array('userModel' => 'AuthUsers'),
			'Form'
		]);
		$objects = $this->Controller->Auth->constructAuthenticate();
		$result = $objects[0];
		$this->assertEquals('AuthUsers', $result->config('userModel'));
	}

/**
 * test defining the same Authenticate object but with different password hashers
 *
 * @return void
 */
	public function testSameAuthenticateWithDifferentHashers() {
		$this->Controller->Auth->config('authenticate', [
			'FormSimple' => ['className' => 'Form', 'passwordHasher' => 'Default'],
			'FormBlowfish' => ['className' => 'Form', 'passwordHasher' => 'Fallback'],
		]);

		$objects = $this->Controller->Auth->constructAuthenticate();
		$this->assertEquals(2, count($objects));

		$this->assertInstanceOf('Cake\Auth\FormAuthenticate', $objects[0]);
		$this->assertInstanceOf('Cake\Auth\FormAuthenticate', $objects[1]);

		$this->assertInstanceOf('Cake\Auth\DefaultPasswordHasher', $objects[0]->passwordHasher());
		$this->assertInstanceOf('Cake\Auth\FallbackPasswordHasher', $objects[1]->passwordHasher());
	}

/**
 * Tests that deny always takes precedence over allow
 *
 * @return void
 */
	public function testAllowDenyAll() {
		$event = new Event('Controller.startup', $this->Controller);

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny(['add', 'camelCase']);

		$this->Controller->request['action'] = 'delete';
		$this->assertNull($this->Controller->Auth->startup($event));

		$this->Controller->request['action'] = 'add';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->request['action'] = 'camelCase';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny(array('add', 'camelCase'));

		$this->Controller->request['action'] = 'delete';
		$this->assertNull($this->Controller->Auth->startup($event));

		$this->Controller->request['action'] = 'camelCase';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny();

		$this->Controller->request['action'] = 'camelCase';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->request['action'] = 'add';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->Auth->allow('camelCase');
		$this->Controller->Auth->deny();

		$this->Controller->request['action'] = 'camelCase';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->request['action'] = 'login';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$this->Controller->Auth->deny();
		$this->Controller->Auth->allow(null);

		$this->Controller->request['action'] = 'camelCase';
		$this->assertNull($this->Controller->Auth->startup($event));

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny(null);

		$this->Controller->request['action'] = 'camelCase';
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));
	}

/**
 * test that deny() converts camel case inputs to lowercase.
 *
 * @return void
 */
	public function testDenyWithCamelCaseMethods() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny(['add', 'camelCase']);

		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);

		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$url = '/auth_test/CamelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));
	}

/**
 * test that allow() and allowedActions work with camelCase method names.
 *
 * @return void
 */
	public function testAllowedActionsWithCamelCaseMethods() {
		$event = new Event('Controller.startup', $this->Controller);
		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUsers';
		$this->Controller->Auth->allow();
		$result = $this->Controller->Auth->startup($event);
		$this->assertNull($result, 'startup() should return null, as action is allowed. %s');

		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUsers';
		$this->Controller->Auth->allowedActions = array('delete', 'camelCase', 'add');
		$result = $this->Controller->Auth->startup($event);
		$this->assertNull($result, 'startup() should return null, as action is allowed. %s');

		$this->Controller->Auth->allowedActions = array('delete', 'add');
		$this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

		$url = '/auth_test/delete';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUsers';

		$this->Controller->Auth->allow(array('delete', 'add'));
		$result = $this->Controller->Auth->startup($event);
		$this->assertNull($result, 'startup() should return null, as action is allowed. %s');
	}

/**
 * testAllowedActionsSetWithAllowMethod method
 *
 * @return void
 */
	public function testAllowedActionsSetWithAllowMethod() {
		$url = '/auth_test/action_name';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->allow(['action_name', 'anotherAction']);
		$this->assertEquals(array('action_name', 'anotherAction'), $this->Controller->Auth->allowedActions);
	}

/**
 * testLoginRedirect method
 *
 * @return void
 */
	public function testLoginRedirect() {
		$url = '/auth_test/camelCase';

		$this->Auth->session->write('Auth', array(
			'AuthUsers' => array('id' => '1', 'username' => 'nate')
		));

		$this->Auth->request->addParams(Router::parse('users/login'));
		$this->Auth->request->url = 'users/login';
		$this->Auth->request->env('HTTP_REFERER', false);

		$this->Auth->config('loginRedirect', [
			'controller' => 'pages',
			'action' => 'display',
			'welcome'
		]);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize($this->Auth->config('loginRedirect'));
		$this->assertEquals($expected, $this->Auth->redirectUrl());

		$this->Auth->session->delete('Auth');

		$url = '/posts/view/1';

		$this->Auth->session->write('Auth', array(
			'AuthUsers' => array('id' => '1', 'username' => 'nate'))
		);
		$this->Controller->testUrl = null;
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->env('HTTP_REFERER', false);
		array_push($this->Controller->methods, 'view', 'edit', 'index');

		$this->Auth->config('authorize', 'controller');

		$this->Auth->config('loginAction', [
			'controller' => 'AuthTest', 'action' => 'login'
		]);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('/auth_test/login');
		$this->assertEquals($expected, $this->Controller->testUrl);

		$this->Auth->session->delete('Auth');
		$this->Auth->session->write('Auth', array(
			'AuthUsers' => array('id' => '1', 'username' => 'nate')
		));
		$this->Auth->request->params['action'] = 'login';
		$this->Auth->request->url = 'auth_test/login';
		$this->Controller->request->env('HTTP_REFERER', Router::url('/admin', true));
		$this->Auth->config('loginAction', 'auth_test/login');
		$this->Auth->config('loginRedirect', false);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('/admin');
		$this->assertEquals($expected, $this->Auth->redirectUrl());

		// Passed Arguments
		$this->Auth->session->delete('Auth');
		$url = '/posts/view/1';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('posts/view/1');
		$this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

		// QueryString parameters
		$this->Auth->session->delete('Auth');
		$url = '/posts/index/29';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->request->query = array(
			'print' => 'true',
			'refer' => 'menu'
		);

		$this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('posts/index/29?print=true&refer=menu');
		$this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

		// Different base urls.
		$appConfig = Configure::read('App');

		Configure::write('App', array(
			'dir' => APP_DIR,
			'webroot' => WEBROOT_DIR,
			'base' => false,
			'baseUrl' => '/cake/index.php'
		));

		$this->Auth->session->delete('Auth');

		$url = '/posts/add';
		$this->Auth->request = $this->Controller->request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = Router::normalize($url);

		$this->Auth->config('loginAction', ['controller' => 'users', 'action' => 'login']);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('/posts/add');
		$this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

		$this->Auth->session->delete('Auth');
		Configure::write('App', $appConfig);

		// External Authed Action
		$this->Auth->session->delete('Auth');
		$url = '/posts/edit/1';
		$request = new Request($url);
		$request->env('HTTP_REFERER', 'http://webmail.example.com/view/message');
		$request->query = array();
		$this->Auth->request = $this->Controller->request = $request;
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('/posts/edit/1');
		$this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

		// External Direct Login Link
		$this->Auth->session->delete('Auth');
		$url = '/auth_test/login';
		$this->Auth->request = $this->Controller->request = new Request($url);
		$this->Auth->request->env('HTTP_REFERER', 'http://webmail.example.com/view/message');
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = Router::normalize($url);
		$this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->startup($event);
		$expected = Router::normalize('/');
		$this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

		$this->Auth->session->delete('Auth');
	}

/**
 * testNoLoginRedirectForAuthenticatedUser method
 *
 * @return void
 */
	public function testNoLoginRedirectForAuthenticatedUser() {
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'login';
		$this->Controller->here = '/auth_test/login';
		$this->Auth->request->url = 'auth_test/login';

		$this->Auth->session->write('Auth.User.id', '1');
		$this->Auth->config('authenticate', ['Form']);
		$this->getMock(
			'Cake\Controller\Component\Auth\BaseAuthorize',
			array('authorize'), array(), 'NoLoginRedirectMockAuthorize', false
		);
		$this->Auth->config('authorize', ['NoLoginRedirectMockAuthorize']);
		$this->Auth->config('loginAction', ['controller' => 'auth_test', 'action' => 'login']);

		$event = new Event('Controller.startup', $this->Controller);
		$return = $this->Auth->startup($event);
		$this->assertNull($return);
		$this->assertNull($this->Controller->testUrl);
	}

/**
 * Default to loginRedirect, if set, on authError.
 *
 * @return void
 */
	public function testDefaultToLoginRedirect() {
		$url = '/party/on';
		$this->Auth->request = $Request = new Request($url);
		$Request->env('HTTP_REFERER', false);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->config('authorize', ['Controller']);
		$this->Auth->setUser(array('username' => 'mariano', 'password' => 'cake'));
		$this->Auth->config('loginRedirect', [
			'controller' => 'something', 'action' => 'else'
		]);

		$response = new Response();
		$Controller = $this->getMock(
			'Cake\Controller\Controller',
			array('on', 'redirect'),
			array($Request, $response)
		);
		$event = new Event('Controller.startup', $Controller);

		$expected = Router::url($this->Auth->config('loginRedirect'));
		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));
		$this->Auth->startup($event);
	}

/**
 * testRedirectToUnauthorizedRedirect
 *
 * @return void
 */
	public function testRedirectToUnauthorizedRedirect() {
		$url = '/party/on';
		$this->Auth->Flash = $this->getMock(
			'Cake\Controller\Component\FlashComponent',
			['set'],
			[$this->Controller->components()]
		);
		$this->Auth->request = $request = new Request([
			'url' => $url,
			'session' => $this->Auth->session
		]);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->config('authorize', ['Controller']);
		$this->Auth->setUser(array('username' => 'admad', 'password' => 'cake'));

		$expected = ['controller' => 'no_can_do', 'action' => 'jack'];
		$this->Auth->config('unauthorizedRedirect', $expected);

		$response = new Response();
		$Controller = $this->getMock(
			'Cake\Controller\Controller',
			array('on', 'redirect'),
			array($request, $response)
		);

		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));

		$this->Auth->Flash->expects($this->once())
			->method('set');

		$event = new Event('Controller.startup', $Controller);
		$this->Auth->startup($event);
	}

/**
 * testRedirectToUnauthorizedRedirectSuppressedAuthError
 *
 * @return void
 */
	public function testRedirectToUnauthorizedRedirectSuppressedAuthError() {
		$url = '/party/on';
		$this->Auth->session = $this->getMock(
			'Cake\Network\Session',
			array('flash')
		);
		$this->Auth->request = $Request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->config('authorize', ['Controller']);
		$this->Auth->setUser(array('username' => 'admad', 'password' => 'cake'));
		$expected = ['controller' => 'no_can_do', 'action' => 'jack'];
		$this->Auth->config('unauthorizedRedirect', $expected);
		$this->Auth->config('authError', false);

		$Response = new Response();
		$Controller = $this->getMock(
			'Cake\Controller\Controller',
			array('on', 'redirect'),
			array($Request, $Response)
		);

		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));

		$this->Auth->session->expects($this->never())
			->method('flash');

		$event = new Event('Controller.startup', $Controller);
		$this->Auth->startup($event);
	}

/**
 * Throw ForbiddenException if config `unauthorizedRedirect` is set to false
 *
 * @expectedException \Cake\Error\ForbiddenException
 * @return void
 */
	public function testForbiddenException() {
		$url = '/party/on';
		$this->Auth->request = $request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->config([
			'authorize' => ['Controller'],
			'unauthorizedRedirect' => false
		]);
		$this->Auth->setUser(array('username' => 'baker', 'password' => 'cake'));

		$response = new Response();
		$Controller = $this->getMock(
			'Cake\Controller\Controller',
			array('on', 'redirect'),
			array($request, $response)
		);

		$event = new Event('Controller.startup', $Controller);
		$this->Auth->startup($event);
	}

/**
 * Test that no redirects or authorization tests occur on the loginAction
 *
 * @return void
 */
	public function testNoRedirectOnLoginAction() {
		$event = new Event('Controller.startup', $this->Controller);
		$controller = $this->getMock('Cake\Controller\Controller');
		$controller->methods = array('login');

		$url = '/AuthTest/login';
		$this->Auth->request = $controller->request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->config([
			'loginAction', ['controller' => 'AuthTest', 'action' => 'login'],
			'authorize', ['Controller']
		]);

		$controller->expects($this->never())
			->method('redirect');

		$this->Auth->startup($event);
	}

/**
 * Ensure that no redirect is performed when a 404 is reached
 * And the user doesn't have a session.
 *
 * @return void
 */
	public function testNoRedirectOn404() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->session->delete('Auth');
		$this->Auth->request->addParams(Router::parse('auth_test/something_totally_wrong'));
		$result = $this->Auth->startup($event);
		$this->assertNull($result, 'Auth redirected a missing action %s');
	}

/**
 * testAdminRoute method
 *
 * @return void
 */
	public function testAdminRoute() {
		$event = new Event('Controller.startup', $this->Controller);
		Router::reload();
		Router::prefix('admin', function($routes) {
			$routes->fallbacks();
		});
		Router::scope('/', function($routes) {
			$routes->fallbacks();
		});

		$url = '/admin/auth_test/add';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->query['url'] = ltrim($url, '/');
		$this->Auth->request->base = '';

		Router::setRequestInfo($this->Auth->request);

		$this->Auth->config('loginAction', [
			'prefix' => 'admin',
			'controller' => 'auth_test',
			'action' => 'login'
		]);

		$this->Auth->startup($event);
		$this->assertEquals('/admin/auth_test/login', $this->Controller->testUrl);
	}

/**
 * testAjaxLogin method
 *
 * @return void
 */
	public function testAjaxLogin() {
		$this->Controller->request = new Request([
			'url' => '/ajax_auth/add',
			'environment' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
		]);
		$this->Controller->request->params['action'] = 'add';

		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->config('ajaxLogin', 'test_element');
		$this->Auth->RequestHandler->ajaxLayout = 'ajax2';

		$response = $this->Auth->startup($event);

		$this->assertTrue($event->isStopped());
		$this->assertEquals(403, $response->statusCode());
		$this->assertEquals(
			"Ajax!\nthis is the test element",
			str_replace("\r\n", "\n", $response->body())
		);
	}

/**
 * testLoginActionRedirect method
 *
 * @return void
 */
	public function testLoginActionRedirect() {
		$event = new Event('Controller.startup', $this->Controller);
		Router::reload();
		Router::prefix('admin', function($routes) {
			$routes->fallbacks();
		});
		Router::scope('/', function($routes) {
			$routes->fallbacks();
		});

		$url = '/admin/auth_test/login';
		$request = $this->Auth->request;
		$request->addParams([
			'plugin' => null,
			'controller' => 'auth_test',
			'action' => 'login',
			'prefix' => 'admin',
			'pass' => [],
		])->addPaths([
			'base' => null,
			'here' => $url,
			'webroot' => '/',
		]);
		$request->url = ltrim($url, '/');
		Router::setRequestInfo($request);

		$this->Auth->config('loginAction', [
			'prefix' => 'admin',
			'controller' => 'auth_test',
			'action' => 'login'
		]);
		$this->Auth->startup($event);

		$this->assertNull($this->Controller->testUrl);
	}

/**
 * Stateless auth methods like Basic should populate data that can be
 * accessed by $this->user().
 *
 * @return void
 */
	public function testStatelessAuthWorksWithUser() {
		$event = new Event('Controller.startup', $this->Controller);
		$url = '/auth_test/add';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->env('PHP_AUTH_USER', 'mariano');
		$this->Auth->request->env('PHP_AUTH_PW', 'cake');

		$this->Auth->config('authenticate', [
			'Basic' => array('userModel' => 'AuthUsers')
		]);
		$this->Auth->startup($event);

		$result = $this->Auth->user();
		$this->assertEquals('mariano', $result['username']);

		$result = $this->Auth->user('username');
		$this->assertEquals('mariano', $result);
	}

/**
 * test $settings in Controller::$components
 *
 * @return void
 */
	public function testComponentSettings() {
		Router::connect('/:controller');

		$request = new Request();
		$this->Controller = new AuthTestController($request, $this->getMock('Cake\Network\Response'));

		$this->Controller->components = array(
			'Auth' => array(
				'loginAction' => array('controller' => 'people', 'action' => 'login'),
				'logoutRedirect' => array('controller' => 'people', 'action' => 'login'),
			),
			'Session'
		);
		$this->Controller->constructClasses();

		$expected = array(
			'loginAction' => array('controller' => 'people', 'action' => 'login'),
			'logoutRedirect' => array('controller' => 'people', 'action' => 'login'),
		);
		$this->assertEquals(
			$expected['loginAction'],
			$this->Controller->Auth->config('loginAction')
		);
		$this->assertEquals(
			$expected['logoutRedirect'],
			$this->Controller->Auth->config('logoutRedirect')
		);
	}

/**
 * test that logout deletes the session variables. and returns the correct URL
 *
 * @return void
 */
	public function testLogout() {
		$this->Auth->session->write('Auth.User.id', '1');
		$this->Auth->session->write('Auth.redirect', '/Users/login');
		$this->Auth->config('logoutRedirect', '/');
		$result = $this->Auth->logout();

		$this->assertEquals('/', $result);
		$this->assertNull($this->Auth->session->read('Auth.AuthUsers'));
		$this->assertNull($this->Auth->session->read('Auth.redirect'));
	}

/**
 * Logout should trigger a logout method on authentication objects.
 *
 * @return void
 */
	public function testLogoutTrigger() {
		$LogoutTriggerMockAuthenticate = $this->getMock(
			'Cake\Controller\Component\Auth\BaseAuthenticate',
			array('authenticate', 'logout'), array(), '', false
		);

		$this->Auth->config('authenticate', ['LogoutTriggerMock']);
		$this->Auth->setAuthenticateObject(0, $LogoutTriggerMockAuthenticate);
		$LogoutTriggerMockAuthenticate->expects($this->once())
			->method('logout');

		$this->Auth->logout();
	}

/**
 * test mapActions loading and delegating to authorize objects.
 *
 * @return void
 */
	public function testMapActionsDelegation() {
		$MapActionMockAuthorize = $this->getMock(
			'Cake\Controller\Component\Auth\BaseAuthorize',
			array('authorize', 'mapActions'), array(), '', false
		);

		$this->Auth->authorize = array('MapActionMock');
		$this->Auth->setAuthorizeObject(0, $MapActionMockAuthorize);
		$MapActionMockAuthorize->expects($this->once())
			->method('mapActions')
			->with(array('create' => array('my_action')));

		$this->Auth->mapActions(array('create' => array('my_action')));
	}

/**
 * test setting user info to session.
 *
 * @return void
 */
	public function testSetUser() {
		$this->Auth->session = $this->getMock(
			'Cake\Network\Session',
			array('renew', 'write')
		);

		$user = array('username' => 'mark', 'role' => 'admin');

		$this->Auth->session->expects($this->once())
			->method('renew');

		$this->Auth->session->expects($this->once())
			->method('write')
			->with($this->Auth->sessionKey, $user);

		$this->Auth->setUser($user);
	}

/**
 * testGettingUserAfterSetUser
 *
 * @return void
 */
	public function testGettingUserAfterSetUser() {
		$this->assertFalse((bool)$this->Auth->user());

		$user = array(
			'username' => 'mariano',
			'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
			'created' => new \DateTime('2007-03-17 01:16:23'),
			'updated' => new \DateTime('2007-03-17 01:18:31')
		);
		$this->Auth->setUser($user);
		$this->assertTrue((bool)$this->Auth->user());
		$this->assertEquals($user['username'], $this->Auth->user('username'));
	}

/**
 * test flash settings.
 *
 * @return void
 */
	public function testFlashSettings() {
		$this->Auth->Flash = $this->getMock(
			'Cake\Controller\Component\FlashComponent',
			[],
			[$this->Controller->components()]
		);
		$this->Auth->Flash->expects($this->at(0))
			->method('set')
			->with('Auth failure', array('key' => 'auth-key', 'element' => 'custom'));

		$this->Auth->Flash->expects($this->at(1))
			->method('set')
			->with('Auth failure', array('element' => 'error', 'key' => 'auth-key'));

		$this->Auth->config('flash', [
			'params' => array('element' => 'custom'),
			'key' => 'auth-key'
		]);
		$this->Auth->flash('Auth failure');

		$this->Auth->config('flash', [
			'key' => 'auth-key'
		], false);
		$this->Auth->flash('Auth failure');
	}

/**
 * test the various states of Auth::redirect()
 *
 * @return void
 */
	public function testRedirectSet() {
		$value = array('controller' => 'users', 'action' => 'home');
		$result = $this->Auth->redirectUrl($value);
		$this->assertEquals('/users/home', $result);
		$this->assertEquals($value, $this->Auth->session->read('Auth.redirect'));

		$request = new Request();
		$request->base = '/base';
		Router::setRequestInfo($request);

		$result = $this->Auth->redirectUrl($value);
		$this->assertEquals('/users/home', $result);
	}

/**
 * test redirect using Auth.redirect from the session.
 *
 * @return void
 */
	public function testRedirectSessionRead() {
		$this->Auth->config('loginAction', ['controller' => 'users', 'action' => 'login']);
		$this->Auth->session->write('Auth.redirect', '/users/home');

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->session->check('Auth.redirect'));
	}

/**
 * test redirectUrl with duplicate base.
 *
 * @return void
 */
	public function testRedirectSessionReadDuplicateBase() {
		$this->Auth->request->webroot = '/waves/';
		$this->Auth->request->base = '/waves';

		Router::setRequestInfo($this->Auth->request);

		$this->Auth->session->write('Auth.redirect', '/waves/add');

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/waves/add', $result);
	}

/**
 * test that redirect does not return loginAction if that is what's stored in Auth.redirect.
 * instead loginRedirect should be used.
 *
 * @return void
 */
	public function testRedirectSessionReadEqualToLoginAction() {
		$this->Auth->config([
			'loginAction' => ['controller' => 'users', 'action' => 'login'],
			'loginRedirect' => ['controller' => 'users', 'action' => 'home']
		]);
		$this->Auth->session->write('Auth.redirect', array('controller' => 'users', 'action' => 'login'));

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->session->check('Auth.redirect'));
	}

/**
 * test that the returned URL doesn't contain the base URL.
 *
 * @see https://cakephp.lighthouseapp.com/projects/42648/tickets/3922-authcomponentredirecturl-prepends-appbaseurl
 *
 * @return void This test method doesn't return anything.
 */
	public function testRedirectUrlWithBaseSet() {
		$App = Configure::read('App');

		Configure::write('App', array(
			'dir' => APP_DIR,
			'webroot' => WEBROOT_DIR,
			'base' => false,
			'baseUrl' => '/cake/index.php'
		));

		$url = '/users/login';
		$this->Auth->request = $this->Controller->request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = Router::normalize($url);

		Router::setRequestInfo($this->Auth->request);

		$this->Auth->config('loginAction', ['controller' => 'users', 'action' => 'login']);
		$this->Auth->config('loginRedirect', ['controller' => 'users', 'action' => 'home']);

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->session->check('Auth.redirect'));

		Configure::write('App', $App);
		Router::reload();
	}

/**
 * testUser method
 *
 * @return void
 */
	public function testUser() {
		$data = array(
			'User' => array(
				'id' => '2',
				'username' => 'mark',
				'group_id' => 1,
				'Group' => array(
					'id' => '1',
					'name' => 'Members'
				),
				'is_admin' => false,
		));
		$this->Auth->session->write('Auth', $data);

		$result = $this->Auth->user();
		$this->assertEquals($data['User'], $result);

		$result = $this->Auth->user('username');
		$this->assertEquals($data['User']['username'], $result);

		$result = $this->Auth->user('Group.name');
		$this->assertEquals($data['User']['Group']['name'], $result);

		$result = $this->Auth->user('invalid');
		$this->assertEquals(null, $result);

		$result = $this->Auth->user('Company.invalid');
		$this->assertEquals(null, $result);

		$result = $this->Auth->user('is_admin');
		$this->assertFalse($result);
	}

/**
 * testStatelessAuthNoRedirect method
 *
 * @expectedException \Cake\Error\UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testStatelessAuthNoRedirect() {
		$event = new Event('Controller.startup', $this->Controller);
		$_SESSION = [];

		$this->sessionKey = false;
		$this->Auth->config('authenticate', ['Basic']);
		$this->Controller->request['action'] = 'admin_add';

		$result = $this->Auth->startup($event);
	}

/**
 * testStatelessAuthRedirect method
 *
 * @return void
 */
	public function testStatelessFollowedByStatefulAuth() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Auth->authenticate = array('Basic', 'Form');
		$this->Controller->request['action'] = 'admin_add';

		$this->Auth->response->expects($this->never())->method('statusCode');
		$this->Auth->response->expects($this->never())->method('send');

		$this->assertInstanceOf('Cake\Network\Response', $this->Auth->startup($event));

		$this->assertEquals('/users/login', $this->Controller->testUrl);
	}
}
