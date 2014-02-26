<?php
/**
 * FormAuthenticateTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component\Auth;

use Cake\Cache\Cache;
use Cake\Controller\Component\Auth\FormAuthenticate;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * Test case for FormAuthentication
 *
 */
class FormAuthenticateTest extends TestCase {

/**
 * Fixtrues
 *
 * @var array
 */
	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Collection = $this->getMock('Cake\Controller\ComponentRegistry');
		$this->auth = new FormAuthenticate($this->Collection, array(
			'userModel' => 'Users'
		));
		$password = Security::hash('password', 'blowfish', false);
		$Users = TableRegistry::get('Users');
		$Users->updateAll(['password' => $password], []);
		$this->response = $this->getMock('Cake\Network\Response');
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	public function testConstructor() {
		$object = new FormAuthenticate($this->Collection, array(
			'userModel' => 'AuthUsers',
			'fields' => array('username' => 'user', 'password' => 'password')
		));
		$this->assertEquals('AuthUsers', $object->settings['userModel']);
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $object->settings['fields']);
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoData() {
		$request = new Request('posts/index');
		$request->data = array();
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoUsername() {
		$request = new Request('posts/index');
		$request->data = array('Users' => array('password' => 'foobar'));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoPassword() {
		$request = new Request('posts/index');
		$request->data = array('Users' => array('username' => 'mariano'));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test authenticate password is false method
 *
 * @return void
 */
	public function testAuthenticatePasswordIsFalse() {
		$request = new Request('posts/index', false);
		$request->data = array(
			'Users' => array(
				'username' => 'mariano',
				'password' => null
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * Test for password as empty string with _checkFields() call skipped
 * Refs https://github.com/cakephp/cakephp/pull/2441
 *
 * @return void
 */
	public function testAuthenticatePasswordIsEmptyString() {
		$request = new Request('posts/index', false);
		$request->data = array(
			'Users' => array(
				'username' => 'mariano',
				'password' => ''
		));

		$this->auth = $this->getMock(
			'Cake\Controller\Component\Auth\FormAuthenticate',
			array('_checkFields'),
			array(
				$this->Collection,
				array(
					'userModel' => 'Users'
				)
			)
		);

		// Simulate that check for ensuring password is not empty is missing.
		$this->auth->expects($this->once())
			->method('_checkFields')
			->will($this->returnValue(true));

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test authenticate field is not string
 *
 * @return void
 */
	public function testAuthenticateFieldsAreNotString() {
		$request = new Request('posts/index', false);
		$request->data = array(
			'Users' => array(
				'username' => array('mariano', 'phpnut'),
				'password' => 'my password'
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));

		$request->data = array(
			'Users' => array(
				'username' => 'mariano',
				'password' => array('password1', 'password2')
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateInjection() {
		$request = new Request('posts/index');
		$request->data = array(
			'Users' => array(
				'username' => '> 1',
				'password' => "' OR 1 = 1"
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test authenticate success
 *
 * @return void
 */
	public function testAuthenticateSuccess() {
		$request = new Request('posts/index');
		$request->data = array('Users' => array(
			'username' => 'mariano',
			'password' => 'password'
		));
		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'mariano',
			'created' => new \DateTime('2007-03-17 01:16:23'),
			'updated' => new \DateTime('2007-03-17 01:18:31')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test scope failure.
 *
 * @return void
 */
	public function testAuthenticateScopeFail() {
		$this->auth->settings['scope'] = array('Users.id' => 2);
		$request = new Request('posts/index');
		$request->data = array('Users' => array(
			'username' => 'mariano',
			'password' => 'password'
		));

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test a model in a plugin.
 *
 * @return void
 */
	public function testPluginModel() {
		Cache::delete('object_map', '_cake_core_');
		Plugin::load('TestPlugin');

		$PluginModel = TableRegistry::get('TestPlugin.AuthUsers');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake', 'blowfish', false);
		$PluginModel->save(new Entity($user));

		$this->auth->settings['userModel'] = 'TestPlugin.AuthUsers';

		$request = new Request('posts/index');
		$request->data = array('AuthUsers' => array(
			'username' => 'gwoo',
			'password' => 'cake'
		));

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'gwoo',
			'created' => new \DateTime('2007-03-17 01:16:23'),
			'updated' => new \DateTime('2007-03-17 01:18:31')
		);
		$this->assertEquals($expected, $result);
		Plugin::unload();
	}

/**
 * test password hasher settings
 *
 * @return void
 */
	public function testPasswordHasherSettings() {
		$this->auth->settings['passwordHasher'] = array(
			'className' => 'Simple',
			'hashType' => 'md5'
		);

		$passwordHasher = $this->auth->passwordHasher();
		$result = $passwordHasher->config();
		$this->assertEquals('md5', $result['hashType']);

		$hash = Security::hash('mypass', 'md5', true);
		$User = TableRegistry::get('Users');
		$User->updateAll(
			array('password' => $hash),
			array('username' => 'mariano')
		);

		$request = new Request('posts/index');
		$request->data = array('Users' => array(
			'username' => 'mariano',
			'password' => 'mypass'
		));

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'mariano',
			'created' => new \DateTime('2007-03-17 01:16:23'),
			'updated' => new \DateTime('2007-03-17 01:18:31')
		);
		$this->assertEquals($expected, $result);

		$this->auth = new FormAuthenticate($this->Collection, array(
			'fields' => array('username' => 'username', 'password' => 'password'),
			'userModel' => 'Users'
		));
		$this->auth->settings['passwordHasher'] = array(
			'className' => 'Simple',
			'hashType' => 'sha1'
		);
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

}
