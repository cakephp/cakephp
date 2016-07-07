<?php
/**
 * AuthComponentTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('AclComponent', 'Controller/Component');
App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('FormAuthenticate', 'Controller/Component/Auth');
App::uses('CakeEvent', 'Event');

/**
 * TestFormAuthenticate class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class TestBaseAuthenticate extends BaseAuthenticate {

/**
 * Implemented events
 *
 * @return array of events => callbacks.
 */
	public function implementedEvents() {
		return array(
			'Auth.afterIdentify' => 'afterIdentify'
		);
	}

	public $afterIdentifyCallable = null;

/**
 * Test function to be used in event dispatching
 *
 * @return void
 */
	public function afterIdentify($event) {
		call_user_func($this->afterIdentifyCallable, $event);
	}

/**
 * Authenticate a user based on the request information.
 *
 * @param CakeRequest $request Request to get authentication information from.
 * @param CakeResponse $response A response object that can have headers added.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		return array(
			'id' => 1,
			'username' => 'mark'
		);
	}

}

/**
 * TestAuthComponent class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class TestAuthComponent extends AuthComponent {

/**
 * testStop property
 *
 * @var bool
 */
	public $testStop = false;

/**
 * Helper method to add/set an authenticate object instance
 *
 * @param int $index The index at which to add/set the object
 * @param object $object The object to add/set
 * @return void
 */
	public function setAuthenticateObject($index, $object) {
		$this->_authenticateObjects[$index] = $object;
	}

/**
 * Helper method to get an authenticate object instance
 *
 * @param int $index The index at which to get the object
 * @return object $object
 */
	public function getAuthenticateObject($index) {
		$this->constructAuthenticate();
		return isset($this->_authenticateObjects[$index]) ? $this->_authenticateObjects[$index] : null;
	}

/**
 * Helper method to add/set an authorize object instance
 *
 * @param int $index The index at which to add/set the object
 * @param Object $object The object to add/set
 * @return void
 */
	public function setAuthorizeObject($index, $object) {
		$this->_authorizeObjects[$index] = $object;
	}

/**
 * stop method
 *
 * @return void
 */
	protected function _stop($status = 0) {
		$this->testStop = true;
	}

	public static function clearUser() {
		static::$_user = array();
	}

}

/**
 * AuthUser class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AuthUser extends CakeTestModel {

/**
 * useDbConfig property
 *
 * @var string
 */
	public $useDbConfig = 'test';

}

/**
 * AuthTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AuthTestController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array('AuthUser');

/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'Flash', 'Auth');

/**
 * testUrl property
 *
 * @var mixed
 */
	public $testUrl = null;

/**
 * construct method
 */
	public function __construct($request, $response) {
		$request->addParams(Router::parse('/auth_test'));
		$request->here = '/auth_test';
		$request->webroot = '/';
		Router::setRequestInfo($request);
		parent::__construct($request, $response);
	}

/**
 * login method
 *
 * @return void
 */
	public function login() {
	}

/**
 * admin_login method
 *
 * @return void
 */
	public function admin_login() {
	}

/**
 * admin_add method
 *
 * @return void
 */
	public function admin_add() {
	}

/**
 * logout method
 *
 * @return void
 */
	public function logout() {
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		echo "add";
	}

/**
 * add method
 *
 * @return void
 */
	public function camelCase() {
		echo "camelCase";
	}

/**
 * redirect method
 *
 * @param string|array $url
 * @param mixed $status
 * @param mixed $exit
 * @return void
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->testUrl = Router::url($url);
		return false;
	}

/**
 * isAuthorized method
 *
 * @return void
 */
	public function isAuthorized() {
	}

}

/**
 * AjaxAuthController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AjaxAuthController extends Controller {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'TestAuth');

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * testUrl property
 *
 * @var mixed
 */
	public $testUrl = null;

/**
 * beforeFilter method
 *
 * @return void
 */
	public function beforeFilter() {
		$this->TestAuth->ajaxLogin = 'test_element';
		$this->TestAuth->userModel = 'AuthUser';
		$this->TestAuth->RequestHandler->ajaxLayout = 'ajax2';
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->TestAuth->testStop !== true) {
			echo 'Added Record';
		}
	}

