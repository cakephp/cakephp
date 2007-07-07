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
require_once LIBS . '/controller/components/auth.php';

class AuthTestUser extends CakeTestModel {
	var $name = 'AuthTestUser';
}

class AuthTestController extends Controller {
	var $name = 'AuthTest';
	var $uses = 'AuthTestUser'; 
	var $components = array('Auth', 'Acl');

	function beforeFilter() {
		$this->Auth->userModel('AuthTestUser');
		$this->Auth->logoutAction = 'login';
		$this->Auth->allow('logout');
		$this->Auth->authorize = 'controller';
	}

	function login() {
	}

	function logout() {
		$this->redirect($this->Auth->logout());
	}

	function add() {
	}

	function redirect($url) {
		return $url;
	}

	function isAuthorized() {
		return true;
	}

	function parentNode() {
		return true;
	}

	function bindNode($object) {
		return 'Roles/User';
	}
}

loadModel('AuthTestUser');

class AuthTest extends CakeTestCase {
	var $name = 'Auth';
	var $fixtures = array('auth_test_user', 'aco', 'aro', 'aros_aco');

	function startCase() {
		$this->Controller =& new AuthTestController();
		restore_error_handler();
		@$this->Controller->_initComponents();
		set_error_handler('simpleTestErrorHandler');
		$this->Controller->Auth->startup(&$this->Controller);
		ClassRegistry::addObject('view', new View($this->Controller));
		$this->AuthTestUser = new AuthTestUser;
	}

	function testNoAuth() {
		$this->assertFalse($this->Controller->Auth->isAuthorized($this->Controller));
	}

	function testUserData() {
		foreach ($this->AuthTestUser->findAll() as $key => $result) {
			$result['AuthTestUser']['password'] = Security::hash(CAKE_SESSION_STRING . $result['AuthTestUser']['password']);
			$this->AuthTestUser->save($result, false);		
		}

		$authTestUser = $this->AuthTestUser->read();
		$data['AuthTestUser']['username'] = $authTestUser['AuthTestUser']['username'];
		$data['AuthTestUser']['password'] = $authTestUser['AuthTestUser']['password'];
		$this->Controller->Auth->params['controller'] = 'AuthTest';
		$this->Controller->Auth->params['action'] = 'add';
		$this->Controller->Auth->Acl->Aro->create(1, null, 'chartjes');
		$this->Controller->Auth->Acl->Aro->create(0, null, 'Users');
		$this->Controller->Auth->Acl->Aro->setParent('Users', 1);
		$this->Controller->Auth->Acl->Aco->create(0, null, '/Home/home');
		$this->Controller->Auth->Acl->allow('Users', 'Home/home');
		$this->assertTrue($this->Controller->Auth->isAuthorized($this->Controller, 'controller', 'AuthTestUser'));
	
	}
}
?>
