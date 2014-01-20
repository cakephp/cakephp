<?php
/**
 * SessionComponentTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('SessionComponent', 'Controller/Component');

/**
 * SessionTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class SessionTestController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * sessionId method
 *
 * @return string
 */
	public function sessionId() {
		return $this->Session->id();
	}

}

/**
 * OrangeSessionTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class OrangeSessionTestController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * sessionId method
 *
 * @return string
 */
	public function sessionId() {
		return $this->Session->id();
	}

}

/**
 * SessionComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class SessionComponentTest extends CakeTestCase {

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
		self::$_sessionBackup = Configure::read('Session');
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
		Configure::write('Session', self::$_sessionBackup);
	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$_SESSION = null;
		$this->ComponentCollection = new ComponentCollection();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakeSession::destroy();
	}

/**
 * ensure that session ids don't change when request action is called.
 *
 * @return void
 */
	public function testSessionIdConsistentAcrossRequestAction() {
		$Object = new Object();
		$Session = new SessionComponent($this->ComponentCollection);
		$expected = $Session->id();

		$result = $Object->requestAction('/session_test/sessionId');
		$this->assertEquals($expected, $result);

		$result = $Object->requestAction('/orange_session_test/sessionId');
		$this->assertEquals($expected, $result);
	}

/**
 * testSessionValid method
 *
 * @return void
 */
	public function testSessionValid() {
		$Session = new SessionComponent($this->ComponentCollection);

		$this->assertTrue($Session->valid());

		Configure::write('Session.checkAgent', true);
		$Session->userAgent('rweerw');
		$this->assertFalse($Session->valid());

		$Session = new SessionComponent($this->ComponentCollection);
		$Session->time = $Session->read('Config.time') + 1;
		$this->assertFalse($Session->valid());
	}

/**
 * testSessionError method
 *
 * @return void
 */
	public function testSessionError() {
		CakeSession::$lastError = null;
		$Session = new SessionComponent($this->ComponentCollection);
		$this->assertFalse($Session->error());
	}

/**
 * testSessionReadWrite method
 *
 * @return void
 */
	public function testSessionReadWrite() {
		$Session = new SessionComponent($this->ComponentCollection);

		$this->assertNull($Session->read('Test'));

		$this->assertTrue($Session->write('Test', 'some value'));
		$this->assertEquals('some value', $Session->read('Test'));
		$Session->delete('Test');

		$this->assertTrue($Session->write('Test.key.path', 'some value'));
		$this->assertEquals('some value', $Session->read('Test.key.path'));
		$this->assertEquals(array('path' => 'some value'), $Session->read('Test.key'));
		$this->assertTrue($Session->write('Test.key.path2', 'another value'));
		$this->assertEquals(array('path' => 'some value', 'path2' => 'another value'), $Session->read('Test.key'));
		$Session->delete('Test');

		$array = array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3');
		$this->assertTrue($Session->write('Test', $array));
		$this->assertEquals($Session->read('Test'), $array);
		$Session->delete('Test');

		$this->assertTrue($Session->write(array('Test'), 'some value'));
		$this->assertTrue($Session->write(array('Test' => 'some value')));
		$this->assertEquals('some value', $Session->read('Test'));
		$Session->delete('Test');
	}

/**
 * testSessionDelete method
 *
 * @return void
 */
	public function testSessionDelete() {
		$Session = new SessionComponent($this->ComponentCollection);

		$this->assertFalse($Session->delete('Test'));

		$Session->write('Test', 'some value');
		$this->assertTrue($Session->delete('Test'));
	}

/**
 * testSessionCheck method
 *
 * @return void
 */
	public function testSessionCheck() {
		$Session = new SessionComponent($this->ComponentCollection);

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
		$Session = new SessionComponent($this->ComponentCollection);

		$this->assertNull($Session->read('Message.flash'));

		$Session->setFlash('This is a test message');
		$this->assertEquals(array('message' => 'This is a test message', 'element' => 'default', 'params' => array()), $Session->read('Message.flash'));

		$Session->setFlash('This is a test message', 'test', array('name' => 'Joel Moss'));
		$this->assertEquals(array('message' => 'This is a test message', 'element' => 'test', 'params' => array('name' => 'Joel Moss')), $Session->read('Message.flash'));

		$Session->setFlash('This is a test message', 'default', array(), 'myFlash');
		$this->assertEquals(array('message' => 'This is a test message', 'element' => 'default', 'params' => array()), $Session->read('Message.myFlash'));

		$Session->setFlash('This is a test message', 'non_existing_layout');
		$this->assertEquals(array('message' => 'This is a test message', 'element' => 'default', 'params' => array()), $Session->read('Message.myFlash'));

		$Session->delete('Message');
	}

/**
 * testSessionId method
 *
 * @return void
 */
	public function testSessionId() {
		unset($_SESSION);
		$Session = new SessionComponent($this->ComponentCollection);
		CakeSession::start();
		$this->assertEquals(session_id(), $Session->id());
	}

/**
 * testSessionDestroy method
 *
 * @return void
 */
	public function testSessionDestroy() {
		$Session = new SessionComponent($this->ComponentCollection);

		$Session->write('Test', 'some value');
		$this->assertEquals('some value', $Session->read('Test'));
		$Session->destroy('Test');
		$this->assertNull($Session->read('Test'));
	}

}