/**
 * redirect method
 *
 * @param string|array $url
 * @param mixed $status
 * @param mixed $exit
 * @return void
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->testUrl = Router::url($url);
		return false;
	}

}

/**
 * Mock class used to test event dispatching
 *
 * @package Cake.Test.Case.Event
 */
class AuthEventTestListener {

	public $callStack = array();

/**
 * Test function to be used in event dispatching
 *
 * @return void
 */
	public function listenerFunction() {
		$this->callStack[] = __FUNCTION__;
	}

}


/**
 * AuthComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AuthComponentTest extends CakeTestCase {

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
	public $fixtures = array('core.auth_user');

/**
 * initialized property
 *
 * @var bool
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

		$request = new CakeRequest(null, false);

		$this->Controller = new AuthTestController($request, $this->getMock('CakeResponse'));

		$collection = new ComponentCollection();
		$collection->init($this->Controller);
		$this->Auth = new TestAuthComponent($collection);
		$this->Auth->request = $request;
		$this->Auth->response = $this->getMock('CakeResponse');
		AuthComponent::$sessionKey = 'Auth.User';

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

		$this->Controller->name = 'CakeError';
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
		$AuthLoginFormAuthenticate = $this->getMock('FormAuthenticate', array(), array(), '', false);
		$this->Auth->authenticate = array(
			'AuthLoginForm' => array(
				'userModel' => 'AuthUser'
			)
		);
		$this->Auth->Session = $this->getMock('SessionComponent', array('renew'), array(), '', false);

		$this->Auth->setAuthenticateObject(0, $AuthLoginFormAuthenticate);

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

		$AuthLoginFormAuthenticate->expects($this->once())
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
 * testLogin afterIdentify event method
 *
 * @return void
 */
	public function testLoginAfterIdentify() {
		$this->Auth->authenticate = array(
			'TestBase',
		);

		$user = array(
			'id' => 1,
			'username' => 'mark'
		);

		$auth = $this->Auth->getAuthenticateObject(0);
		$listener = $this->getMock('AuthEventTestListener');
		$auth->afterIdentifyCallable = array($listener, 'listenerFunction');
		$event = new CakeEvent('Auth.afterIdentify', $this->Auth, array('user' => $user));
		$listener->expects($this->once())->method('listenerFunction')->with($event);

		$result = $this->Auth->login();
		$this->assertTrue($result);
		$this->assertTrue($this->Auth->loggedIn());
		$this->assertEquals($user, $this->Auth->user());
	}

/**
 * testRedirectVarClearing method
 *
 * @return void
 */
	public function testRedirectVarClearing() {
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'admin_add';
		$this->Controller->here = '/auth_test/admin_add';
		$this->assertNull($this->Auth->Session->read('Auth.redirect'));

		$this->Auth->authenticate = array('Form');
		$this->Auth->startup($this->Controller);
		$this->assertEquals('/auth_test/admin_add', $this->Auth->Session->read('Auth.redirect'));

		$this->Auth->Session->write('Auth.User', array('username' => 'admad'));
		$this->Auth->startup($this->Controller);
		$this->assertNull($this->Auth->Session->read('Auth.redirect'));
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
		$this->Controller->Auth->initialize($this->Controller);
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
 * @expectedException CakeException
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
		$AuthMockOneAuthorize = $this->getMock('BaseAuthorize', array('authorize'), array(), '', false);
		$AuthMockTwoAuthorize = $this->getMock('BaseAuthorize', array('authorize'), array(), '', false);
		$AuthMockThreeAuthorize = $this->getMock('BaseAuthorize', array('authorize'), array(), '', false);

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
	}

