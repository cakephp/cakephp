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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\SessionComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * SessionComponentTest class
 *
 */
class SessionComponentTest extends TestCase {

	protected static $_sessionBackup;

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = array('core.sessions');

/**
 * test case startup
 *
 * @return void
 */
	public static function setupBeforeClass() {
		DispatcherFactory::add('Routing');
		DispatcherFactory::add('ControllerFactory');
	}

/**
 * cleanup after test case.
 *
 * @return void
 */
	public static function teardownAfterClass() {
		DispatcherFactory::clear();
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
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 * ensure that session ids don't change when request action is called.
 *
 * @return void
 */
	public function testSessionIdConsistentAcrossRequestAction() {
		Configure::write('App.namespace', 'TestApp');
		Router::connect('/session_test/:action', ['controller' => 'SessionTest']);
		Router::connect('/orange_session_test/:action', ['controller' => 'OrangeSessionTest']);

		$Controller = new Controller();
		$Session = new SessionComponent($this->ComponentRegistry);
		$expected = $Session->id();

		$result = $Controller->requestAction('/session_test/session_id');
		$this->assertEquals($expected, $result);

		$result = $Controller->requestAction('/orange_session_test/session_id');
		$this->assertEquals($expected, $result);
	}

/**
 * testSessionReadWrite method
 *
 * @return void
 */
	public function testSessionReadWrite() {
		$Session = new SessionComponent($this->ComponentRegistry);

		$this->assertNull($Session->read('Test'));

		$Session->write('Test', 'some value');
		$this->assertEquals('some value', $Session->read('Test'));
		$Session->delete('Test');

		$Session->write('Test.key.path', 'some value');
		$this->assertEquals('some value', $Session->read('Test.key.path'));
		$this->assertEquals(array('path' => 'some value'), $Session->read('Test.key'));
		$Session->write('Test.key.path2', 'another value');
		$this->assertEquals(array('path' => 'some value', 'path2' => 'another value'), $Session->read('Test.key'));
		$Session->delete('Test');

		$array = array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3');
		$Session->write('Test', $array);
		$this->assertEquals($Session->read('Test'), $array);
		$Session->delete('Test');

		$Session->write(array('Test'), 'some value');
		$Session->write(array('Test' => 'some value'));
		$this->assertEquals('some value', $Session->read('Test'));
		$Session->delete('Test');
	}

/**
 * testSessionDelete method
 *
 * @return void
 */
	public function testSessionDelete() {
		$Session = new SessionComponent($this->ComponentRegistry);

		$Session->write('Test', 'some value');
		$Session->delete('Test');
		$this->assertNull($Session->read('Test'));
	}

/**
 * testSessionCheck method
 *
 * @return void
 */
	public function testSessionCheck() {
		$Session = new SessionComponent($this->ComponentRegistry);

		$this->assertFalse($Session->check('Test'));

		$Session->write('Test', 'some value');
		$this->assertTrue($Session->check('Test'));
		$Session->delete('Test');
	}

/**
 * testSessionFlash method
 *
 * @return void
 */
	public function testSessionFlash() {
		$Session = new SessionComponent($this->ComponentRegistry);

		$this->assertNull($Session->read('Flash.flash'));

		$Session->setFlash('This is a test message');
		$this->assertEquals(array(
				'message' => 'This is a test message',
				'element' => null,
				'params' => array(),
				'key' => 'flash'
			), $Session->read('Flash.flash'));
	}

/**
 * testSessionId method
 *
 * @return void
 */
	public function testSessionId() {
		$Session = new SessionComponent($this->ComponentRegistry);
		$this->assertEquals(session_id(), $Session->id());
	}

/**
 * testSessionDestroy method
 *
 * @return void
 */
	public function testSessionDestroy() {
		$Session = new SessionComponent($this->ComponentRegistry);

		$Session->write('Test', 'some value');
		$this->assertEquals('some value', $Session->read('Test'));
		$Session->destroy('Test');
		$this->assertNull($Session->read('Test'));
	}

}
