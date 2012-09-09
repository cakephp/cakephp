<?php
/**
 * AuthComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentCollection;
use Cake\Controller\Component\AuthComponent;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Dispatcher;
use Cake\Routing\Router;
use Cake\TestSuite\Fixture\TestModel;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Security;
use TestApp\Controller\AuthTestController;
use TestApp\Controller\Component\TestAuthComponent;
use TestApp\Model\AuthUser;

/**
* AuthComponentTest class
*
* @package       Cake.Test.Case.Controller.Component
*/
class AuthComponentTest extends TestCase {

/**
 * name property
 *
 * @var string 'Auth'
 */
	public $name = 'Auth';

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.auth_user');

/**
 * initialized property
 *
 * @var bool false
 */
	public $initialized = false;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('Security.salt', 'YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
		Configure::write('Security.cipherSeed', 770011223369876);
		Configure::write('App.namespace', 'TestApp');

		$request = new Request();

		$this->Controller = new AuthTestController($request, $this->getMock('Cake\Network\Response'));

		$collection = new ComponentCollection();
		$collection->init($this->Controller);
		$this->Auth = new TestAuthComponent($collection);
		$this->Auth->request = $request;
		$this->Auth->response = $this->getMock('Cake\Network\Response');

		$this->Controller->Components->init($this->Controller);

		$this->initialized = true;
		Router::reload();
		Router::connect('/:controller/:action/*');

		$User = ClassRegistry::init('AuthUser');
		$User->updateAll(array('password' => $User->getDataSource()->value(Security::hash('cake', null, true))));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		TestAuthComponent::clearUser();
		$this->Auth->Session->delete('Auth');
		$this->Auth->Session->delete('Message.auth');
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
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->name = 'Error';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->name = 'Post';
		$this->Controller->request['action'] = 'thisdoesnotexist';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->scaffold = null;
		$this->Controller->request['action'] = 'index';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * testLogin method
 *
 * @return void
 */
	public function testLogin() {
		$this->getMock('Cake\Controller\Component\Auth\FormAuthenticate', array(), array(), 'AuthLoginFormAuthenticate', false);
		class_alias('AuthLoginFormAuthenticate', 'Cake\Controller\Component\Auth\AuthLoginFormAuthenticate');
		$this->Auth->authenticate = array(
			'AuthLoginForm' => array(
				'userModel' => 'AuthUser'
			)
		);
		$this->Auth->Session = $this->getMock('Cake\Controller\Component\SessionComponent', array('renew'), array(), '', false);

		$mocks = $this->Auth->constructAuthenticate();
		$this->mockObjects[] = $mocks[0];

		$this->Auth->request->data = array(
			'AuthUser' => array(
				'username' => 'mark',
				'password' => Security::hash('cake', null, true)
			)
		);

		$user = array(
			'id' => 1,
			'username' => 'mark'
		);

		$mocks[0]->expects($this->once())
			->method('authenticate')
			->with($this->Auth->request)
			->will($this->returnValue($user));

		$this->Auth->Session->expects($this->once())
			->method('renew');

		$result = $this->Auth->login();
		$this->assertTrue($result);

		$this->assertTrue($this->Auth->loggedIn());
		$this->assertEquals($user, $this->Auth->user());
	}

/**
 * test that being redirected to the login page, with no post data does
 * not set the session value.  Saving the session value in this circumstance
 * can cause the user to be redirected to an already public page.
 *
 * @return void
 */
	public function testLoginActionNotSettingAuthRedirect() {
		$_SERVER['HTTP_REFERER'] = '/pages/display/about';

		$this->Controller->data = array();
		$this->Controller->request->addParams(Router::parse('auth_test/login'));
		$this->Controller->request->url = 'auth_test/login';
		$this->Auth->Session->delete('Auth');

		$this->Auth->loginRedirect = '/users/dashboard';
		$this->Auth->loginAction = 'auth_test/login';
		$this->Auth->userModel = 'AuthUser';

		$this->Auth->startup($this->Controller);
		$redirect = $this->Auth->Session->read('Auth.redirect');
		$this->assertNull($redirect);
	}

/**
 * testAuthorizeFalse method
 *
 * @return void
 */
	public function testAuthorizeFalse() {
		$this->AuthUser = new AuthUser();
		$user = $this->AuthUser->find();
		$this->Auth->Session->write('Auth.User', $user['AuthUser']);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = false;
		$this->Controller->request->addParams(Router::parse('auth_test/add'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Auth->Session->delete('Auth');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertFalse($result);
		$this->assertTrue($this->Auth->Session->check('Message.auth'));

		$this->Controller->request->addParams(Router::parse('auth_test/camelCase'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertFalse($result);
	}

/**
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testIsAuthorizedMissingFile() {
		$this->Controller->Auth->authorize = 'Missing';
		$this->Controller->Auth->isAuthorized(array('User' => array('id' => 1)));
	}

/**
 * test that isAuthorized calls methods correctly
 *
 * @return void
 */
	public function testIsAuthorizedDelegation() {
		$this->getMock('Cake\Controller\Component\Auth\BaseAuthorize', array('authorize'), array(), 'AuthMockOneAuthorize', false);
		$this->getMock('Cake\Controller\Component\Auth\BaseAuthorize', array('authorize'), array(), 'AuthMockTwoAuthorize', false);
		$this->getMock('Cake\Controller\Component\Auth\BaseAuthorize', array('authorize'), array(), 'AuthMockThreeAuthorize', false);

		class_alias('AuthMockOneAuthorize', 'Cake\Controller\Component\Auth\AuthMockOneAuthorize');
		class_alias('AuthMockTwoAuthorize', 'Cake\Controller\Component\Auth\AuthMockTwoAuthorize');
		class_alias('AuthMockThreeAuthorize', 'Cake\Controller\Component\Auth\AuthMockThreeAuthorize');

		$this->Auth->authorize = array(
			'AuthMockOne',
			'AuthMockTwo',
			'AuthMockThree'
		);
		$mocks = $this->Auth->constructAuthorize();
		$request = $this->Auth->request;

		$this->assertEquals(3, count($mocks));
		$mocks[0]->expects($this->once())
			->method('authorize')
			->with(array('User'), $request)
			->will($this->returnValue(false));

		$mocks[1]->expects($this->once())
			->method('authorize')
			->with(array('User'), $request)
			->will($this->returnValue(true));

		$mocks[2]->expects($this->never())
			->method('authorize');

		$this->assertTrue($this->Auth->isAuthorized(array('User'), $request));
	}

/**
 * test that isAuthorized will use the session user if none is given.
 *
 * @return void
 */
	public function testIsAuthorizedUsingUserInSession() {
		$this->getMock('Cake\Controller\Component\Auth\BaseAuthorize', array('authorize'), array(), 'AuthMockFourAuthorize', false);
		class_alias('AuthMockFourAuthorize', 'Cake\Controller\Component\Auth\AuthMockFourAuthorize');
		$this->Auth->authorize = array('AuthMockFour');

		$user = array('user' => 'mark');
		$this->Auth->Session->write('Auth.User', $user);
		$mocks = $this->Auth->constructAuthorize();
		$request = $this->Controller->request;

		$mocks[0]->expects($this->once())
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
		$this->Controller->Auth->authorize = array(
			'Controller'
		);
		$result = $this->Controller->Auth->constructAuthorize();
		$this->assertEquals(1, count($result));

		$result = $this->Controller->Auth->constructAuthorize();
		$this->assertEquals(1, count($result));
	}

/**
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testLoadAuthenticateNoFile() {
		$this->Controller->Auth->authenticate = 'Missing';
		$this->Controller->Auth->identify($this->Controller->request, $this->Controller->response);
	}

/**
 * test the * key with authenticate
 *
 * @return void
 */
	public function testAllConfigWithAuthorize() {
		$this->Controller->Auth->authorize = array(
			AuthComponent::ALL => array('actionPath' => 'controllers/'),
			'Actions'
		);
		$objects = $this->Controller->Auth->constructAuthorize();
		$result = $objects[0];
		$this->assertEquals('controllers/', $result->settings['actionPath']);
	}

/**
 * test that loadAuthorize resets the loaded objects each time.
 *
 * @return void
 */
	public function testLoadAuthenticateResets() {
		$this->Controller->Auth->authenticate = array(
			'Form'
		);
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
		$this->Controller->Auth->authenticate = array(
			AuthComponent::ALL => array('userModel' => 'AuthUser'),
			'Form'
		);
		$objects = $this->Controller->Auth->constructAuthenticate();
		$result = $objects[0];
		$this->assertEquals('AuthUser', $result->settings['userModel']);
	}

/**
 * Tests that deny always takes precedence over allow
 *
 * @return void
 */
	public function testAllowDenyAll() {
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny('add', 'camelCase');

		$this->Controller->request['action'] = 'delete';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'add';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny(array('add', 'camelCase'));

		$this->Controller->request['action'] = 'delete';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->allow('*');
		$this->Controller->Auth->deny();

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'add';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->allow('camelCase');
		$this->Controller->Auth->deny();

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'login';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->deny();
		$this->Controller->Auth->allow(null);

		$this->Controller->request['action'] = 'camelCase';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny(null);

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * test that deny() converts camel case inputs to lowercase.
 *
 * @return void
 */
	public function testDenyWithCamelCaseMethods() {
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->allow();
		$this->Controller->Auth->deny('add', 'camelCase');

		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);

		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$url = '/auth_test/CamelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * test that allow() and allowedActions work with camelCase method names.
 *
 * @return void
 */
	public function testAllowedActionsWithCamelCaseMethods() {
		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->allow();
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result, 'startup() should return true, as action is allowed. %s');

		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->allowedActions = array('delete', 'camelCase', 'add');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result, 'startup() should return true, as action is allowed. %s');

		$this->Controller->Auth->allowedActions = array('delete', 'add');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertFalse($result, 'startup() should return false, as action is not allowed. %s');

		$url = '/auth_test/delete';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->allow(array('delete', 'add'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result, 'startup() should return true, as action is allowed. %s');
	}

	public function testAllowedActionsSetWithAllowMethod() {
		$url = '/auth_test/action_name';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->allow('action_name', 'anotherAction');
		$this->assertEquals(array('action_name', 'anotherAction'), $this->Controller->Auth->allowedActions);
	}

/**
 * testLoginRedirect method
 *
 * @return void
 */
	public function testLoginRedirect() {
		$_SERVER['HTTP_REFERER'] = false;
		$_ENV['HTTP_REFERER'] = false;
		putenv('HTTP_REFERER=');

		$this->Auth->Session->write('Auth', array(
			'AuthUser' => array('id' => '1', 'username' => 'nate')
		));

		$this->Auth->request->addParams(Router::parse('users/login'));
		$this->Auth->request->url = 'users/login';
		$this->Auth->initialize($this->Controller);

		$this->Auth->loginRedirect = array(
			'controller' => 'pages', 'action' => 'display', 'welcome'
		);
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize($this->Auth->loginRedirect);
		$this->assertEquals($expected, $this->Auth->redirect());

		$this->Auth->Session->delete('Auth');

		//empty referer no session
		$_SERVER['HTTP_REFERER'] = false;
		$_ENV['HTTP_REFERER'] = false;
		putenv('HTTP_REFERER=');
		$url = '/posts/view/1';

		$this->Auth->Session->write('Auth', array(
			'AuthUser' => array('id' => '1', 'username' => 'nate'))
		);
		$this->Controller->testUrl = null;
		$this->Auth->request->addParams(Router::parse($url));
		array_push($this->Controller->methods, 'view', 'edit', 'index');

		$this->Auth->initialize($this->Controller);
		$this->Auth->authorize = 'controller';

		$this->Auth->loginAction = array(
			'controller' => 'AuthTest', 'action' => 'login'
		);
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('/AuthTest/login');
		$this->assertEquals($expected, $this->Controller->testUrl);

		$this->Auth->Session->delete('Auth');
		$_SERVER['HTTP_REFERER'] = $_ENV['HTTP_REFERER'] = Router::url('/admin', true);
		$this->Auth->Session->write('Auth', array(
			'AuthUser' => array('id' => '1', 'username' => 'nate')
		));
		$this->Auth->request->params['action'] = 'login';
		$this->Auth->request->url = 'auth_test/login';
		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = 'auth_test/login';
		$this->Auth->loginRedirect = false;
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('/admin');
		$this->assertEquals($expected, $this->Auth->redirect());

		// Passed Arguments
		$this->Auth->Session->delete('Auth');
		$url = '/posts/view/1';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/view/1');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

		// QueryString parameters
		$_back = $_GET;
		$_GET = array(
			'print' => 'true',
			'refer' => 'menu'
		);
		$this->Auth->Session->delete('Auth');
		$url = '/posts/index/29';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->request->query = $_GET;

		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/index/29?print=true&refer=menu');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

		$_GET = $_back;

		// External Authed Action
		$_SERVER['HTTP_REFERER'] = 'http://webmail.example.com/view/message';
		$this->Auth->Session->delete('Auth');
		$url = '/posts/edit/1';
		$request = new Request($url);
		$request->query = array();
		$this->Auth->request = $this->Controller->request = $request;
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('/posts/edit/1');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

		// External Direct Login Link
		$_SERVER['HTTP_REFERER'] = 'http://webmail.example.com/view/message';
		$this->Auth->Session->delete('Auth');
		$url = '/AuthTest/login';
		$this->Auth->request = $this->Controller->request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = Router::normalize($url);
		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('/');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

		$this->Auth->Session->delete('Auth');
	}

/**
 * Default to loginRedirect, if set, on authError.
 *
 * @return void
 */
	public function testDefaultToLoginRedirect() {
		$_SERVER['HTTP_REFERER'] = false;
		$_ENV['HTTP_REFERER'] = false;
		putenv('HTTP_REFERER=');

		$url = '/party/on';
		$this->Auth->request = $Request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->authorize = array('Controller');
		$this->Auth->login(array('username' => 'mariano', 'password' => 'cake'));
		$this->Auth->loginRedirect = array(
			'controller' => 'something', 'action' => 'else',
		);

		$response = new Response();
		$Controller = $this->getMock(
			'Cake\Controller\Controller',
			array('on', 'redirect'),
			array($Request, $response)
		);

		$expected = Router::url($this->Auth->loginRedirect, true);
		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));
		$this->Auth->startup($Controller);
	}

/**
 * Test that no redirects or authorization tests occur on the loginAction
 *
 * @return void
 */
	public function testNoRedirectOnLoginAction() {
		$controller = $this->getMock('Cake\Controller\Controller');
		$controller->methods = array('login');

		$url = '/AuthTest/login';
		$this->Auth->request = $controller->request = new Request($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->authorize = array('Controller');

		$controller->expects($this->never())
			->method('redirect');

		$this->Auth->startup($controller);
	}

/**
 * Ensure that no redirect is performed when a 404 is reached
 * And the user doesn't have a session.
 *
 * @return void
 */
	public function testNoRedirectOn404() {
		$this->Auth->Session->delete('Auth');
		$this->Auth->initialize($this->Controller);
		$this->Auth->request->addParams(Router::parse('auth_test/something_totally_wrong'));
		$result = $this->Auth->startup($this->Controller);
		$this->assertTrue($result, 'Auth redirected a missing action %s');
	}

/**
 * testAdminRoute method
 *
 * @return void
 */
	public function testAdminRoute() {
		$pref = Configure::read('Routing.prefixes');
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();
		require CAKE . 'Config/routes.php';

		$url = '/admin/auth_test/add';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->query['url'] = ltrim($url, '/');
		$this->Auth->request->base = '';

		Router::setRequestInfo($this->Auth->request);
		$this->Auth->initialize($this->Controller);

		$this->Auth->loginAction = array(
			'prefix' => 'admin', 'controller' => 'auth_test', 'action' => 'login'
		);

		$this->Auth->startup($this->Controller);
		$this->assertEquals('/admin/auth_test/login', $this->Controller->testUrl);

		Configure::write('Routing.prefixes', $pref);
	}

/**
 * testAjaxLogin method
 *
 * @return void
 */
	public function testAjaxLogin() {
		App::build(array(
			'View' => array(CAKE . 'Test/TestApp/View/')
		));
		$_SERVER['HTTP_X_REQUESTED_WITH'] = "XMLHttpRequest";

		ob_start();
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch(new Request('/ajax_auth/add'), new Response(), array('return' => 1));
		$result = ob_get_clean();

		$this->assertEquals("Ajax!\nthis is the test element", str_replace("\r\n", "\n", $result));
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testLoginActionRedirect method
 *
 * @return void
 */
	public function testLoginActionRedirect() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();
		require CAKE . 'Config/routes.php';

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

		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = [
			'prefix' => 'admin',
			'controller' => 'auth_test',
			'action' => 'login'
		];
		$this->Auth->startup($this->Controller);

		$this->assertNull($this->Controller->testUrl);
	}

/**
 * Stateless auth methods like Basic should populate data that can be
 * accessed by $this->user().
 *
 * @return void
 */
	public function testStatelessAuthWorksWithUser() {
		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = 'cake';
		$url = '/auth_test/add';
		$this->Auth->request->addParams(Router::parse($url));

		$this->Auth->authenticate = array(
			'Basic' => array('userModel' => 'AuthUser')
		);
		$this->Auth->startup($this->Controller);

		$result = $this->Auth->user();
		$this->assertEquals('mariano', $result['username']);

		$result = $this->Auth->user('username');
		$this->assertEquals('mariano', $result);
	}

/**
 * Tests that shutdown destroys the redirect session var
 *
 * @return void
 */
	public function testShutDown() {
		$this->Auth->Session->write('Auth.User', 'not empty');
		$this->Auth->Session->write('Auth.redirect', 'foo');
		$this->Controller->Auth->loggedIn(true);

		$this->Controller->Auth->shutdown($this->Controller);
		$this->assertNull($this->Auth->Session->read('Auth.redirect'));
	}

/**
 * test $settings in Controller::$components
 *
 * @return void
 */
	public function testComponentSettings() {
		$request = new Request();
		$this->Controller = new AuthTestController($request, $this->getMock('Cake\Network\Response'));

		$this->Controller->components = array(
			'Auth' => array(
				'loginAction' => array('controller' => 'people', 'action' => 'login'),
				'logoutRedirect' => array('controller' => 'people', 'action' => 'login'),
			),
			'Session'
		);
		$this->Controller->Components->init($this->Controller);
		$this->Controller->Components->trigger('initialize', array(&$this->Controller));
		Router::reload();

		$expected = array(
			'loginAction' => array('controller' => 'people', 'action' => 'login'),
			'logoutRedirect' => array('controller' => 'people', 'action' => 'login'),
		);
		$this->assertEquals($expected['loginAction'], $this->Controller->Auth->loginAction);
		$this->assertEquals($expected['logoutRedirect'], $this->Controller->Auth->logoutRedirect);
	}

/**
 * test that logout deletes the session variables. and returns the correct url
 *
 * @return void
 */
	public function testLogout() {
		$this->Auth->Session->write('Auth.User.id', '1');
		$this->Auth->Session->write('Auth.redirect', '/users/login');
		$this->Auth->logoutRedirect = '/';
		$result = $this->Auth->logout();

		$this->assertEquals('/', $result);
		$this->assertNull($this->Auth->Session->read('Auth.AuthUser'));
		$this->assertNull($this->Auth->Session->read('Auth.redirect'));
	}

/**
 * Logout should trigger a logout method on authentication objects.
 *
 * @return void
 */
	public function testLogoutTrigger() {
		$this->getMock('Cake\Controller\Component\Auth\BaseAuthenticate', array('authenticate', 'logout'), array(), 'LogoutTriggerMockAuthenticate', false);
		class_alias('LogoutTriggerMockAuthenticate', 'Cake\Controller\Component\Auth\LogoutTriggerMockAuthenticate');

		$this->Auth->authenticate = array('LogoutTriggerMock');
		$mock = $this->Auth->constructAuthenticate();
		$mock[0]->expects($this->once())
			->method('logout');

		$this->Auth->logout();
	}

/**
 * test mapActions loading and delegating to authorize objects.
 *
 * @return void
 */
	public function testMapActionsDelegation() {
		$this->getMock('Cake\Controller\Component\Auth\BaseAuthorize', array('authorize'), array(), 'MapActionMockAuthorize', false);
		class_alias('MapActionMockAuthorize', 'Cake\Controller\Component\Auth\MapActionMockAuthorize');
		$this->Auth->authorize = array('MapActionMock');
		$mock = $this->Auth->constructAuthorize();
		$mock[0]->expects($this->once())
			->method('mapActions')
			->with(array('create' => array('my_action')));

		$this->Auth->mapActions(array('create' => array('my_action')));
	}

/**
 * test logging in with a request.
 *
 * @return void
 */
	public function testLoginWithRequestData() {
		$this->getMock('Cake\Controller\Component\Auth\FormAuthenticate', array(), array(), 'RequestLoginMockAuthenticate', false);
		class_alias('RequestLoginMockAuthenticate', 'Cake\Controller\Component\Auth\RequestLoginMockAuthenticate');
		$request = new Request('users/login');
		$user = array('username' => 'mark', 'role' => 'admin');

		$this->Auth->request = $request;
		$this->Auth->authenticate = array('RequestLoginMock');
		$mock = $this->Auth->constructAuthenticate();
		$mock[0]->expects($this->once())
			->method('authenticate')
			->with($request)
			->will($this->returnValue($user));

		$this->assertTrue($this->Auth->login());
		$this->assertEquals($user['username'], $this->Auth->user('username'));
	}

/**
 * test login() with user data
 *
 * @return void
 */
	public function testLoginWithUserData() {
		$this->assertFalse($this->Auth->loggedIn());

		$user = array(
			'username' => 'mariano',
			'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertTrue($this->Auth->login($user));
		$this->assertTrue($this->Auth->loggedIn());
		$this->assertEquals($user['username'], $this->Auth->user('username'));
	}

/**
 * test flash settings.
 *
 * @return void
 */
	public function testFlashSettings() {
		$this->Auth->Session = $this->getMock('Cake\Controller\Component\SessionComponent', array(), array(), '', false);
		$this->Auth->Session->expects($this->once())
			->method('setFlash')
			->with('Auth failure', 'custom', array(1), 'auth-key');

		$this->Auth->flash = array(
			'element' => 'custom',
			'params' => array(1),
			'key' => 'auth-key'
		);
		$this->Auth->flash('Auth failure');
	}

/**
 * test the various states of Auth::redirect()
 *
 * @return void
 */
	public function testRedirectSet() {
		$value = array('controller' => 'users', 'action' => 'home');
		$result = $this->Auth->redirect($value);
		$this->assertEquals('/users/home', $result);
		$this->assertEquals($value, $this->Auth->Session->read('Auth.redirect'));
	}

/**
 * test redirect using Auth.redirect from the session.
 *
 * @return void
 */
	public function testRedirectSessionRead() {
		$this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Auth->Session->write('Auth.redirect', '/users/home');

		$result = $this->Auth->redirect();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->Session->check('Auth.redirect'));
	}

/**
 * test that redirect does not return loginAction if that is what's stored in Auth.redirect.
 * instead loginRedirect should be used.
 *
 * @return void
 */
	public function testRedirectSessionReadEqualToLoginAction() {
		$this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');
		$this->Auth->Session->write('Auth.redirect', array('controller' => 'users', 'action' => 'login'));

		$result = $this->Auth->redirect();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->Session->check('Auth.redirect'));
	}

/**
 * test password hashing
 *
 * @return void
 */
	public function testPassword() {
		$result = $this->Auth->password('password');
		$expected = Security::hash('password', null, true);
		$this->assertEquals($expected, $result);
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
		$this->Auth->Session->write('Auth', $data);

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
}