/**
 * test that isAuthorized will use the session user if none is given.
 *
 * @return void
 */
	public function testIsAuthorizedUsingUserInSession() {
		$AuthMockFourAuthorize = $this->getMock('BaseAuthorize', array('authorize'), array(), '', false);
		$this->Auth->authorize = array('AuthMockFour');
		$this->Auth->setAuthorizeObject(0, $AuthMockFourAuthorize);

		$user = array('user' => 'mark');
		$this->Auth->Session->write('Auth.User', $user);
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
		$this->Controller->Auth->authorize = array(
			'Controller'
		);
		$result = $this->Controller->Auth->constructAuthorize();
		$this->assertEquals(1, count($result));

		$result = $this->Controller->Auth->constructAuthorize();
		$this->assertEquals(1, count($result));
	}

/**
 * @expectedException CakeException
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
 * test defining the same Authenticate object but with different password hashers
 *
 * @return void
 */
	public function testSameAuthenticateWithDifferentHashers() {
		$this->Controller->Auth->authenticate = array(
			'FormSimple' => array('className' => 'Form', 'passwordHasher' => 'Simple'),
			'FormBlowfish' => array('className' => 'Form', 'passwordHasher' => 'Blowfish'),
		);

		$objects = $this->Controller->Auth->constructAuthenticate();
		$this->assertEquals(2, count($objects));

		$this->assertInstanceOf('FormAuthenticate', $objects[0]);
		$this->assertInstanceOf('FormAuthenticate', $objects[1]);

		$this->assertInstanceOf('SimplePasswordHasher', $objects[0]->passwordHasher());
		$this->assertInstanceOf('BlowfishPasswordHasher', $objects[1]->passwordHasher());
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
		$this->assertEquals($expected, $this->Auth->redirectUrl());

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
		$this->assertEquals($expected, $this->Auth->redirectUrl());

		// Ticket #4750
		// Named Parameters
		$this->Controller->request = $this->Auth->request;
		$this->Auth->Session->delete('Auth');
		$url = '/posts/index/year:2008/month:feb';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/index/year:2008/month:feb');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

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

		// Different base urls.
		$appConfig = Configure::read('App');

		$_GET = array();

		Configure::write('App', array(
			'dir' => APP_DIR,
			'webroot' => WEBROOT_DIR,
			'base' => false,
			'baseUrl' => '/cake/index.php'
		));

		$this->Auth->Session->delete('Auth');

		$url = '/posts/add';
		$this->Auth->request = $this->Controller->request = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = Router::normalize($url);

		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('/posts/add');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

		$this->Auth->Session->delete('Auth');
		Configure::write('App', $appConfig);

		$_GET = $_back;

		// External Authed Action
		$_SERVER['HTTP_REFERER'] = 'http://webmail.example.com/view/message';
		$this->Auth->Session->delete('Auth');
		$url = '/posts/edit/1';
		$request = new CakeRequest($url);
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
		$this->Auth->request = $this->Controller->request = new CakeRequest($url);
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
 * testNoLoginRedirectForAuthenticatedUser method
 *
 * @return void
 */
	public function testNoLoginRedirectForAuthenticatedUser() {
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'login';
		$this->Controller->here = '/auth_test/login';
		$this->Auth->request->url = 'auth_test/login';

		$this->Auth->Session->write('Auth.User.id', '1');
		$this->Auth->authenticate = array('Form');
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'NoLoginRedirectMockAuthorize', false);
		$this->Auth->authorize = array('NoLoginRedirectMockAuthorize');
		$this->Auth->loginAction = array('controller' => 'auth_test', 'action' => 'login');

		$return = $this->Auth->startup($this->Controller);
		$this->assertTrue($return);
		$this->assertNull($this->Controller->testUrl);
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
		$this->Auth->request = $CakeRequest = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->authorize = array('Controller');
		$this->Auth->login(array('username' => 'mariano', 'password' => 'cake'));
		$this->Auth->loginRedirect = array(
			'controller' => 'something', 'action' => 'else',
		);

		$CakeResponse = new CakeResponse();
		$Controller = $this->getMock(
			'Controller',
			array('on', 'redirect'),
			array($CakeRequest, $CakeResponse)
		);

		$expected = Router::url($this->Auth->loginRedirect);
		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));
		$this->Auth->startup($Controller);
	}

