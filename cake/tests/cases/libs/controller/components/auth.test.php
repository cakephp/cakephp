<?php
/**
 * AuthComponentTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Component', array('Auth', 'Acl'));
App::import('Model', 'DbAcl');
App::import('Core', 'Xml');

Mock::generate('AclComponent', 'AuthTestMockAclComponent');

/**
* TestAuthComponent class
*
* @package       cake
* @subpackage    cake.tests.cases.libs.controller.components
*/
class TestAuthComponent extends AuthComponent {

/**
 * testStop property
 *
 * @var bool false
 * @access public
 */
	var $testStop = false;

/**
 * Sets default login state
 *
 * @var bool true
 * @access protected
 */
	var $_loggedIn = true;

/**
 * stop method
 *
 * @access public
 * @return void
 */
	function _stop() {
		$this->testStop = true;
	}
}

/**
* AuthUser class
*
* @package       cake
* @subpackage    cake.tests.cases.libs.controller.components
*/
class AuthUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	var $name = 'AuthUser';

/**
 * useDbConfig property
 *
 * @var string 'test_suite'
 * @access public
 */
	var $useDbConfig = 'test_suite';

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
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AuthUserCustomField extends AuthUser {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	var $name = 'AuthUserCustomField';
}

/**
* UuidUser class
*
* @package       cake
* @subpackage    cake.tests.cases.libs.controller.components
*/
class UuidUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	var $name = 'UuidUser';

/**
 * useDbConfig property
 *
 * @var string 'test_suite'
 * @access public
 */
	var $useDbConfig = 'test_suite';

/**
 * useTable property
 *
 * @var string 'uuid'
 * @access public
 */
	var $useTable = 'uuids';

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
* @subpackage    cake.tests.cases.libs.controller.components
*/
class AuthTestController extends Controller {

/**
 * name property
 *
 * @var string 'AuthTest'
 * @access public
 */
	var $name = 'AuthTest';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array('AuthUser');

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Session', 'Auth', 'Acl');

/**
 * testUrl property
 *
 * @var mixed null
 * @access public
 */
	var $testUrl = null;

/**
 * construct method
 *
 * @access private
 * @return void
 */
	function __construct() {
		$this->params = Router::parse('/auth_test');
		Router::setRequestInfo(array($this->params, array('base' => null, 'here' => '/auth_test', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())));
		parent::__construct();
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
		if (isset($this->params['testControllerAuth'])) {
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
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AjaxAuthController extends Controller {

/**
 * name property
 *
 * @var string 'AjaxAuth'
 * @access public
 */
	var $name = 'AjaxAuth';

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Session', 'TestAuth');

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * testUrl property
 *
 * @var mixed null
 * @access public
 */
	var $testUrl = null;

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
* @subpackage    cake.tests.cases.libs.controller.components
*/
class AuthTest extends CakeTestCase {

/**
 * name property
 *
 * @var string 'Auth'
 * @access public
 */
	var $name = 'Auth';

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.uuid', 'core.auth_user', 'core.auth_user_custom_field', 'core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

/**
 * initialized property
 *
 * @var bool false
 * @access public
 */
	var $initialized = false;

/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->_server = $_SERVER;
		$this->_env = $_ENV;

		$this->_securitySalt = Configure::read('Security.salt');
		Configure::write('Security.salt', 'JfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');

		$this->_acl = Configure::read('Acl');
		Configure::write('Acl.database', 'test_suite');
		Configure::write('Acl.classname', 'DbAcl');

		$this->Controller =& new AuthTestController();
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();

		ClassRegistry::addObject('view', new View($this->Controller));

		$this->Controller->Session->delete('Auth');
		$this->Controller->Session->delete('Message.auth');

		Router::reload();

		$this->initialized = true;
	}

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		$_SERVER = $this->_server;
		$_ENV = $this->_env;
		Configure::write('Acl', $this->_acl);
		Configure::write('Security.salt', $this->_securitySalt);

		$this->Controller->Session->delete('Auth');
		$this->Controller->Session->delete('Message.auth');
		ClassRegistry::flush();
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
		$this->Controller->params['action'] = 'thisdoesnotexist';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->scaffold = null;
		$this->Controller->params['action'] = 'index';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * testLogin method
 *
 * @access public
 * @return void
 */
	function testLogin() {
		$this->AuthUser =& new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->AuthUser->save($user, false);

		$authUser = $this->AuthUser->find();

		$this->Controller->data['AuthUser']['username'] = $authUser['AuthUser']['username'];
		$this->Controller->data['AuthUser']['password'] = 'cake';

		$this->Controller->params = Router::parse('auth_test/login');
		$this->Controller->params['url']['url'] = 'auth_test/login';

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

		$this->Controller->data['AuthUser']['username'] = 'blah';
		$this->Controller->data['AuthUser']['password'] = '';

		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertFalse($user);
		$this->Controller->Session->delete('Auth');

		$this->Controller->data['AuthUser']['username'] = 'now() or 1=1 --';
		$this->Controller->data['AuthUser']['password'] = '';

		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertFalse($user);
		$this->Controller->Session->delete('Auth');

		$this->Controller->data['AuthUser']['username'] = 'now() or 1=1 # something';
		$this->Controller->data['AuthUser']['password'] = '';

		$this->Controller->Auth->startup($this->Controller);

		$user = $this->Controller->Auth->user();
		$this->assertFalse($user);
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
		$_referer = $_SERVER['HTTP_REFERER'];
		$_SERVER['HTTP_REFERER'] = '/pages/display/about';

		$this->Controller->data = array();
		$this->Controller->params = Router::parse('auth_test/login');
		$this->Controller->params['url']['url'] = 'auth_test/login';
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
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = false;
		$this->Controller->params = Router::parse('auth_test/add');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->Session->delete('Auth');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertFalse($result);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));

		$this->Controller->params = Router::parse('auth_test/camelCase');
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
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'controller';
		$this->Controller->params = Router::parse('auth_test/add');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->params['testControllerAuth'] = 1;
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
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);

		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';
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
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);

		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';

