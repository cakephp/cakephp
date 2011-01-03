<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.libs.controller.components.auth
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Component', 'auth/form_authenticate');
App::import('Model', 'AppModel');
App::import('Core', 'CakeRequest');
App::import('Core', 'Security');

require_once  CAKE_TESTS . 'cases' . DS . 'libs' . DS . 'model' . DS . 'models.php';

/**
 * Test case for FormAuthentication
 *
 * @package cake.test.cases.controller.components.auth
 */
class FormAuthenticateTest extends CakeTestCase {

	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->auth = new FormAuthenticate(array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User'
		));
		$this->password = Security::hash('password', null, true);
		ClassRegistry::init('User')->updateAll(array('password' => '"' . $this->password . '"'));
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	function testConstructor() {
		$object = new FormAuthenticate(array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'password')
		));
		$this->assertEquals('AuthUser', $object->settings['userModel']);
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $object->settings['fields']);
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateNoData() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array();
		$this->assertFalse($this->auth->authenticate($request));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateNoUsername() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array('password' => 'foobar'));
		$this->assertFalse($this->auth->authenticate($request));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateNoPassword() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array('user' => 'mariano'));
		$this->assertFalse($this->auth->authenticate($request));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	function testAuthenticateInjection() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array(
			'User' => array(
				'user' => '> 1',
				'password' => "' OR 1 = 1"
		));
		$this->assertFalse($this->auth->authenticate($request));
	}

/**
 * test authenticate sucesss
 *
 * @return void
 */
	function testAuthenticateSuccess() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array(
			'user' => 'mariano',
			'password' => $this->password
		));
		$result = $this->auth->authenticate($request);
		$expected = array(
			'id' => 1,
			'user' => 'mariano',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test scope failure.
 *
 * @return void
 */
	function testAuthenticateScopeFail() {
		$this->auth->settings['scope'] = array('user' => 'nate');
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array(
			'user' => 'mariano',
			'password' => $this->password
		));

		$this->assertFalse($this->auth->authenticate($request));
	}

/**
 * test a model in a plugin.
 *
 * @return void
 */
	function testPluginModel() {
		Cache::delete('object_map', '_cake_core_');
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
		), true);
		App::objects('plugin', null, false);

		$PluginModel = ClassRegistry::init('TestPlugin.TestPluginAuthUser');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$PluginModel->save($user, false);
	
		$this->auth->settings['userModel'] = 'TestPlugin.TestPluginAuthUser';
		$this->auth->settings['fields']['username'] = 'username';
	
		$request = new CakeRequest('posts/index', false);
		$request->data = array('TestPluginAuthUser' => array(
			'username' => 'gwoo',
			'password' => Security::hash('cake', null, true)
		));

		$result = $this->auth->authenticate($request);
		$expected = array(
			'id' => 1,
			'username' => 'gwoo',
			'created' => '2007-03-17 01:16:23',
			'updated' => date('Y-m-d H:i:s')
		);
		$this->assertEquals($expected, $result);
	}

}