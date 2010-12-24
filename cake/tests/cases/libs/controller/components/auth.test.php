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
		$this->AuthUser = new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->AuthUser->save($user, false);

		$authUser = $this->AuthUser->find();

		$this->Controller->request->data['AuthUser'] = array(
			'username' => $authUser['AuthUser']['username'], 'password' => 'cake'
		);

		$this->Controller->request->addParams(Router::parse('auth_test/login'));
		$this->Controller->request->query['url'] = 'auth_test/login';

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$expected = array('AuthUser' => array(
			'id' => 1, 'username' => 'mariano', 'created' => '2007-03-17 01:16:23', 'updated' => date('Y-m-d H:i:s')
		));
		$this->assertEqual($user, $expected);
		$this->Controller->Session->delete('Auth');

		$this->Controller->request->data['AuthUser'] = array(
			'username' => 'blah', 'password' => ''
		);

		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertNull($user);
		$this->Controller->Session->delete('Auth');

		$this->Controller->request->data['AuthUser'] = array(
			'username' => 'now() or 1=1 --', 'password' => ''
		);

		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertNull($user);
		$this->Controller->Session->delete('Auth');

		$this->Controller->request->data['AuthUser'] = array(
			'username' => 'now() or 1=1 #something', 'password' => ''
		);

		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertNull($user);
		$this->Controller->Session->delete('Auth');

		$this->Controller->Auth->userModel = 'UuidUser';
		$this->Controller->Auth->login('47c36f9c-bc00-4d17-9626-4e183ca6822b');

		$user = $this->Controller->Auth->user();
		$expected = array('UuidUser' => array(
			'id' => '47c36f9c-bc00-4d17-9626-4e183ca6822b', 'title' => 'Unique record 1', 'count' => 2, 'created' => '2008-03-13 01:16:23', 'updated' => '2008-03-13 01:18:31'
		));
		$this->assertEqual($user, $expected);
		$this->Controller->Session->delete('Auth');
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
		$this->Controller->Session->write('Auth', $user);
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
 * testAuthorizeController method
 *
 * @access public
 * @return void
 */
	function testAuthorizeController() {
		$this->AuthUser = new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'controller';
		$this->Controller->request->addParams(Router::parse('auth_test/add'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->request['testControllerAuth'] = 1;
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$this->assertFalse($result);

		$this->Controller->Session->delete('Auth');
	}

/**
 * testAuthorizeModel method
 *
 * @access public
 * @return void
 */
	function testAuthorizeModel() {
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
 * testAuthorizeCrud method
 *
 * @access public
 * @return void
 */
	function testAuthorizeCrud() {
		$this->AuthUser = new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);

		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'add';

		$this->Controller->Acl->name = 'DbAclTest';

		$this->Controller->Acl->Aro->id = null;
		$this->Controller->Acl->Aro->create(array('alias' => 'Roles'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertFalse(empty($result));

		$parent = $this->Controller->Acl->Aro->id;

		$this->Controller->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Admin'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertFalse(empty($result));

		$parent = $this->Controller->Acl->Aro->id;

		$this->Controller->Acl->Aro->create(array(
			'model' => 'AuthUser', 'parent_id' => $parent, 'foreign_key' => 1, 'alias'=> 'mariano'
		));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertFalse(empty($result));

		$this->Controller->Acl->Aco->create(array('alias' => 'Root'));
		$result = $this->Controller->Acl->Aco->save();
		$this->assertFalse(empty($result));

		$parent = $this->Controller->Acl->Aco->id;

		$this->Controller->Acl->Aco->create(array('parent_id' => $parent, 'alias' => 'AuthTest'));
		$result = $this->Controller->Acl->Aco->save();
		$this->assertFalse(empty($result));

		$this->Controller->Acl->allow('Roles/Admin', 'Root');
		$this->Controller->Acl->allow('Roles/Admin', 'Root/AuthTest');

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'crud';
		$this->Controller->Auth->actionPath = 'Root/';

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Auth->isAuthorized());

		$this->Controller->Session->delete('Auth');
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
	}

/**
 * test authorize = 'actions' setting.
 *
 * @return void
 */
	function testAuthorizeActions() {
		$this->AuthUser = new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'add';

		$this->Controller->Acl = $this->getMock('AclComponent', array(), array(), '', false);
		$this->Controller->Acl->expects($this->atLeastOnce())->method('check')->will($this->returnValue(true));

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'actions';
		$this->Controller->Auth->actionPath = 'Root/';

		$this->Controller->Acl->expects($this->at(0))->method('check')->with($user, 'Root/AuthTest/add');

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Auth->isAuthorized());
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
 * test the action() method
 *
 * @return void
 */
	function testActionMethod() {
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'add';

		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->actionPath = 'ROOT/';

		$result = $this->Controller->Auth->action();
		$this->assertEqual($result, 'ROOT/AuthTest/add');

		$result = $this->Controller->Auth->action(':controller');
		$this->assertEqual($result, 'ROOT/AuthTest');

		$result = $this->Controller->Auth->action(':controller');
		$this->assertEqual($result, 'ROOT/AuthTest');

		$this->Controller->request['plugin'] = 'test_plugin';
		$this->Controller->request['controller'] = 'auth_test';
		$this->Controller->request['action'] = 'add';
		$this->Controller->Auth->initialize($this->Controller);
		$result = $this->Controller->Auth->action();
		$this->assertEqual($result, 'ROOT/TestPlugin/AuthTest/add');
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

		$this->Controller->request->query['url'] = 'admin/';
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = null;
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('admin/');
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
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
		$this->Controller->request['testControllerAuth'] = true;

		$this->Controller->Auth->loginAction = array(
			'controller' => 'AuthTest', 'action' => 'login'
		);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/');
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
			'refer' => 'menu',
			'ext' => 'html'
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
 * testEmptyUsernameOrPassword method
 *
 * @access public
 * @return void
 */
	function testEmptyUsernameOrPassword() {
		$this->AuthUser = new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->AuthUser->save($user, false);

		$authUser = $this->AuthUser->find();

		$this->Controller->request->data['AuthUser'] = array(
			'username' => '', 'password' => ''
		);

		$this->Controller->request->addParams(Router::parse('auth_test/login'));
		$this->Controller->request->query['url'] = 'auth_test/login';
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$this->assertEqual($user, false);
		$this->Controller->Session->delete('Auth');
	}

/**
 * testInjection method
 *
 * @access public
 * @return void
 */
	function testInjection() {
		$this->AuthUser = new AuthUser();
		$this->AuthUser->id = 2;
		$this->AuthUser->saveField('password', Security::hash(Configure::read('Security.salt') . 'cake'));

		$this->Controller->request->data['AuthUser'] = array(
			'username' => 'nate', 'password' => 'cake'
		);

		$this->Controller->request->addParams(Router::parse('auth_test/login'));
		$this->Controller->request->query['url'] = 'auth_test/login';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_array($this->Controller->Auth->user()));

		$this->Controller->Session->delete($this->Controller->Auth->sessionKey);

		$this->Controller->request->data = array(
			'AuthUser' => array(
				'username' => 'nate',
				'password' => 'cake1'
			)
		);
		$this->Controller->request->query['url'] = 'auth_test/login';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));

		$this->Controller->Session->delete($this->Controller->Auth->sessionKey);

		$this->Controller->request->data = array(
			'AuthUser' => array(
				'username' => '> n',
				'password' => 'cake'
			)
		);
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));

		unset($this->Controller->request->data['AuthUser']['password']);
		$this->Controller->request->data['AuthUser']['username'] = "1'1";
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));

		unset($this->Controller->request->data['AuthUser']['username']);
		$this->Controller->request->data['AuthUser']['password'] = "1'1";
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));
	}

