<?php
/**
 * AuthComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Controller');
App::import('Component', array('Auth', 'Acl'));
App::import('Component', 'auth/form_authenticate');
App::import('Model', 'DbAcl');
App::import('Core', 'Xml');

/**
* TestAuthComponent class
*
* @package       cake
* @package       cake.tests.cases.libs.controller.components
*/
class TestAuthComponent extends AuthComponent {

/**
 * testStop property
 *
 * @var bool false
 * @access public
 */
	public $testStop = false;

/**
 * Sets default login state
 *
 * @var bool true
 * @access protected
 */
	protected $_loggedIn = true;

/**
 * stop method
 *
 * @access public
 * @return void
 */
	function _stop($status = 0) {
		$this->testStop = true;
	}

}

/**
* AuthUser class
*
* @package       cake
* @package       cake.tests.cases.libs.controller.components
*/
class AuthUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	public $name = 'AuthUser';

/**
 * useDbConfig property
 *
 * @var string 'test'
 * @access public
 */
	public $useDbConfig = 'test';

/**
 * parentNode method
 *
 * @access public
 * @return void
 */
	function parentNode() {
		return true;
	}

/**
 * bindNode method
 *
 * @param mixed $object
 * @access public
 * @return void
 */
	function bindNode($object) {
		return 'Roles/Admin';
	}

/**
 * isAuthorized method
 *
 * @param mixed $user
 * @param mixed $controller
 * @param mixed $action
 * @access public
 * @return void
 */
	function isAuthorized($user, $controller = null, $action = null) {
		if (!empty($user)) {
			return true;
		}
		return false;
	}
}

/**
 * AuthUserCustomField class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class AuthUserCustomField extends AuthUser {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	public $name = 'AuthUserCustomField';
}

/**
* UuidUser class
*
* @package       cake
* @package       cake.tests.cases.libs.controller.components
*/
class UuidUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	public $name = 'UuidUser';

/**
 * useDbConfig property
 *
 * @var string 'test'
 * @access public
 */
	public $useDbConfig = 'test';

/**
 * useTable property
 *
 * @var string 'uuid'
 * @access public
 */
	public $useTable = 'uuids';

/**
 * parentNode method
 *
 * @access public
 * @return void
 */
	function parentNode() {
		return true;
	}

/**
 * bindNode method
 *
 * @param mixed $object
 * @access public
 * @return void
 */
	function bindNode($object) {
		return 'Roles/Admin';
	}

/**
 * isAuthorized method
 *
 * @param mixed $user
 * @param mixed $controller
 * @param mixed $action
 * @access public
 * @return void
 */
	function isAuthorized($user, $controller = null, $action = null) {
		if (!empty($user)) {
			return true;
		}
		return false;
	}
}

/**
* AuthTestController class
*
* @package       cake
* @package       cake.tests.cases.libs.controller.components
*/
class AuthTestController extends Controller {

/**
 * name property
 *
 * @var string 'AuthTest'
 * @access public
 */
	public $name = 'AuthTest';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array('AuthUser');

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Session', 'Auth', 'Acl');

/**
 * testUrl property
 *
 * @var mixed null
 * @access public
 */
	public $testUrl = null;

/**
 * construct method
 *
 * @access private
 * @return void
 */
	function __construct($request) {
		$request->addParams(Router::parse('/auth_test'));
		$request->here = '/auth_test';
		$request->webroot = '/';
		Router::setRequestInfo($request);
		parent::__construct($request);
	}

/**
 * beforeFilter method
 *
 * @access public
 * @return void
 */
	function beforeFilter() {
		$this->Auth->userModel = 'AuthUser';
	}

/**
 * login method
 *
 * @access public
 * @return void
 */
	function login() {
	}

/**
 * admin_login method
 *
 * @access public
 * @return void
 */
	function admin_login() {
	}

/**
 * logout method
 *
 * @access public
 * @return void
 */
	function logout() {
		// $this->redirect($this->Auth->logout());
	}

/**
 * add method
 *
 * @access public
 * @return void
 */
	function add() {
		echo "add";
	}

/**
 * add method
 *
 * @access public
 * @return void
 */
	function camelCase() {
		echo "camelCase";
	}

/**
 * redirect method
 *
 * @param mixed $url
 * @param mixed $status
 * @param mixed $exit
 * @access public
 * @return void
 */
	function redirect($url, $status = null, $exit = true) {
		$this->testUrl = Router::url($url);
		return false;
	}

/**
 * isAuthorized method
 *
 * @access public
 * @return void
 */
	function isAuthorized() {
		if (isset($this->request['testControllerAuth'])) {
			return false;
		}
		return true;
	}

/**
 * Mock delete method
 *
 * @param mixed $url
 * @param mixed $status
 * @param mixed $exit
 * @access public
 * @return void
 */
	function delete($id = null) {
		if ($this->TestAuth->testStop !== true && $id !== null) {
			echo 'Deleted Record: ' . var_export($id, true);
		}
	}
}