/**
 * testRedirectToUnauthorizedRedirect
 *
 * @return void
 */
	public function testRedirectToUnauthorizedRedirect() {
		$url = '/party/on';
		$this->Auth->request = $CakeRequest = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->authorize = array('Controller');
		$this->Auth->login(array('username' => 'admad', 'password' => 'cake'));
		$this->Auth->unauthorizedRedirect = array(
			'controller' => 'no_can_do', 'action' => 'jack'
		);

		$CakeResponse = new CakeResponse();
		$Controller = $this->getMock(
			'Controller',
			array('on', 'redirect'),
			array($CakeRequest, $CakeResponse)
		);
		$this->Auth->Flash = $this->getMock(
			'FlashComponent',
			array('set'),
			array($Controller->Components)
		);

		$expected = array(
			'controller' => 'no_can_do', 'action' => 'jack'
		);
		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));
		$this->Auth->Flash->expects($this->once())
			->method('set');
		$this->Auth->startup($Controller);
	}

/**
 * testRedirectToUnauthorizedRedirectSuppressedAuthError
 *
 * @return void
 */
	public function testRedirectToUnauthorizedRedirectSuppressedAuthError() {
		$url = '/party/on';
		$this->Auth->request = $CakeRequest = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->authorize = array('Controller');
		$this->Auth->login(array('username' => 'admad', 'password' => 'cake'));
		$this->Auth->unauthorizedRedirect = array(
			'controller' => 'no_can_do', 'action' => 'jack'
		);
		$this->Auth->authError = false;

		$CakeResponse = new CakeResponse();
		$Controller = $this->getMock(
			'Controller',
			array('on', 'redirect'),
			array($CakeRequest, $CakeResponse)
		);
		$this->Auth->Flash = $this->getMock(
			'FlashComponent',
			array('set'),
			array($Controller->Components)
		);

		$expected = array(
			'controller' => 'no_can_do', 'action' => 'jack'
		);
		$Controller->expects($this->once())
			->method('redirect')
			->with($this->equalTo($expected));
		$this->Auth->Flash->expects($this->never())
			->method('set');
		$this->Auth->startup($Controller);
	}

/**
 * Throw ForbiddenException if AuthComponent::$unauthorizedRedirect set to false
 * @expectedException ForbiddenException
 * @return void
 */
	public function testForbiddenException() {
		$url = '/party/on';
		$this->Auth->request = $CakeRequest = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->authorize = array('Controller');
		$this->Auth->authorize = array('Controller');
		$this->Auth->unauthorizedRedirect = false;
		$this->Auth->login(array('username' => 'baker', 'password' => 'cake'));

		$CakeResponse = new CakeResponse();
		$Controller = $this->getMock(
			'Controller',
			array('on', 'redirect'),
			array($CakeRequest, $CakeResponse)
		);

		$this->Auth->startup($Controller);
	}

