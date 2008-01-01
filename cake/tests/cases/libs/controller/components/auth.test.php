<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake
 * @subpackage		cake.cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5347
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'components' . DS .'auth', 'controller' . DS . 'components' . DS .'acl');

uses('controller'.DS.'components'.DS.'acl', 'model'.DS.'db_acl');
Configure::write('Security.salt', 'JfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class AuthUser extends CakeTestModel {
	var $name = 'AuthUser';
	var $useDbConfig = 'test_suite';

	function parentNode() {
		return true;
	}

	function bindNode($object) {
		return 'Roles/Admin';
	}

	function isAuthorized($user, $controller = null, $action = null) {
		if (!empty($user)) {
			return true;
		}
		return false;
	}
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class AuthTestController extends Controller {
	var $name = 'AuthTest';
	var $uses = array('AuthUser');
	var $components = array('Auth', 'Acl');

	function __construct() {
		$this->params = Router::parse('/auth_test');
		Router::setRequestInfo(array($this->params, array('base' => null, 'here' => '/auth_test', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)));
		parent::__construct();
	}

	function beforeFilter() {
	}

	function login() {
	}

	function logout() {
		//$this->redirect($this->Auth->logout());
	}

	function add() {
	}

	function redirect() {
		return false;
	}

	function isAuthorized() {
		if(isset($this->params['testControllerAuth'])) {
			return false;
		}
		return true;
	}
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class AuthTest extends CakeTestCase {
	var $name = 'Auth';

	var $fixtures = array('core.auth_user', 'core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

	function startTest() {
		$this->Controller =& new AuthTestController();
		restore_error_handler();
		@$this->Controller->_initComponents();
		set_error_handler('simpleTestErrorHandler');
		ClassRegistry::addObject('view', new View($this->Controller));
		$this->Controller->Session->del('Auth');
		$this->Controller->Session->del('Message.auth');
	}

	function testNoAuth() {
		$this->assertFalse($this->Controller->Auth->isAuthorized());
	}

	function testLogin() {
		$this->AuthUser =& new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->AuthUser->save($user, false);

		$authUser = $this->AuthUser->find();

		$this->Controller->data['AuthUser']['username'] = $authUser['AuthUser']['username'];
		$this->Controller->data['AuthUser']['password'] = 'cake';

		$this->Controller->params['url']['url'] = 'auth_test/login';

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$this->assertEqual($user, array('AuthUser'=>array('id'=>1, 'username'=>'mariano', 'created'=> '2007-03-17 01:16:23', 'updated'=> date('Y-m-d H:i:s'))));
		$this->Controller->Session->del('Auth');
	}

	function testAuthorizeFalse() {
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = false;
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->Session->del('Auth');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
	}

	function testAuthorizeController(){
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'controller';
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);

		$this->Controller->params['testControllerAuth'] = 1;
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$this->assertFalse($result);

		$this->Controller->Session->del('Auth');
	}

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

		$this->Controller->Session->del('Auth');
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$result = $this->Controller->Auth->isAuthorized();
		$this->assertFalse($result);

	}

	function testAuthorizeCrud() {
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);

		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';

		$this->Controller->Acl->name = 'DB_ACL_TEST';

		$this->Controller->Acl->Aro->id = null;
		$this->Controller->Acl->Aro->create(array('alias'=>'Roles'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Controller->Acl->Aro->create(array('alias'=>'Admin'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Controller->Acl->Aro->create(array('model'=>'AuthUser', 'foreign_key'=>'1', 'alias'=> 'mariano'));
		$result = $this->Controller->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Controller->Acl->Aro->setParent(1, 2);
		$this->Controller->Acl->Aro->setParent(2, 3);

		$this->Controller->Acl->Aco->create(array('alias'=>'Root'));
		$result = $this->Controller->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Controller->Acl->Aco->create(array('alias'=>'AuthTest'));
		$result = $this->Controller->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Controller->Acl->Aco->setParent(1, 2);

		$this->Controller->Acl->allow('Roles/Admin', 'Root');
		$this->Controller->Acl->allow('Roles/Admin', 'Root/AuthTest');

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'crud';
		$this->Controller->Auth->actionPath = 'Root/';

		$this->Controller->Auth->startup($this->Controller);


		$this->assertTrue($this->Controller->Auth->isAuthorized());

		$this->Controller->Session->del('Auth');
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
	}

	function testLoginRedirect() {
		if (isset($_SERVER['HTTP_REFERER'])) {
			$backup = $_SERVER['HTTP_REFERER'];
		} else {
			$backup = null;
		}

		$_SERVER['HTTP_REFERER'] = false;

		$this->Controller->Session->write('Auth', array('AuthUser' => array('id'=>'1', 'username'=>'nate')));

		$this->Controller->params['url']['url'] = 'users/login';
		$this->Controller->Auth->initialize($this->Controller);

 		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = array('controller' => 'pages', 'action' => 'display', 'welcome');
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize($this->Controller->Auth->loginRedirect);
		$this->assertEqual($expected, $this->Controller->Auth->redirect());

		$this->Controller->Session->del('Auth');

		$this->Controller->params['url']['url'] = 'admin/';
		$this->Controller->Auth->initialize($this->Controller);
 		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = null;
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('admin/');
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$this->assertEqual($expected, $this->Controller->Auth->redirect());

		$this->Controller->Session->del('Auth');

		$_SERVER['HTTP_REFERER'] = '/admin/';

		$this->Controller->Session->write('Auth', array('AuthUser' => array('id'=>'1', 'username'=>'nate')));

		$this->Controller->params['url']['url'] = 'auth_test/login';

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';

 		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = false;
		$this->Controller->Auth->startup($this->Controller);
		$expected = Router::normalize('admin');
		$this->assertEqual($expected, $this->Controller->Auth->redirect());

		$_SERVER['HTTP_REFERER'] = $backup;
		$this->Controller->Session->del('Auth');
	}

	function testEmptyUsernameOrPassword() {
		$this->AuthUser =& new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$this->AuthUser->save($user, false);

		$authUser = $this->AuthUser->find();

		$this->Controller->data['AuthUser']['username'] = '';
		$this->Controller->data['AuthUser']['password'] = '';

		$this->Controller->params['url']['url'] = 'auth_test/login';

		$this->Controller->Auth->initialize($this->Controller);

		$this->Controller->Auth->loginAction = 'auth_test/login';
		$this->Controller->Auth->userModel = 'AuthUser';

		$this->Controller->Auth->startup($this->Controller);
		$user = $this->Controller->Auth->user();
		$this->assertTrue($this->Controller->Session->check('Message.auth'));
		$this->assertEqual($user, false);
		$this->Controller->Session->del('Auth');
	}

	function tearDown() {
		unset($this->Controller, $this->AuthUser);
	}
}
?>