		$this->Controller->Acl->name = 'DbAclTest';

		$this->Controller->Acl->Aro->id = null;
		$this->Controller->Acl->Aro->create(array('alias' => 'Roles'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertTrue($result);

		$parent = $this->Controller->Acl->Aro->id;

		$this->Controller->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Admin'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertTrue($result);

		$parent = $this->Controller->Acl->Aro->id;

		$this->Controller->Acl->Aro->create(array(
			'model' => 'AuthUser', 'parent_id' => $parent, 'foreign_key' => 1, 'alias'=> 'mariano'
		));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Controller->Acl->Aco->create(array('alias' => 'Root'));
		$result = $this->Controller->Acl->Aco->save();
		$this->assertTrue($result);

		$parent = $this->Controller->Acl->Aco->id;

		$this->Controller->Acl->Aco->create(array('parent_id' => $parent, 'alias' => 'AuthTest'));
		$result = $this->Controller->Acl->Aco->save();
		$this->assertTrue($result);

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
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';

		$this->Controller->Acl =& new AuthTestMockAclComponent();
		$this->Controller->Acl->setReturnValue('check', true);

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'actions';
		$this->Controller->Auth->actionPath = 'Root/';

		$this->Controller->Acl->expectAt(0, 'check', array(
			$user, 'Root/AuthTest/add'
		));

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
		$this->Controller->Auth->deny('add', 'camelcase');

		$this->Controller->params['action'] = 'delete';
		$this->assertTrue($this->Controller->Auth->startup($this->Controller));

		$this->Controller->params['action'] = 'add';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->params['action'] = 'Add';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->params['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));

		$this->Controller->Auth->allow('*');
		$this->Controller->Auth->deny(array('add', 'camelcase'));

		$this->Controller->params['action'] = 'camelCase';
		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * test the action() method
 *
 * @return void
 */
	function testActionMethod() {
		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';

		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->actionPath = 'ROOT/';

		$result = $this->Controller->Auth->action();
		$this->assertEqual($result, 'ROOT/AuthTest/add');

		$result = $this->Controller->Auth->action(':controller');
		$this->assertEqual($result, 'ROOT/AuthTest');

		$result = $this->Controller->Auth->action(':controller');
		$this->assertEqual($result, 'ROOT/AuthTest');

		$this->Controller->params['plugin'] = 'test_plugin';
		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';
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
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);

		$this->assertFalse($this->Controller->Auth->startup($this->Controller));
	}

/**
 * test that allow() and allowedActions work with camelCase method names.
 *
 * @return void
 */
	function testAllowedActionsWithCamelCaseMethods() {
		$url = '/auth_test/camelCase';
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->allow('*');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result, 'startup() should return true, as action is allowed. %s');

		$url = '/auth_test/camelCase';
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
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
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->allow(array('delete', 'add'));
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result, 'startup() should return true, as action is allowed. %s');
	}

