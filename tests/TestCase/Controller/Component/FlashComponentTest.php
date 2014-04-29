<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;

/**
 * FlashComponentTest class
 *
 */
class FlashComponentTest extends TestCase {

	protected static $_sessionBackup;

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = array('core.session');

/**
 * test case startup
 *
 * @return void
 */
	public static function setupBeforeClass() {
		static::$_sessionBackup = Configure::read('Session');
		Configure::write('Session', array(
			'defaults' => 'php',
			'timeout' => 100,
			'cookie' => 'test'
		));
	}

/**
 * cleanup after test case.
 *
 * @return void
 */
	public static function teardownAfterClass() {
		Configure::write('Session', static::$_sessionBackup);
	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$_SESSION = null;
		Configure::write('App.namespace', 'TestApp');
		$this->ComponentRegistry = new ComponentRegistry();
		$this->Controller = $this->getMock('\Cake\Controller\Controller', ['log', 'referer', 'redirect']);
		$this->Flash = new FlashComponent($this->ComponentRegistry);
		$this->Flash->startup(new Event('Controller.startup', $this->Controller));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Session::destroy();
	}

/**
 * testSet method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSet() {
		$this->assertNull(Session::read('Message.flash'));

		$this->Flash->set('This is a test message');
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'notice']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', ['foo' => 'bar', 'element' => 'test']);
		$expected = ['message' => 'This is a test message', 'element' => 'test', 'params' => ['foo' => 'bar', 'type' => 'notice']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', ['element' => 'MyPlugin.alert']);
		$expected = ['message' => 'This is a test message', 'element' => 'alert', 'params' => ['type' => 'notice', 'plugin' => 'MyPlugin']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', ['key' => 'foobar']);
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'notice']];
		$result = Session::read('Message.foobar');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', 'error');
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'error']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithException method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSetWithException() {
		$this->Flash->set(new \Exception('This is a test message'));
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'error']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithTemplate method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSetWithTemplate() {
		$this->Flash->set('create.failure');
		$expected = ['message' => __d('cake', 'There was a problem creating your record, fix the error(s) and try again.'), 'element' => 'default', 'params' => ['type' => 'error']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('Hello {{username}}', ['username' => 'foobar']);
		$expected = ['message' => 'Hello foobar', 'element' => 'default', 'params' => ['type' => 'notice', 'username' => 'foobar']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithLog method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSetWithLog() {
		$this->Controller->expects($this->exactly(2))
			->method('log')
			->with('foobar tried accessing record #123.', 'notice', []);

		$this->Flash->set('Un-authorized access', ['id' => '123', 'username' => 'foobar', 'log' => '{{username}} tried accessing {{modelName}} #{{id}}.']);
		$expected = ['message' => 'Un-authorized access', 'element' => 'default', 'params' => ['type' => 'notice', 'username' => 'foobar', 'id' => '123']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);

		$user = ['id' => '321', 'name' => 'foobar'];
		$object = ['id' => '123', 'name' => 'Bar Foo'];
		$callback = function() use ($user, $object) {
			$this->Controller->log(sprintf('%s tried accessing record #%s.', $user['name'], $object['id']), 'notice', []);
		};

		$this->Flash->set('Un-authorized access', ['log' => $callback]);
		$expected = ['message' => 'Un-authorized access', 'element' => 'default', 'params' => ['type' => 'notice']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithRedirect method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSetWithRedirect() {
		$this->Controller->expects($this->once())
			->method('referer')
			->with()
			->will($this->returnValue('http://foo.bar'));
		$this->Controller->expects($this->exactly(2))
			->method('redirect')
			->with('http://foo.bar');

		$this->Flash->set('This is a test message', ['redirect' => true]);
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'notice']];
		$result = Session::read('Message.flash');

		$this->Flash->set('This is a test message', ['redirect' => 'http://foo.bar']);
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'notice']];
		$result = Session::read('Message.flash');

		Session::delete('Message');
	}

/**
 * testCall method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::__call
 */
	public function testCall() {
		$this->assertNull(Session::read('Message.flash'));

		$this->Flash->error('This is a test message');
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'error']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->customType('This is a test message');
		$expected = ['message' => 'This is a test message', 'element' => 'default', 'params' => ['type' => 'customType']];
		$result = Session::read('Message.flash');
		$this->assertEquals($expected, $result);
	}
}