/**
 * Test that no redirects or authorization tests occur on the loginAction
 *
 * @return void
 */
	public function testNoRedirectOnLoginAction() {
		$controller = $this->getMock('Controller');
		$controller->methods = array('login');

		$url = '/AuthTest/login';
		$this->Auth->request = $controller->request = new CakeRequest($url);
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
		require CAKE . 'Config' . DS . 'routes.php';

		$url = '/admin/auth_test/add';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->query['url'] = ltrim($url, '/');
		$this->Auth->request->base = '';

		Router::setRequestInfo($this->Auth->request);
		$this->Auth->initialize($this->Controller);

		$this->Auth->loginAction = array(
			'admin' => true, 'controller' => 'auth_test', 'action' => 'login'
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
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		App::uses('Dispatcher', 'Routing');

		$Response = new CakeResponse();
		ob_start();
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch(new CakeRequest('/ajax_auth/add'), $Response, array('return' => 1));
		$result = ob_get_clean();

		$this->assertEquals(403, $Response->statusCode());
		$this->assertEquals("Ajax!\nthis is the test element", str_replace("\r\n", "\n", $result));
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testAjaxLoginResponseCode
 *
 * @return void
 */
	public function testAjaxLoginResponseCode() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$url = '/ajax_auth/add';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->query['url'] = ltrim($url, '/');
		$this->Auth->request->base = '';
		$this->Auth->ajaxLogin = 'test_element';

		Router::setRequestInfo($this->Auth->request);

		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$this->Controller->response->expects($this->at(0))
			->method('_sendHeader')
			->with('HTTP/1.1 403 Forbidden', null);
		$this->Auth->initialize($this->Controller);

		ob_start();
		$result = $this->Auth->startup($this->Controller);
		ob_end_clean();

		$this->assertFalse($result);
		$this->assertEquals('this is the test element', $this->Controller->response->body());
		$this->assertArrayNotHasKey('Location', $this->Controller->response->header());
		$this->assertNull($this->Controller->testUrl, 'redirect() not called');
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * test ajax login with no element
 *
 * @return void
 */
	public function testAjaxLoginResponseCodeNoElement() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$url = '/ajax_auth/add';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->query['url'] = ltrim($url, '/');
		$this->Auth->request->base = '';
		$this->Auth->ajaxLogin = false;

		Router::setRequestInfo($this->Auth->request);

		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$this->Controller->response->expects($this->at(0))
			->method('_sendHeader')
			->with('HTTP/1.1 403 Forbidden', null);
		$this->Auth->initialize($this->Controller);

		$this->Auth->startup($this->Controller);

		$this->assertArrayNotHasKey('Location', $this->Controller->response->header());
		$this->assertNull($this->Controller->testUrl, 'redirect() not called');
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testLoginActionRedirect method
 *
 * @return void
 */
	public function testLoginActionRedirect() {
		$admin = Configure::read('Routing.prefixes');
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';

		$url = '/admin/auth_test/login';
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = ltrim($url, '/');
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_login', 'plugin' => null, 'controller' => 'auth_test',
				'admin' => true,
			),
			array(
				'base' => null, 'here' => $url,
				'webroot' => '/', 'passedArgs' => array(),
			)
		));

		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('admin' => true, 'controller' => 'auth_test', 'action' => 'login');
		$this->Auth->startup($this->Controller);

		$this->assertNull($this->Controller->testUrl);

		Configure::write('Routing.prefixes', $admin);
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
 * test $settings in Controller::$components
 *
 * @return void
 */
	public function testComponentSettings() {
		$request = new CakeRequest(null, false);
		$this->Controller = new AuthTestController($request, $this->getMock('CakeResponse'));

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
 * test that logout deletes the session variables. and returns the correct URL
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
		$LogoutTriggerMockAuthenticate = $this->getMock('BaseAuthenticate', array('authenticate', 'logout'), array(), '', false);

		$this->Auth->authenticate = array('LogoutTriggerMock');
		$this->Auth->setAuthenticateObject(0, $LogoutTriggerMockAuthenticate);
		$LogoutTriggerMockAuthenticate->expects($this->once())
			->method('logout');

		$this->Auth->logout();
	}

/**
 * Test mapActions as a getter
 *
 * @return void
 */
	public function testMapActions() {
		$MapActionMockAuthorize = $this->getMock(
			'BaseAuthorize',
			array('authorize'),
			array(),
			'',
			false
		);
		$this->Auth->authorize = array('MapActionAuthorize');
		$this->Auth->setAuthorizeObject(0, $MapActionMockAuthorize);

		$actions = array('my_action' => 'create');
		$this->Auth->mapActions($actions);
		$actions = array(
			'create' => array('my_other_action'),
			'update' => array('updater')
		);
		$this->Auth->mapActions($actions);

		$actions = $this->Auth->mapActions();

		$result = $actions['my_action'];
		$expected = 'create';
		$this->assertEquals($expected, $result);

		$result = $actions['my_other_action'];
		$expected = 'create';
		$this->assertEquals($expected, $result);

		$result = $actions['updater'];
		$expected = 'update';
		$this->assertEquals($expected, $result);
	}