	function testAllowedActionsSetWithAllowMethod() {
		$url = '/auth_test/action_name';
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->allow('action_name', 'anotherAction');
		$this->assertEqual($this->Controller->Auth->allowedActions, array('action_name', 'anotheraction'));
	}

/**
 * testLoginRedirect method
 *
 * @access public
 * @return void
 */
	function testLoginRedirect() {
		if (isset($_SERVER['HTTP_REFERER'])) {
			$backup = $_SERVER['HTTP_REFERER'];
		} else {
			$backup = null;
		}

		$_SERVER['HTTP_REFERER'] = false;

		$this->Controller->Session->write('Auth', array(
			'AuthUser' => array('id' => '1', 'username' => 'nate')
		));

		$this->Controller->params = Router::parse('users/login');
		$this->Controller->params['url']['url'] = 'users/login';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = array(
			'controller' => 'pages', 'action' => 'display', 'welcome'
		);
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize($this->Controller->Auth->loginRedirect);
		$this->assertEqual($expected, $this->Controller->Auth->redirect());

		$this->Controller->Session->delete('Auth');

		$this->Controller->params['url']['url'] = 'admin/';
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
		$this->Controller->params = Router::parse($url);
		array_push($this->Controller->methods, 'view', 'edit', 'index');

		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->authorize = 'controller';
		$this->Controller->params['testControllerAuth'] = true;

		$this->Controller->Auth->loginAction = array(
			'controller' => 'AuthTest', 'action' => 'login'
		);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/');
		$this->assertEqual($expected, $this->Controller->testUrl);


		$this->Controller->Session->delete('Auth');
		$_SERVER['HTTP_REFERER'] = Router::url('/admin/', true);

		$this->Controller->Session->write('Auth', array(
			'AuthUser' => array('id'=>'1', 'username'=>'nate'))
		);
		$this->Controller->params['url']['url'] = 'auth_test/login';
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
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('posts/index/year:2008/month:feb');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

		//passed args
		$this->Controller->Session->delete('Auth');
		$url = '/posts/view/1';
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
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
		$url = '/posts/index/29?print=true&refer=menu';
		$this->Controller->params = Dispatcher::parseParams($url);
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
		$url = '/posts/index/29?print=true&refer=menu';
		$this->Controller->params = Dispatcher::parseParams($url);
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
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
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
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = Router::normalize($url);
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->loginAction = array('controller' => 'AuthTest', 'action' => 'login');
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('/');
		$this->assertEqual($expected, $this->Controller->Session->read('Auth.redirect'));

		$_SERVER['HTTP_REFERER'] = $backup;
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
		$this->Controller->params = Router::parse('auth_test/something_totally_wrong');
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
		$this->AuthUser =& new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->AuthUser->save($user, false);

		$authUser = $this->AuthUser->find();

		$this->Controller->data['AuthUser']['username'] = '';
		$this->Controller->data['AuthUser']['password'] = '';

		$this->Controller->params = Router::parse('auth_test/login');
		$this->Controller->params['url']['url'] = 'auth_test/login';
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
		$this->AuthUser =& new AuthUser();
		$this->AuthUser->id = 2;
		$this->AuthUser->saveField('password', Security::hash(Configure::read('Security.salt') . 'cake'));

		$this->Controller->data['AuthUser']['username'] = 'nate';
		$this->Controller->data['AuthUser']['password'] = 'cake';
		$this->Controller->params = Router::parse('auth_test/login');
		$this->Controller->params['url']['url'] = 'auth_test/login';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_array($this->Controller->Auth->user()));

