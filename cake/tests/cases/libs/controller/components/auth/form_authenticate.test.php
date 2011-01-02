<?php

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

	public $fixtures = array('core.user');

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

}