/**
 * AjaxAuthController class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class AjaxAuthController extends Controller {

/**
 * name property
 *
 * @var string 'AjaxAuth'
 * @access public
 */
	public $name = 'AjaxAuth';

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Session', 'TestAuth');

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array();

/**
 * testUrl property
 *
 * @var mixed null
 * @access public
 */
	public $testUrl = null;

/**
 * beforeFilter method
 *
 * @access public
 * @return void
 */
	function beforeFilter() {
		$this->TestAuth->ajaxLogin = 'test_element';
		$this->TestAuth->userModel = 'AuthUser';
		$this->TestAuth->RequestHandler->ajaxLayout = 'ajax2';
	}

/**
 * add method
 *
 * @access public
 * @return void
 */
	function add() {
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
 * @access public
 * @return void
 */
	function redirect($url, $status = null, $exit = true) {
		$this->testUrl = Router::url($url);
		return false;
	}
}

/**
* AuthTest class
*
* @package       cake
* @package       cake.tests.cases.libs.controller.components
*/
class AuthTest extends CakeTestCase {

/**
 * name property
 *
 * @var string 'Auth'
 * @access public
 */
	public $name = 'Auth';

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.uuid', 'core.auth_user', 'core.auth_user_custom_field', 'core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

/**
 * initialized property
 *
 * @var bool false
 * @access public
 */
	public $initialized = false;

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->_server = $_SERVER;
		$this->_env = $_ENV;