/**
 * test mapActions loading and delegating to authorize objects.
 *
 * @return void
 */
	public function testMapActionsDelegation() {
		$MapActionMockAuthorize = $this->getMock('BaseAuthorize', array('authorize', 'mapActions'), array(), '', false);

		$this->Auth->authorize = array('MapActionMock');
		$this->Auth->setAuthorizeObject(0, $MapActionMockAuthorize);
		$MapActionMockAuthorize->expects($this->once())
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
		$RequestLoginMockAuthenticate = $this->getMock('FormAuthenticate', array(), array(), '', false);
		$request = new CakeRequest('users/login', false);
		$user = array('username' => 'mark', 'role' => 'admin');

		$this->Auth->request = $request;
		$this->Auth->authenticate = array('RequestLoginMock');
		$this->Auth->setAuthenticateObject(0, $RequestLoginMockAuthenticate);
		$RequestLoginMockAuthenticate->expects($this->once())
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
		$this->Auth->Flash = $this->getMock('FlashComponent', array(), array(), '', false);
		$this->Auth->Flash->expects($this->once())
			->method('set')
			->with('Auth failure', array('element' => 'custom', 'params' => array(1), 'key' => 'auth-key'));

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
		$result = $this->Auth->redirectUrl($value);
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

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->Session->check('Auth.redirect'));
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

		$this->Auth->Session->write('Auth.redirect', '/waves/add');

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
		$this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');
		$this->Auth->Session->write('Auth.redirect', array('controller' => 'users', 'action' => 'login'));

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->Session->check('Auth.redirect'));
	}

/**
 * test that the returned URL doesn't contain the base URL.
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
		$this->Auth->request = $this->Controller->request = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = Router::normalize($url);

		Router::setRequestInfo($this->Auth->request);

		$this->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');

		$result = $this->Auth->redirectUrl();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Auth->Session->check('Auth.redirect'));

		Configure::write('App', $App);
		Router::reload();
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

/**
 * testStatelessAuthNoRedirect method
 *
 * @expectedException UnauthorizedException
 * @expectedExceptionCode 401
 * @return void
 */
	public function testStatelessAuthNoRedirect() {
		if (CakeSession::id()) {
			session_destroy();
			CakeSession::$id = null;
		}
		$_SESSION = null;

		AuthComponent::$sessionKey = false;
		$this->Auth->authenticate = array('Basic');
		$this->Controller->request['action'] = 'admin_add';

		$this->Auth->startup($this->Controller);
	}

/**
 * testStatelessLoginSetUserNoSessionStart method
 *
 * @return void
 */
	public function testStatelessLoginSetUserNoSessionStart() {
		$user = array(
			'id' => 1,
			'username' => 'mark'
		);

		AuthComponent::$sessionKey = false;
		$result = $this->Auth->login($user);
		$this->assertTrue($result);

		$this->assertTrue($this->Auth->loggedIn());
		$this->assertEquals($user, $this->Auth->user());

		$this->assertNull($this->Auth->Session->started());
	}

/**
 * testStatelessAuthNoSessionStart method
 *
 * @return void
 */
	public function testStatelessAuthNoSessionStart() {
		if (CakeSession::id()) {
			session_destroy();
			CakeSession::$id = null;
		}
		$_SESSION = null;

		$_SERVER['PHP_AUTH_USER'] = 'mariano';
		$_SERVER['PHP_AUTH_PW'] = 'cake';

		AuthComponent::$sessionKey = false;
		$this->Auth->authenticate = array(
			'Basic' => array('userModel' => 'AuthUser')
		);
		$this->Controller->request['action'] = 'admin_add';

		$result = $this->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->assertNull(CakeSession::id());
	}

/**
 * testStatelessAuthRedirect method
 *
 * @return void
 */
	public function testStatelessFollowedByStatefulAuth() {
		$this->Auth->authenticate = array('Basic', 'Form');
		$this->Controller->request['action'] = 'admin_add';

		$this->Auth->response->expects($this->never())->method('statusCode');
		$this->Auth->response->expects($this->never())->method('send');

		$result = $this->Auth->startup($this->Controller);
		$this->assertFalse($result);

		$this->assertEquals('/users/login', $this->Controller->testUrl);
	}
}