/**
 * test Hashing of passwords
 *
 * @return void
 */
	function testHashPasswords() {
		$this->Controller->Auth->userModel = 'AuthUser';

		$data['AuthUser']['password'] = 'superSecret';
		$data['AuthUser']['username'] = 'superman@dailyplanet.com';
		$return = $this->Controller->Auth->hashPasswords($data);
		$expected = $data;
		$expected['AuthUser']['password'] = Security::hash($expected['AuthUser']['password'], null, true);
		$this->assertEqual($return, $expected);

		$data['Wrong']['password'] = 'superSecret';
		$data['Wrong']['username'] = 'superman@dailyplanet.com';
		$data['AuthUser']['password'] = 'IcantTellYou';
		$return = $this->Controller->Auth->hashPasswords($data);
		$expected = $data;
		$expected['AuthUser']['password'] = Security::hash($expected['AuthUser']['password'], null, true);
		$this->assertEqual($return, $expected);

		$xml = array(
			'User' => array(
				'username' => 'batman@batcave.com',
				'password' => 'bruceWayne',
			)
		);
		$data = new Xml($xml);
		$return = $this->Controller->Auth->hashPasswords($data);
		$expected = $data;
		$this->assertEqual($return, $expected);
	}

/**
 * testCustomRoute method
 *
 * @access public
 * @return void
 */
	function testCustomRoute() {
		Router::reload();
		Router::connect(
			'/:lang/:controller/:action/*',
			array('lang' => null),
			array('lang' => '[a-z]{2,3}')
		);

		$url = '/en/users/login';
		$this->Controller->request->addParams(Router::parse($url));
		Router::setRequestInfo($this->Controller->request);

		$this->AuthUser = new AuthUser();
		$user = array(
			'id' => 1, 'username' => 'felix',
			'password' => Security::hash(Configure::read('Security.salt') . 'cake'
		));
		$user = $this->AuthUser->save($user, false);

		$this->Controller->request->data['AuthUser'] = array('username' => 'felix', 'password' => 'cake');
		$this->Controller->request->query['url'] = substr($url, 1);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('lang' => 'en', 'controller' => 'users', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$this->assertTrue(!!$user);

		$this->Controller->Session->delete('Auth');
		Router::reload();
		Router::connect('/', array('controller' => 'people', 'action' => 'login'));
		$url = '/';
		$this->Controller->request->addParams(Router::parse($url));
		Router::setRequestInfo(array($this->Controller->passedArgs, array(
			'base' => null, 'here' => $url, 'webroot' => '/', 'passedArgs' => array(),
			'argSeparator' => ':', 'namedArgs' => array()
		)));
		$this->Controller->request->data['AuthUser'] = array('username' => 'felix', 'password' => 'cake');
		$this->Controller->request->query['url'] = substr($url, 1);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'people', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$this->assertTrue(!!$user);
	}

/**
 * testCustomField method
 *
 * @access public
 * @return void
 */
	function testCustomField() {
		Router::reload();

		$this->AuthUserCustomField = new AuthUserCustomField();
		$user = array(
			'id' => 1, 'email' => 'harking@example.com',
			'password' => Security::hash(Configure::read('Security.salt') . 'cake'
		));
		$user = $this->AuthUserCustomField->save($user, false);

		Router::connect('/', array('controller' => 'people', 'action' => 'login'));
		$url = '/';
		$this->Controller->request->addParams(Router::parse($url));
		Router::setRequestInfo($this->Controller->request);
		$this->Controller->request->data['AuthUserCustomField'] = array(
			'email' => 'harking@example.com', 'password' => 'cake'
		);
		$this->Controller->request->query['url'] = substr($url, 1);
		$this->Controller->Auth->initialize($this->Controller);
        $this->Controller->Auth->fields = array('username' => 'email', 'password' => 'password');
		$this->Controller->Auth->loginAction = array('controller' => 'people', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUserCustomField';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$this->assertTrue(!!$user);
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
 * testPluginModel method
 *
 * @access public
 * @return void
 */
	function testPluginModel() {
		// Adding plugins
		Cache::delete('object_map', '_cake_core_');
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS)
		), true);
		App::objects('plugin', null, false);

		$PluginModel = ClassRegistry::init('TestPlugin.TestPluginAuthUser');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$PluginModel->save($user, false);

		$authUser = $PluginModel->find();

		$this->Controller->request->data['TestPluginAuthUser'] = array(
			'username' => $authUser['TestPluginAuthUser']['username'], 'password' => 'cake'
		);

		$this->Controller->request->addParams(Router::parse('auth_test/login'));
		$this->Controller->request->query['url'] = 'auth_test/login';

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'TestPlugin.TestPluginAuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$expected = array('TestPluginAuthUser' => array(
			'id' => 1, 'username' => 'gwoo', 'created' => '2007-03-17 01:16:23', 'updated' => date('Y-m-d H:i:s')
		));
		$this->assertEqual($user, $expected);
		$sessionKey = $this->Controller->Auth->sessionKey;
		$this->assertEqual('Auth.TestPluginAuthUser', $sessionKey);
		
		$this->Controller->Auth->loginAction = null;
		$this->Controller->Auth->__setDefaults();
		$loginAction = $this->Controller->Auth->loginAction;
		$expected = array(
		    'controller'	=> 'test_plugin_auth_users',
		    'action'		=> 'login',
		    'plugin'		=> 'test_plugin'
		);
		$this->assertEqual($loginAction, $expected);

		// Reverting changes
		Cache::delete('object_map', '_cake_core_');
		App::build();
		App::objects('plugin', null, false);
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
 * test the initialize callback and its interactions with Router::prefixes()
 *
 * @return void
 */
	function testInitializeAndRoutingPrefixes() {
		$restore = Configure::read('Routing');
		Configure::write('Routing.prefixes', array('admin', 'super_user'));
		Router::reload();
		$this->Controller->Auth->initialize($this->Controller);

		$this->assertTrue(isset($this->Controller->Auth->actionMap['delete']));
		$this->assertTrue(isset($this->Controller->Auth->actionMap['view']));
		$this->assertTrue(isset($this->Controller->Auth->actionMap['add']));
		$this->assertTrue(isset($this->Controller->Auth->actionMap['admin_view']));
		$this->assertTrue(isset($this->Controller->Auth->actionMap['super_user_delete']));

		Configure::write('Routing', $restore);
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
				'fields' => array('username' => 'email', 'password' => 'password'),
				'loginAction' => array('controller' => 'people', 'action' => 'login'),
				'userModel' => 'AuthUserCustomField',
				'sessionKey' => 'AltAuth.AuthUserCustomField'
			),
			'Session'
		);
		$this->Controller->Components->init($this->Controller);
		$this->Controller->Components->trigger('initialize', array(&$this->Controller));
		Router::reload();

		$this->AuthUserCustomField = new AuthUserCustomField();
		$user = array(
			'id' => 1, 'email' => 'harking@example.com',
			'password' => Security::hash(Configure::read('Security.salt') . 'cake'
		));
		$user = $this->AuthUserCustomField->save($user, false);

		Router::connect('/', array('controller' => 'people', 'action' => 'login'));
		$url = '/';
		$this->Controller->request->addParams(Router::parse($url));
		Router::setRequestInfo($this->Controller->request);
		$this->Controller->request->data['AuthUserCustomField'] = array(
			'email' => 'harking@example.com', 'password' => 'cake'
		);
		$this->Controller->request->query['url'] = substr($url, 1);
		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertTrue(!!$user);

		$expected = array(
			'fields' => array('username' => 'email', 'password' => 'password'),
			'loginAction' => array('controller' => 'people', 'action' => 'login'),
			'logoutRedirect' => array('controller' => 'people', 'action' => 'login'),
			'userModel' => 'AuthUserCustomField',
			'sessionKey' => 'AltAuth.AuthUserCustomField'
		);
		$this->assertEqual($expected['fields'], $this->Controller->Auth->fields);
		$this->assertEqual($expected['loginAction'], $this->Controller->Auth->loginAction);
		$this->assertEqual($expected['logoutRedirect'], $this->Controller->Auth->logoutRedirect);
		$this->assertEqual($expected['userModel'], $this->Controller->Auth->userModel);
		$this->assertEqual($expected['sessionKey'], $this->Controller->Auth->sessionKey);
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
}
