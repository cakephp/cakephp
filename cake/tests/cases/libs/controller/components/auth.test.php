<?php
/* SVN FILE: $Id$ */
/**
 * Series of tests for email component.
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
	var $name = 'User';

	function parentNode() {
		return true;
	}

	function bindNode($object) {
		return 'Roles/User';
	}
}

class AuthTestController extends Controller {
	var $name = 'AuthTest';
	var $uses = array('AuthUser');
	var $components = array('Auth', 'Acl');

	function __construct() {
		$this->params = Router::parse('/auth_test');
		Router::setRequestInfo(array($this->params, array('base' => '/', 'here' => '/', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)));
		parent::__construct();
	}

	function beforeFilter() {
		$this->Auth->logoutAction = 'login';
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
		return true;
	}

	function isAuthorized() {
		return true;
	}
}

class AuthTest extends CakeTestCase {
	var $name = 'Auth';
	var $fixtures = array('core.auth_user', 'core.aco', 'core.aro', 'core.aros_aco');

	function setUp() {
		$this->Controller =& new AuthTestController();
		restore_error_handler();
		@$this->Controller->_initComponents();
		set_error_handler('simpleTestErrorHandler');
		ClassRegistry::addObject('view', new View($this->Controller));
	}

	function testIt(){
		$this->User =& new AuthUser();
		$user = $this->User->find();
		$this->Controller->Session->write('Auth', $user);
		$this->Auth->authorize = 'controller';
		$this->Controller->Auth->startup($this->Controller);
		$this->assertTrue(true);
	}
	function testNoAuth() {
		$this->assertFalse($this->Controller->Auth->isAuthorized($this->Controller));
	}

	function testUserData() {
		$this->User =& new AuthUser();
		foreach ($this->User->findAll() as $key => $result) {
			$result['User']['password'] = Security::hash(CAKE_SESSION_STRING . $result['User']['password']);
			$this->User->save($result, false);
		}

		$authTestUser = $this->User->read();
		$data['User']['username'] = $authTestUser['User']['username'];
		$data['User']['password'] = $authTestUser['User']['password'];

		$this->Auth->authorize = 'Acl';
		$this->Controller->Auth->startup($this->Controller);

		$this->Controller->Auth->params['controller'] = 'AuthTest';
		$this->Controller->Auth->params['action'] = 'add';
		$this->Controller->Auth->Acl->Aro->create(1, null, 'chartjes');
		$this->Controller->Auth->Acl->Aro->create(0, null, 'Users');
		$this->Controller->Auth->Acl->Aro->setParent('Users', 1);
		$this->Controller->Auth->Acl->Aco->create(0, null, '/Home/home');
		$this->Controller->Auth->Acl->allow('Users', 'Home/home');
		$this->assertTrue($this->Controller->Auth->isAuthorized($this->Controller, 'controller', 'User'));
	}

	function tearDown() {
		unset($this->Controller, $this->AuthUser);
	}
}
?>