<?php
/**
 * BlowfishAuthenticateTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under the MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package	      Cake.Test.Case.Controller.Component.Auth
 * @since	      CakePHP(tm) v 2.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Controller\Component\Auth;

use Cake\Cache\Cache;
use Cake\Controller\Component\Auth\BlowfishAuthenticate;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Security;

require_once CAKE . 'Test/TestCase/Model/models.php';

/**
 * Test case for BlowfishAuthentication
 *
 * @package	Cake.Test.Case.Controller.Component.Auth
 */
class BlowfishAuthenticateTest extends TestCase {

	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->markTestIncomplete('Need to revisit once models work again.');
		$this->Collection = $this->getMock('Cake\Controller\ComponentRegistry');
		$this->auth = new BlowfishAuthenticate($this->Collection, array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User'
		));
		$password = Security::hash('password', 'blowfish');
		$User = ClassRegistry::init('User');
		$User->updateAll(array('password' => $User->getDataSource()->value($password)));
		$this->response = $this->getMock('Cake\Network\Response');

		$hash = Security::hash('password', 'blowfish');
		$this->skipIf(strpos($hash, '$2a$') === false, 'Skipping blowfish tests as hashing is not working');
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	public function testConstructor() {
		$Object = new BlowfishAuthenticate($this->Collection, array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'password')
		));
		$this->assertEquals('AuthUser', $Object->settings['userModel']);
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $Object->settings['fields']);
	}

/**
 * testAuthenticateNoData method
 *
 * @return void
 */
	public function testAuthenticateNoData() {
		$request = new Request('posts/index', false);
		$request->data = array();
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testAuthenticateNoUsername method
 *
 * @return void
 */
	public function testAuthenticateNoUsername() {
		$request = new Request('posts/index', false);
		$request->data = array('User' => array('password' => 'foobar'));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testAuthenticateNoPassword method
 *
 * @return void
 */
	public function testAuthenticateNoPassword() {
		$request = new Request('posts/index', false);
		$request->data = array('User' => array('user' => 'mariano'));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testAuthenticatePasswordIsFalse method
 *
 * @return void
 */
	public function testAuthenticatePasswordIsFalse() {
		$request = new Request('posts/index', false);
		$request->data = array(
			'User' => array(
				'user' => 'mariano',
				'password' => null
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testAuthenticateInjection method
 *
 * @return void
 */
	public function testAuthenticateInjection() {
		$request = new Request('posts/index', false);
		$request->data = array('User' => array(
			'user' => '> 1',
			'password' => "' OR 1 = 1"
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testAuthenticateSuccess method
 *
 * @return void
 */
	public function testAuthenticateSuccess() {
		$request = new Request('posts/index', false);
		$request->data = array('User' => array(
			'user' => 'mariano',
			'password' => 'password'
		));
		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'user' => 'mariano',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31',
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testAuthenticateScopeFail method
 *
 * @return void
 */
	public function testAuthenticateScopeFail() {
		$this->auth->settings['scope'] = array('user' => 'nate');
		$request = new Request('posts/index', false);
		$request->data = array('User' => array(
			'user' => 'mariano',
			'password' => 'password'
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * testPluginModel method
 *
 * @return void
 */
	public function testPluginModel() {
		Cache::delete('object_map', '_cake_core_');
		Plugin::load('TestPlugin');

		$PluginModel = ClassRegistry::init('TestPlugin.TestPluginAuthUser');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$user['password'] = Security::hash('password', 'blowfish');
		$PluginModel->save($user, false);

		$this->auth->settings['userModel'] = 'TestPlugin.TestPluginAuthUser';
		$this->auth->settings['fields']['username'] = 'username';

		$request = new Request('posts/index', false);
		$request->data = array('TestPluginAuthUser' => array(
			'username' => 'gwoo',
			'password' => 'password'
		));

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'gwoo',
			'created' => '2007-03-17 01:16:23'
		);
		$this->assertEquals(static::date(), $result['updated']);
		unset($result['updated']);
		$this->assertEquals($expected, $result);
		Plugin::unload();
	}
}