		$this->Controller->Session->delete($this->Controller->Auth->sessionKey);

		$this->Controller->data['AuthUser']['username'] = 'nate';
		$this->Controller->data['AuthUser']['password'] = 'cake1';
		$this->Controller->params['url']['url'] = 'auth_test/login';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));

		$this->Controller->Session->delete($this->Controller->Auth->sessionKey);

		$this->Controller->data['AuthUser']['username'] = '> n';
		$this->Controller->data['AuthUser']['password'] = 'cake';
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));

		unset($this->Controller->data['AuthUser']['password']);
		$this->Controller->data['AuthUser']['username'] = "1'1";
		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(is_null($this->Controller->Auth->user()));

		unset($this->Controller->data['AuthUser']['username']);
		$this->Controller->data['AuthUser']['password'] = "1'1";
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

		if (PHP5) {
			$xml = array(
				'User' => array(
					'username' => 'batman@batcave.com',
					'password' => 'bruceWayne',
				)
			);
			$data =& new Xml($xml);
			$return = $this->Controller->Auth->hashPasswords($data);
			$expected = $data;
			$this->assertEqual($return, $expected);
		}
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
		$this->Controller->params = Router::parse($url);
		Router::setRequestInfo(array($this->Controller->passedArgs, array(
			'base' => null, 'here' => $url, 'webroot' => '/', 'passedArgs' => array(),
			'argSeparator' => ':', 'namedArgs' => array()
		)));

		$this->AuthUser =& new AuthUser();
		$user = array(
			'id' => 1, 'username' => 'felix',
			'password' => Security::hash(Configure::read('Security.salt') . 'cake'
		));
		$user = $this->AuthUser->save($user, false);

		$this->Controller->data['AuthUser'] = array('username' => 'felix', 'password' => 'cake');
		$this->Controller->params['url']['url'] = substr($url, 1);
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
		$this->Controller->params = Router::parse($url);
		Router::setRequestInfo(array($this->Controller->passedArgs, array(
			'base' => null, 'here' => $url, 'webroot' => '/', 'passedArgs' => array(),
			'argSeparator' => ':', 'namedArgs' => array()
		)));
		$this->Controller->data['AuthUser'] = array('username' => 'felix', 'password' => 'cake');
		$this->Controller->params['url']['url'] = substr($url, 1);
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

		$this->AuthUserCustomField =& new AuthUserCustomField();
		$user = array(
			'id' => 1, 'email' => 'harking@example.com',
			'password' => Security::hash(Configure::read('Security.salt') . 'cake'
		));
		$user = $this->AuthUserCustomField->save($user, false);

		Router::connect('/', array('controller' => 'people', 'action' => 'login'));
		$url = '/';
		$this->Controller->params = Router::parse($url);
		Router::setRequestInfo(array($this->Controller->passedArgs, array(
			'base' => null, 'here' => $url, 'webroot' => '/', 'passedArgs' => array(),
			'argSeparator' => ':', 'namedArgs' => array()
		)));
		$this->Controller->data['AuthUserCustomField'] = array('email' => 'harking@example.com', 'password' => 'cake');
		$this->Controller->params['url']['url'] = substr($url, 1);
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
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = ltrim($url, '/');
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'add', 'plugin' => null,
				'controller' => 'auth_test', 'admin' => true,
				'url' => array('url' => $this->Controller->params['url']['url'])
			),
			array(
				'base' => null, 'here' => $url,
				'webroot' => '/', 'passedArgs' => array(),
			)
		));
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

		$PluginModel =& ClassRegistry::init('TestPlugin.TestPluginAuthUser');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$PluginModel->save($user, false);

		$authUser = $PluginModel->find();

		$this->Controller->data['TestPluginAuthUser']['username'] = $authUser['TestPluginAuthUser']['username'];
		$this->Controller->data['TestPluginAuthUser']['password'] = 'cake';

		$this->Controller->params = Router::parse('auth_test/login');
		$this->Controller->params['url']['url'] = 'auth_test/login';

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
		App::build(array('views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)));
		$_SERVER['HTTP_X_REQUESTED_WITH'] = "XMLHttpRequest";

		if (!class_exists('dispatcher')) {
			require CAKE . 'dispatcher.php';
		}

		ob_start();
		$Dispatcher =& new Dispatcher();
		$Dispatcher->dispatch('/ajax_auth/add', array('return' => 1));
		$result = ob_get_clean();
		$this->assertEqual("Ajax!\nthis is the test element", $result);
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testLoginActionRedirect method
 *
 * @access public
 * @return void
 */
	function testLoginActionRedirect() {
		$admin = Configure::read('Routing.admin');
		Configure::write('Routing.admin', 'admin');
		Router::reload();

		$url = '/admin/auth_test/login';
		$this->Controller->params = Router::parse($url);
		$this->Controller->params['url']['url'] = ltrim($url, '/');
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_login', 'plugin' => null, 'controller' => 'auth_test',
				'admin' => true, 'url' => array('url' => $this->Controller->params['url']['url']),
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

		Configure::write('Routing.admin', $admin);
	}

/**
 * Tests that shutdown destroys the redirect session var
 *
 * @access public
 * @return void
 */
	function testShutDown() {
		$this->Controller->Session->write('Auth.redirect', 'foo');
		$this->Controller->Auth->_loggedIn = true;
		$this->Controller->Auth->shutdown($this->Controller);
		$this->assertFalse($this->Controller->Session->read('Auth.redirect'));
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
		$this->Controller =& new AuthTestController();
		$this->Controller->components = array(
			'Auth' => array(
				'fields' => array('username' => 'email', 'password' => 'password'),
				'loginAction' => array('controller' => 'people', 'action' => 'login'),
				'userModel' => 'AuthUserCustomField',
				'sessionKey' => 'AltAuth.AuthUserCustomField'
			),
			'Session'
		);
		$this->Controller->Component->init($this->Controller);
		$this->Controller->Component->initialize($this->Controller);
		Router::reload();

		$this->AuthUserCustomField =& new AuthUserCustomField();
		$user = array(
			'id' => 1, 'email' => 'harking@example.com',
			'password' => Security::hash(Configure::read('Security.salt') . 'cake'
		));
		$user = $this->AuthUserCustomField->save($user, false);

		Router::connect('/', array('controller' => 'people', 'action' => 'login'));
		$url = '/';
		$this->Controller->params = Router::parse($url);
		Router::setRequestInfo(array($this->Controller->passedArgs, array(
			'base' => null, 'here' => $url, 'webroot' => '/', 'passedArgs' => array(),
			'argSeparator' => ':', 'namedArgs' => array()
		)));
		$this->Controller->data['AuthUserCustomField'] = array('email' => 'harking@example.com', 'password' => 'cake');
		$this->Controller->params['url']['url'] = substr($url, 1);
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
