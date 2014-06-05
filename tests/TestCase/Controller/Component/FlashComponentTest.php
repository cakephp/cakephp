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
use Cake\Network\Request;
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
		$_SESSION = [];
		Configure::write('App.namespace', 'TestApp');
		$controller = new Controller(new Request(['session' => new Session()]));
		$this->ComponentRegistry = new ComponentRegistry($controller);
		$this->Flash = new FlashComponent($this->ComponentRegistry);
		$this->Session = new Session();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		$this->Session->destroy();
	}

/**
 * testSet method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSet() {
		$this->assertNull($this->Session->read('Message.flash'));

		$this->Flash->set('This is a test message');
		$expected = [
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => null,
			'class' => 'info',
			'params' => []
		];
		$result = $this->Session->read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', ['element' => 'test', 'params' => ['foo' => 'bar']]);
		$expected = [
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'test',
			'class' => 'info',
			'params' => ['foo' => 'bar']
		];
		$result = $this->Session->read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', ['element' => 'MyPlugin.alert']);
		$expected = [
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'MyPlugin.alert',
			'class' => 'info',
			'params' => []
		];
		$result = $this->Session->read('Message.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', ['key' => 'foobar']);
		$expected = [
			'message' => 'This is a test message',
			'key' => 'foobar',
			'element' => null,
			'class' => 'info',
			'params' => []
		];
		$result = $this->Session->read('Message.foobar');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithException method
 *
 * @return void
 * @covers \Cake\Controller\Component\FlashComponent::set
 */
	public function testSetWithException() {
		$this->assertNull($this->Session->read('Message.flash'));

		$this->Flash->set(new \Exception('This is a test message'));
		$expected = [
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => null,
			'class' => 'info',
			'params' => []
		];
		$result = $this->Session->read('Message.flash');
		$this->assertEquals($expected, $result);
	}
}
