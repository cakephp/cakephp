<?php
/**
 * AuthComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('AclComponent', 'Controller/Component');
App::uses('FormAuthenticate', 'Controller/Component/Auth');

/**
* TestAuthComponent class
*
* @package       Cake.Test.Case.Controller.Component
* @package       Cake.Test.Case.Controller.Component
*/
class TestAuthComponent extends AuthComponent {

/**
 * testStop property
 *
 * @var bool false
 */
	public $testStop = false;

/**
 * stop method
 *
 * @return void
 */
	function _stop($status = 0) {
		$this->testStop = true;
	}

	public static function clearUser() {
		self::$_user = array();
	}

}

/**
* AuthUser class
*
* @package       Cake.Test.Case.Controller.Component
* @package       Cake.Test.Case.Controller.Component
*/
class AuthUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 */
	public $name = 'AuthUser';

/**
 * useDbConfig property
 *
 * @var string 'test'
 */
	public $useDbConfig = 'test';

}

/**
* AuthTestController class
*
* @package       Cake.Test.Case.Controller.Component
* @package       Cake.Test.Case.Controller.Component
*/
class AuthTestController extends Controller {

/**
 * name property
 *
 * @var string 'AuthTest'
 */
	public $name = 'AuthTest';

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
	public $components = array('Session', 'Auth');

/**
 * testUrl property
 *
 * @var mixed null
 */
	public $testUrl = null;

/**
 * construct method
 *
 * @return void
 */
	function __construct($request, $response) {
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
 * @param mixed $url
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
 * name property
 *
 * @var string 'AjaxAuth'
 */
	public $name = 'AjaxAuth';

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
 * @var mixed null
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
 * @param mixed $url
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
* AuthComponentTest class
*
* @package       Cake.Test.Case.Controller.Component
* @package       Cake.Test.Case.Controller.Component
*/
class AuthComponentTest extends CakeTestCase {

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
		$this->_server = $_SERVER;
		$this->_env = $_ENV;

		Configure::write('Security.salt', 'YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
		Configure::write('Security.cipherSeed', 770011223369876);

		$request = new CakeRequest(null, false);

		$this->Controller = new AuthTestController($request, $this->getMock('CakeResponse'));

		$collection = new ComponentCollection();
		$collection->init($this->Controller);
		$this->Auth = new TestAuthComponent($collection);
		$this->Auth->request = $request;
		$this->Auth->response = $this->getMock('CakeResponse');

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
		$_SERVER = $this->_server;
		$_ENV = $this->_env;

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
		$this->getMock('FormAuthenticate', array(), array(), 'AuthLoginFormAuthenticate', false);
		$this->Auth->authenticate = array(
			'AuthLoginForm' => array(
				'userModel' => 'AuthUser'
			)
		);
		$this->Auth->Session = $this->getMock('SessionComponent', array('renew'), array(), '', false);

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
 * @expectedException CakeException
 * @return void
 */
	public function testIsAuthorizedMissingFile() {
		$this->Controller->Auth->authorize = 'Missing';
		$this->Controller->Auth->isAuthorized(array('User' => array('id' => 1)));
	}

/**
 * test that isAuthroized calls methods correctly
 *
 * @return void
 */
	public function testIsAuthorizedDelegation() {
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockOneAuthorize', false);
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockTwoAuthorize', false);
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockThreeAuthorize', false);

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
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockFourAuthorize', false);
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
		$this->assertEquals($result->settings['actionPath'], 'controllers/');
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
		$this->assertEquals($result->settings['userModel'], 'AuthUser');
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

		$this->Controller->Auth->allow('*');
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
	}

/**
 * test that deny() converts camel case inputs to lowercase.
 *
 * @return void
 */
	public function testDenyWithCamelCaseMethods() {
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->allow('*');
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
		$this->Controller->Auth->allow('*');
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
		$this->assertEquals($this->Controller->Auth->allowedActions, array('action_name', 'anotherAction'));
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

		//Ticket #4750
		//named params
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

		//passed args
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

		//external authed action
		$_SERVER['HTTP_REFERER'] = 'http://webmail.example.com/view/message';
		$this->Auth->Session->delete('Auth');
		$url = '/posts/edit/1';
		$this->Auth->request = $this->Controller->request = new CakeRequest($url);
		$this->Auth->request->addParams(Router::parse($url));
		$this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
		$this->Auth->initialize($this->Controller);
		$this->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Auth->startup($this->Controller);
		$expected = Router::normalize('/posts/edit/1');
		$this->assertEquals($expected, $this->Auth->Session->read('Auth.redirect'));

		//external direct login link
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
 * test that no redirects or authoization tests occur on the loginAction
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
		$this->assertEquals($this->Controller->testUrl, '/admin/auth_test/login');

		Configure::write('Routing.prefixes', $pref);
	}

/**
 * testAjaxLogin method
 *
 * @return void
 */
	public function testAjaxLogin() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));
		$_SERVER['HTTP_X_REQUESTED_WITH'] = "XMLHttpRequest";

		App::uses('Dispatcher', 'Routing');

		ob_start();
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch(new CakeRequest('/ajax_auth/add'), new CakeResponse(), array('return' => 1));
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
 * test that logout deletes the session variables. and returns the correct url
 *
 * @return void
 */
	public function testLogout() {
		$this->Auth->Session->write('Auth.User.id', '1');
		$this->Auth->Session->write('Auth.redirect', '/users/login');
		$this->Auth->logoutRedirect = '/';
		$result = $this->Auth->logout();

		$this->assertEquals($result, '/');
		$this->assertNull($this->Auth->Session->read('Auth.AuthUser'));
		$this->assertNull($this->Auth->Session->read('Auth.redirect'));
	}

/**
 * Logout should trigger a logout method on authentication objects.
 *
 * @return void
 */
	public function testLogoutTrigger() {
		$this->getMock('BaseAuthenticate', array('authenticate', 'logout'), array(), 'LogoutTriggerMockAuthenticate', false);

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
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'MapActionMockAuthorize', false);
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
		$this->getMock('FormAuthenticate', array(), array(), 'RequestLoginMockAuthenticate', false);
		$request = new CakeRequest('users/login', false);
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
		$this->Auth->Session = $this->getMock('SessionComponent', array(), array(), '', false);
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
}
