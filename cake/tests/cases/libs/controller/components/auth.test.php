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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('aclnodetestbase')) {
	class AclNodeTestBase extends AclNode {
		var $useDbConfig = 'test_suite';
		var $cacheSources = false;
	}
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('arotest')) {
	class AroTest extends AclNodeTestBase {
		var $name = 'AroTest';
		var $useTable = 'aros';
		var $hasAndBelongsToMany = array('AcoTest' => array('with' => 'PermissionTest'));
	}
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('acotest')) {
	class AcoTest extends AclNodeTestBase {
		var $name = 'AcoTest';
		var $useTable = 'acos';
		var $hasAndBelongsToMany = array('AroTest' => array('with' => 'PermissionTest'));
	}
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('permissiontest')) {
	class PermissionTest extends CakeTestModel {
		var $name = 'PermissionTest';
		var $useTable = 'aros_acos';
		var $cacheQueries = false;
		var $belongsTo = array('AroTest' => array('foreignKey' => 'aro_id'),
								'AcoTest' => array('foreignKey' => 'aco_id')
								);
		var $actsAs = null;
	}
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('acoactiontest')) {
	class AcoActionTest extends CakeTestModel {
		var $name = 'AcoActionTest';
		var $useTable = 'aco_actions';
		var $belongsTo = array('AcoTest' => array('foreignKey' => 'aco_id'));
	}
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('db_acl_test')) {
	class DB_ACL_TEST extends DB_ACL {

		function __construct() {
			$this->Aro =& new AroTest();
			$this->Aro->Permission =& new PermissionTest();
			$this->Aco =& new AcoTest();
			$this->Aro->Permission =& new PermissionTest();
		}
	}
}
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

	function setUp() {
		$this->Controller =& new AuthTestController();
		restore_error_handler();
		@$this->Controller->_initComponents();
		set_error_handler('simpleTestErrorHandler');
		ClassRegistry::addObject('view', new View($this->Controller));
	}

	function testNoAuth() {
		$this->assertFalse($this->Controller->Auth->isAuthorized());
	}

	function testLogin() {
		$this->AuthUser =& new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(CAKE_SESSION_STRING . 'cake');
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
		$this->Controller->Acl->startup($this->Controller);

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
	}

	function testLoginRedirect() {
        $backup = $_SERVER['HTTP_REFERER'];

        $_SERVER['HTTP_REFERER'] = false;
		
		$this->Controller->Session->write('Auth', array('AuthUser' => array('id'=>'1', 'username'=>'nate')));
		
        $this->Controller->params['url']['url'] = 'users/login';
        $this->Controller->Auth->initialize($this->Controller);

 		$this->Controller->Auth->userModel = 'AuthUser';
        $this->Controller->Auth->loginRedirect = array('controller' => 'pages', 'action' => 'display', 'welcome');
        $this->Controller->Auth->startup($this->Controller);
        $expected = $this->Controller->Auth->_normalizeURL($this->Controller->Auth->loginRedirect);
        $this->assertEqual($expected, $this->Controller->Auth->redirect());

		$this->Controller->Session->del('Auth');
       
        $this->Controller->params['url']['url'] = 'admin/';
        $this->Controller->Auth->initialize($this->Controller);
 		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->loginRedirect = null;
        $this->Controller->Auth->startup($this->Controller);
        $expected = $this->Controller->Auth->_normalizeURL('admin/');
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
        $expected = $this->Controller->Auth->_normalizeURL('admin');
        $this->assertEqual($expected, $this->Controller->Auth->redirect());

        $_SERVER['HTTP_REFERER'] = $backup;
        $this->Controller->Session->del('Auth');
    }

	function testEmptyUsernameOrPassword() {
		$this->AuthUser =& new AuthUser();
		$user['id'] = 1;
		$user['username'] = 'mariano';
		$user['password'] = Security::hash(CAKE_SESSION_STRING . 'cake');
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
		$this->assertEqual($user, false);
		$this->Controller->Session->del('Auth');
    }

	function tearDown() {
		unset($this->Controller, $this->AuthUser);
	}
}
?>