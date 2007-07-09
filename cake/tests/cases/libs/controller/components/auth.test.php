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
uses('controller' . DS . 'components' . DS .'auth');

class AuthUser extends CakeTestModel {
	var $name = 'AuthUser';

	function parentNode() {
		return true;
	}

	function bindNode($object) {
		return 'Roles/Admin';
	}

	function isAuthorized($user) {
		if(!empty($user)) {
			return true;
		}
		return false;
	}
}

class AuthTestController extends Controller {
	var $name = 'AuthTest';
	var $uses = array('AuthUser');
	var $components = array('Auth', 'Acl');

	function __construct() {
		$this->params = Router::parse('/auth_test');
		Router::setRequestInfo(array($this->params, array('base' => null, 'here' => '/', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)));
		parent::__construct();
	}

	function beforeFilter() {
		$this->Auth->allow('logout');
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
		exit();
	}

	function isAuthorized() {
		return true;
	}
}

class AuthTest extends CakeTestCase {
	var $name = 'Auth';
	var $fixtures = array('core.auth_user', 'core.aco', 'core.aro', 'core.aros_aco');

	function skip() {
		$this->skipIf(true, 'Auth tests currently disabled, to test use a clean database with tables needed for acl and comment out this line');
	}

	function setUp() {
		$this->Controller =& new AuthTestController();
		restore_error_handler();
		@$this->Controller->_initComponents();
		set_error_handler('simpleTestErrorHandler');
		ClassRegistry::addObject('view', new View($this->Controller));
	}
	function testIt(){
		$this->assertTrue(true);
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

	function testAuthFalse() {
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = false;
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);
	}

	function testAuthController(){
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->authorize = 'controller';
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);
		$this->Controller->Session->del('Auth');
	}

	function testAuthorizeModel() {
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Controller->Auth->userModel = 'AuthUser';
		$this->Controller->Auth->initialize($this->Controller);
		$this->Controller->Auth->authorize = array('model'=>'AuthUser');
		$result = $this->Controller->Auth->startup($this->Controller);
		$this->assertTrue($result);
	}

	function testAuthWithDB_ACL() {
		$this->AuthUser =& new AuthUser();
		$user = $this->AuthUser->find();
		$this->Controller->Session->write('Auth', $user);

		$this->Controller->params['controller'] = 'auth_test';
		$this->Controller->params['action'] = 'add';

		$this->Controller->Acl->startup($this->Controller);

		$this->Controller->Acl->Aro->id = null;
		$this->Controller->Acl->Aro->create(array('alias'=>'Roles'));
		$this->Controller->Acl->Aro->save();
		$this->Controller->Acl->Aro->create(array('alias'=>'Admin'));
		$this->Controller->Acl->Aro->save();
		$this->Controller->Acl->Aro->create(array('model'=>'AuthUser', 'foreign_key'=>'1', 'alias'=> 'mariano'));
		$this->Controller->Acl->Aro->save();
		$this->Controller->Acl->Aro->setParent(1, 2);
		$this->Controller->Acl->Aro->setParent(2, 3);

		$this->Controller->Acl->Aco->create(array('alias'=>'Root'));
		$this->Controller->Acl->Aco->save();
		$this->Controller->Acl->Aco->create(array('alias'=>'AuthTest'));
		$this->Controller->Acl->Aco->save();
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
		$this->Controller->Acl->Aro->execute('truncate aros;');
		$this->Controller->Acl->Aro->execute('truncate acos;');
		$this->Controller->Acl->Aro->execute('truncate aros_acos;');
	}


	function tearDown() {
		unset($this->Controller, $this->AuthUser);
	}
}
?>