		Configure::write('Security.salt', 'YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
		Configure::write('Security.cipherSeed', 770011223369876);

		Configure::write('Acl.database', 'test');
		Configure::write('Acl.classname', 'DbAcl');

		$request = new CakeRequest(null, false);

		$this->Controller = new AuthTestController($request);
		$this->Controller->Components->init($this->Controller);
		$this->Controller->Components->trigger(
			'initialize', array(&$this->Controller), array('triggerDisabled' => true)
		);
		$this->Controller->beforeFilter();

		$view = new View($this->Controller);
		ClassRegistry::addObject('view', $view);

		$this->Controller->Session->delete('Auth');
		$this->Controller->Session->delete('Message.auth');

		$this->initialized = true;
		Router::reload();

		ClassRegistry::init('AuthUser')->updateAll(array('password' => '"' . Security::hash('cake', null, true) . '"'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		$_SERVER = $this->_server;
		$_ENV = $this->_env;

		$this->Controller->Session->delete('Auth');
		$this->Controller->Session->delete('Message.auth');
		unset($this->Controller, $this->AuthUser);
	}

/**
 * testNoAuth method
 *
 * @access public
 * @return void
 */
	function testNoAuth() {
		$this->assertFalse($this->Controller->Auth->isAuthorized());
	}

/**
 * testIsErrorOrTests
 *
 * @access public
 * @return void
 */
	function testIsErrorOrTests() {
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
 * @access public
 * @return void
 */
	function testLogin() {
		$this->getMock('FormAuthenticate', array(), array(), 'AuthLoginFormAuthenticate', false);
		$this->Controller->Auth->authenticate = array(
			'AuthLoginForm' => array(
				'userModel' => 'AuthUser'
			)
		);
		$mocks = $this->Controller->Auth->constructAuthenticate();
		$this->mockObjects[] = $mocks[0];

		$this->Controller->Auth->request->data = array(
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
			->with($this->Controller->Auth->request)
			->will($this->returnValue($user));

		$result = $this->Controller->Auth->login();
		$this->assertTrue($result);

		$this->assertTrue($this->Controller->Auth->loggedIn());
		$this->assertEquals($user, $this->Controller->Auth->user());
	}

/**
 * test that being redirected to the login page, with no post data does
 * not set the session value.  Saving the session value in this circumstance
 * can cause the user to be redirected to an already public page.
 *
 * @return void
 */
	function testLoginActionNotSettingAuthRedirect() {
		$_SERVER['HTTP_REFERER'] = '/pages/display/about';

		$this->Controller->data = array();
		$this->Controller->request->addParams(Router::parse('auth_test/login'));
		$this->Controller->request->query['url'] = 'auth_test/login';
		$this->Controller->Session->delete('Auth');

		$this->Controller->Auth->loginRedirect = '/users/dashboard';
		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$redirect = $this->Controller->Session->read('Auth.redirect');
		$this->assertNull($redirect);
	}

/**
 * testAuthorizeFalse method
 *
 * @access public
 * @return void
 */
	function testAuthorizeFalse() {
		$this->AuthUser = new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth.User', $user['AuthUser']);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = false;
		$this->Controller->request->addParams(Router::parse('auth_test/add'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->Session->delete('Auth');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertFalse($result);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));

		$this->Controller->request->addParams(Router::parse('auth_test/camelCase'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertFalse($result);
	}


/**
 * testAuthorizeModel method
 *
 * @access public
 * @return void
 */
	function testAuthorizeModel() {
		$this->markTestSkipped('This is not implemented');
		
		$this->AuthUser = new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);

		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'add';
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->authorize = array('model'=>'AuthUser');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->Session->delete('Auth');
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$result = $this->Controller->Auth->isAuthorized();
		$this->assertFalse($result);
	}

/**
 * @expectedException CakeException
 * @return void
 */
	function testIsAuthorizedMissingFile() {
		$this->Controller->Auth->authorize = 'Missing';
		$this->Controller->Auth->isAuthorized(array('User' => array('id' => 1)));
	}

/**
 * test that isAuthroized calls methods correctly
 *
 * @return void
 */
	function testIsAuthorizedDelegation() {
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockOneAuthorize', false);
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockTwoAuthorize', false);
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'AuthMockThreeAuthorize', false);

		$this->Controller->Auth->authorize = array(
			'AuthMockOne',
			'AuthMockTwo',
			'AuthMockThree'
		);
		$mocks = $this->Controller->Auth->constructAuthorize();
		$request = $this->Controller->request;

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

		$this->assertTrue($this->Controller->Auth->isAuthorized(array('User'), $request));
	}

/**
 * test that loadAuthorize resets the loaded objects each time.
 *
 * @return void
 */
	function testLoadAuthorizeResets() {
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
	function testLoadAuthenticateNoFile() {
		$this->Controller->Auth->authenticate = 'Missing';
		$this->Controller->Auth->identify($this->Controller->request, $this->Controller->response);
	}

/**
 * test the * key with authenticate
 *
 * @return void
 */
	function testAllConfigWithAuthorize() {
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
	function testLoadAuthenticateResets() {
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
	function testAllConfigWithAuthenticate() {
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
 * @access public
 * @return void
 */
	function testAllowDenyAll() {
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->allow('*');
		$this->Controller->Auth->deny('add', 'camelCase');

		$this->Controller->request['action'] = 'delete';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'add';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->allow('*');
		$this->Controller->Auth->deny(array('add', 'camelCase'));

		$this->Controller->request['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * test that deny() converts camel case inputs to lowercase.
 *
 * @return void
 */
	function testDenyWithCamelCaseMethods() {
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->allow('*');
		$this->Controller->Auth->deny('add', 'camelCase');

		$url = '/auth_test/camelCase';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);

		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * test that allow() and allowedActions work with camelCase method names.
 *
 * @return void
 */
	function testAllowedActionsWithCamelCaseMethods() {
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

	function testAllowedActionsSetWithAllowMethod() {
		$url = '/auth_test/action_name';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->allow('action_name', 'anotherAction');
		$this->assertEqual($this->Controller->Auth->allowedActions, array('action_name', 'anotherAction'));
	}

/**
 * testLoginRedirect method
 *
 * @access public
 * @return void
 */
	function testLoginRedirect() {
		$_SERVER['HTTP_REFERER'] = false;
		$_ENV['HTTP_REFERER'] = false;
		putenv('HTTP_REFERER=');

		$this->Controller->Session->write('Auth', array(
			'AuthUser' => array('id' => '1', 'username' => 'nate')
		));

		$this->Controller->request->addParams(Router::parse('users/login'));
		$this->Controller->request->query['url'] = 'users/login';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = array(
			'controller' => 'pages', 'action' => 'display', 'welcome'
		);
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize($this->Controller->Auth->loginRedirect);
		$this->assertEqual($expected, $this->Controller->Auth->redirect());

		$this->Controller->Session->delete('Auth');

		//empty referer no session
		$_SERVER['HTTP_REFERER'] = false;
		$_ENV['HTTP_REFERER'] = false;
		putenv('HTTP_REFERER=');
		$url = '/posts/view/1';

		$this->Controller->Session->write('Auth', array(
			'AuthUser' => array('id' => '1', 'username' => 'nate'))
		);
		$this->Controller->testUrl = null;
		$this->Controller->request->addParams(Router::parse($url));
		array_push($this->Controller->methods, 'view', 'edit', 'index');

		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->authorize = 'controller';

		$this->Controller->Auth->loginAction = array(
			'controller' => 'AuthTest', 'action' => 'login'
		);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/AuthTest/login');
		$this->assertEqual($expected, $this->Controller->testUrl);

		$this->Controller->Session->delete('Auth');
		$_SERVER['HTTP_REFERER'] = $_ENV['HTTP_REFERER'] = Router::url('/admin', true);
		$this->Controller->Session->write('Auth', array(
			'AuthUser' => array('id'=>'1', 'username' => 'nate')
		));
		$this->Controller->request->params['action'] = 'login';
		$this->Controller->request->query['url'] = 'auth_test/login';
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = false;
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/admin');
		$this->assertEqual($expected, $this->Controller->Auth->redirect());

		//Ticket #4750
		//named params
		$this->Controller->Session->delete('Auth');
		$url = '/posts/index/year:2008/month:feb';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/index/year:2008/month:feb');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

		//passed args
		$this->Controller->Session->delete('Auth');
		$url = '/posts/view/1';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/view/1');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

        // QueryString parameters
		$_back = $_GET;
		$_GET = array(
			'url' => '/posts/index/29',
			'print' => 'true',
			'refer' => 'menu'
		);
		$this->Controller->Session->delete('Auth');
		$url = '/posts/index/29';
		$this->Controller->request = new CakeRequest($url);
		$this->Controller->request->addParams(Router::parse($url));

		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/index/29?print=true&refer=menu');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

		$_GET = array(
			'url' => '/posts/index/29',
			'print' => 'true',
			'refer' => 'menu'
		);
		$this->Controller->Session->delete('Auth');
		$url = '/posts/index/29';
		$this->Controller->request = new CakeRequest($url);
		$this->Controller->request->addParams(Router::parse($url));

		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/index/29?print=true&refer=menu');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));
		$_GET = $_back;

		//external authed action
		$_SERVER['HTTP_REFERER'] = 'http://webmail.example.com/view/message';
		$this->Controller->Session->delete('Auth');
		$url = '/posts/edit/1';
		$this->Controller->request = new CakeRequest($url);
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query = array('url' => Router::normalize($url));
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/posts/edit/1');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

		//external direct login link
		$_SERVER['HTTP_REFERER'] = 'http://webmail.example.com/view/message';
		$this->Controller->Session->delete('Auth');
		$url = '/AuthTest/login';
		$this->Controller->request = new CakeRequest($url);
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

		$this->Controller->Session->delete('Auth');
	}

/**
 * Ensure that no redirect is performed when a 404 is reached
 * And the user doesn't have a session.
 *
 * @return void
 */
	function testNoRedirectOn404() {
		$this->Controller->Session->delete('Auth');
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->request->addParams(Router::parse('auth_test/something_totally_wrong'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result, 'Auth redirected a missing action %s');
	}

/**
 * testAdminRoute method
 *
 * @access public
 * @return void
 */
	function testAdminRoute() {
		$prefixes = Configure::read('Routing.prefixes');
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		$url = '/admin/auth_test/add';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = ltrim($url, '/');
		$this->Controller->request->base = '';
		Router::setRequestInfo($this->Controller->request);
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = array(
			'admin' => true, 'controller' => 'auth_test', 'action' => 'login'
		);
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$this->assertEqual($this->Controller->testUrl, '/admin/auth_test/login');

		Configure::write('Routing.prefixes', $prefixes);
	}

/**
 * testAjaxLogin method
 *
 * @access public
 * @return void
 */
	function testAjaxLogin() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));
		$_SERVER['HTTP_X_REQUESTED_WITH'] = "XMLHttpRequest";

		App::import('Core', 'Dispatcher');

		ob_start();
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch(new CakeRequest('/ajax_auth/add'), array('return' => 1));
		$result = ob_get_clean();

		$this->assertEqual("Ajax!\nthis is the test element", str_replace("\r\n", "\n", $result));
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testLoginActionRedirect method
 *
 * @access public
 * @return void
 */
	function testLoginActionRedirect() {
		$admin = Configure::read('Routing.prefixes');
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		$url = '/admin/auth_test/login';
		$this->Controller->request->addParams(Router::parse($url));
		$this->Controller->request->query['url'] = ltrim($url, '/');
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_login', 'plugin' => null, 'controller' => 'auth_test',
				'admin' => true, 'url' => array('url' => $this->Controller->request->query['url']),
			),
			array(
				'base' => null, 'here' => $url,
				'webroot' => '/', 'passedArgs' => array(),
			)
		));

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = array('admin' => true, 'controller' => 'auth_test', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);

		$this->assertNull($this->Controller->testUrl);

		Configure::write('Routing.prefixes', $admin);
	}

/**
 * Tests that shutdown destroys the redirect session var
 *
 * @access public
 * @return void
 */
	function testShutDown() {
		$this->Controller->Auth->initialize($this->Controller, array('_loggedIn' => true));
		$this->Controller->Session->write('Auth.redirect', 'foo');
		$this->Controller->Auth->loggedIn(true);

		$this->Controller->Auth->shutdown($this->Controller);
		$this->assertNull($this->Controller->Session->read('Auth.redirect'));
	}

/**
 * test $settings in Controller::$components
 *
 * @access public
 * @return void
 */
	function testComponentSettings() {
		$request = new CakeRequest(null, false);
		$this->Controller = new AuthTestController($request);

		$this->Controller->components = array(
			'Auth' => array(
				'loginAction' => array('controller' => 'people', 'action' => 'login'),
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
		$this->assertEqual($expected['loginAction'], $this->Controller->Auth->loginAction);
		$this->assertEqual($expected['logoutRedirect'], $this->Controller->Auth->logoutRedirect);
	}

/**
 * test that logout deletes the session variables. and returns the correct url
 *
 * @return void
 */
	function testLogout() {
		$this->Controller->Session->write('Auth.User.id', '1');
		$this->Controller->Session->write('Auth.redirect', '/users/login');
		$this->Controller->Auth->logoutRedirect = '/';
		$result = $this->Controller->Auth->logout();

		$this->assertEqual($result, '/');
		$this->assertNull($this->Controller->Session->read('Auth.AuthUser'));
		$this->assertNull($this->Controller->Session->read('Auth.redirect'));
	}

/**
 * test mapActions loading and delegating to authorize objects.
 *
 * @return void
 */
	function testMapActionsDelegation() {
		$this->getMock('BaseAuthorize', array('authorize'), array(), 'MapActionMockAuthorize', false);
		$this->Controller->Auth->authorize = array('MapActionMock');
		$mock = $this->Controller->Auth->constructAuthorize();
		$mock[0]->expects($this->once())
			->method('mapActions')
			->with(array('create' => array('my_action')));

		$this->Controller->Auth->mapActions(array('create' => array('my_action')));
	}

/**
 * test logging in with a request.
 *
 * @return void
 */
	function testLoginWithRequestData() {
		$this->getMock('FormAuthenticate', array(), array(), 'RequestLoginMockAuthenticate', false);
		$request = new CakeRequest('users/login', false);
		$user = array('username' => 'mark', 'role' => 'admin');

		$this->Controller->Auth->request = $request;
		$this->Controller->Auth->authenticate = array('RequestLoginMock');
		$mock = $this->Controller->Auth->constructAuthenticate();
		$mock[0]->expects($this->once())
			->method('authenticate')
			->with($request)
			->will($this->returnValue($user));

		$this->assertTrue($this->Controller->Auth->login());
		$this->assertEquals($user['username'], $this->Controller->Auth->user('username'));
	}

/**
 * test login() with user data
 *
 * @return void
 */
	function testLoginWithUserData() {
		$this->assertFalse($this->Controller->Auth->loggedIn());

		$user = array(
			'username' => 'mariano',
			'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertTrue($this->Controller->Auth->login($user));
		$this->assertTrue($this->Controller->Auth->loggedIn());
		$this->assertEquals($user['username'], $this->Controller->Auth->user('username'));
	}

/**
 * test flash settings.
 *
 * @return void
 */
	function testFlashSettings() {
		$this->Controller->Auth->Session = $this->getMock('SessionComponent', array(), array(), '', false);
		$this->Controller->Auth->Session->expects($this->once())
			->method('setFlash')
			->with('Auth failure', 'custom', array(1), 'auth-key');
		
		$this->Controller->Auth->flash = array(
			'element' => 'custom',
			'params' => array(1),
			'key' => 'auth-key'
		);
		$this->Controller->Auth->flash('Auth failure');
	}

/**
 * test the various states of Auth::redirect()
 *
 * @return void
 */
	function testRedirectSet() {
		$value = array('controller' => 'users', 'action' => 'home');
		$result = $this->Controller->Auth->redirect($value);
		$this->assertEquals('/users/home', $result);
		$this->assertEquals($value, $this->Controller->Session->read('Auth.redirect'));
	}

/**
 * test redirect using Auth.redirect from the session.
 *
 * @return void
 */
	function testRedirectSessionRead() {
		$this->Controller->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Controller->Session->write('Auth.redirect', '/users/home');

		$result = $this->Controller->Auth->redirect();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Controller->Session->check('Auth.redirect'));
	}

/**
 * test that redirect does not return loginAction if that is what's stored in Auth.redirect.
 * instead loginRedirect should be used.
 *
 * @return void
 */
	function testRedirectSessionReadEqualToLoginAction() {
		$this->Controller->Auth->loginAction = array('controller' => 'users', 'action' => 'login');
		$this->Controller->Auth->loginRedirect = array('controller' => 'users', 'action' => 'home');
		$this->Controller->Session->write('Auth.redirect', array('controller' => 'users', 'action' => 'login'));

		$result = $this->Controller->Auth->redirect();
		$this->assertEquals('/users/home', $result);
		$this->assertFalse($this->Controller->Session->check('Auth.redirect'));
	}
}
