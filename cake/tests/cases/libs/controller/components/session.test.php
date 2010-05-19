<?php
/**
 * SessionComponentTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Controller', 'Controller', false);
App::import('Component', 'Session');

/**
 * SessionTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class SessionTestController extends Controller {

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * session_id method
 *
 * @return string
 * @access public
 */
	function session_id() {
		return $this->Session->id();
	}
}

/**
 * OrangeSessionTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class OrangeSessionTestController extends Controller {

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * session_id method
 *
 * @return string
 * @access public
 */
	function session_id() {
		return $this->Session->id();
	}
}

/**
 * SessionComponentTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class SessionComponentTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_session = Configure::read('Session');
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('Session', $this->_session);
	}

/**
 * testSessionAutoStart method
 *
 * @access public
 * @return void
 */
	function testSessionAutoStart() {
		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$this->assertFalse($Session->__active);
		$this->assertFalse($Session->started());
		$Session->startup(new SessionTestController());

		Configure::write('Session.start', true);
		$Session =& new SessionComponent();
		$this->assertTrue($Session->__active);
		$this->assertFalse($Session->started());
		$Session->startup(new SessionTestController());
		$this->assertTrue(isset($_SESSION));

		$Object = new Object();
		$Session =& new SessionComponent();
		$Session->start();
		$expected = $Session->id();

		$result = $Object->requestAction('/session_test/session_id');
		$this->assertEqual($result, $expected);

		$result = $Object->requestAction('/orange_session_test/session_id');
		$this->assertEqual($result, $expected);
	}

/**
 * testSessionActivate method
 *
 * @access public
 * @return void
 */
	function testSessionActivate() {
		$Session =& new SessionComponent();

		$this->assertTrue($Session->__active);
		$this->assertNull($Session->activate());
		$this->assertTrue($Session->__active);

		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$this->assertFalse($Session->__active);
		$this->assertNull($Session->activate());
		$this->assertTrue($Session->__active);
		Configure::write('Session.start', true);
		$Session->destroy();
	}

/**
 * testSessionValid method
 *
 * @access public
 * @return void
 */
	function testSessionValid() {
		$Session =& new SessionComponent();

		$this->assertTrue($Session->valid());

		$Session->_userAgent = 'rweerw';
		$this->assertFalse($Session->valid());

		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$this->assertFalse($Session->__active);
		$this->assertFalse($Session->valid());
		Configure::write('Session.start', true);

		$Session =& new SessionComponent();
		$Session->time = $Session->read('Config.time') + 1;
		$this->assertFalse($Session->valid());

		Configure::write('Session.checkAgent', false);
		$Session =& new SessionComponent();
		$Session->time = $Session->read('Config.time') + 1;
		$this->assertFalse($Session->valid());
		Configure::write('Session.checkAgent', true);
	}

/**
 * testSessionError method
 *
 * @access public
 * @return void
 */
	function testSessionError() {
		$Session =& new SessionComponent();

		$this->assertFalse($Session->error());

		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$this->assertFalse($Session->__active);
		$this->assertFalse($Session->error());
		Configure::write('Session.start', true);
	}

/**
 * testSessionReadWrite method
 *
 * @access public
 * @return void
 */
	function testSessionReadWrite() {
		$Session =& new SessionComponent();

		$this->assertFalse($Session->read('Test'));

		$this->assertTrue($Session->write('Test', 'some value'));
		$this->assertEqual($Session->read('Test'), 'some value');
		$this->assertFalse($Session->write('Test.key', 'some value'));
		$Session->delete('Test');

		$this->assertTrue($Session->write('Test.key.path', 'some value'));
		$this->assertEqual($Session->read('Test.key.path'), 'some value');
		$this->assertEqual($Session->read('Test.key'), array('path' => 'some value'));
		$this->assertTrue($Session->write('Test.key.path2', 'another value'));
		$this->assertEqual($Session->read('Test.key'), array('path' => 'some value', 'path2' => 'another value'));
		$Session->delete('Test');

		$array = array('key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3');
		$this->assertTrue($Session->write('Test', $array));
		$this->assertEqual($Session->read('Test'), $array);
		$Session->delete('Test');

		$this->assertFalse($Session->write(array('Test'), 'some value'));
		$this->assertTrue($Session->write(array('Test' => 'some value')));
		$this->assertEqual($Session->read('Test'), 'some value');
		$Session->delete('Test');

		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$this->assertFalse($Session->write('Test', 'some value'));
		$Session->write('Test', 'some value');
		$this->assertFalse($Session->read('Test'));
		Configure::write('Session.start', true);
	}

/**
 * testSessionDelete method
 *
 * @access public
 * @return void
 */
	function testSessionDelete() {
		$Session =& new SessionComponent();

		$this->assertFalse($Session->delete('Test'));

		$Session->write('Test', 'some value');
		$this->assertTrue($Session->delete('Test'));

		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$Session->write('Test', 'some value');
		$this->assertFalse($Session->delete('Test'));
		Configure::write('Session.start', true);
	}

/**
 * testSessionCheck method
 *
 * @access public
 * @return void
 */
	function testSessionCheck() {
		$Session =& new SessionComponent();

		$this->assertFalse($Session->check('Test'));

		$Session->write('Test', 'some value');
		$this->assertTrue($Session->check('Test'));
		$Session->delete('Test');

		Configure::write('Session.start', false);
		$Session =& new SessionComponent();
		$Session->write('Test', 'some value');
		$this->assertFalse($Session->check('Test'));
		Configure::write('Session.start', true);
	}

/**
 * testSessionFlash method
 *
 * @access public
 * @return void
 */
	function testSessionFlash() {
		$Session =& new SessionComponent();

		$this->assertNull($Session->read('Message.flash'));

		$Session->setFlash('This is a test message');
		$this->assertEqual($Session->read('Message.flash'), array('message' => 'This is a test message', 'element' => 'default', 'params' => array()));

		$Session->setFlash('This is a test message', 'test', array('name' => 'Joel Moss'));
		$this->assertEqual($Session->read('Message.flash'), array('message' => 'This is a test message', 'element' => 'test', 'params' => array('name' => 'Joel Moss')));

		$Session->setFlash('This is a test message', 'default', array(), 'myFlash');
		$this->assertEqual($Session->read('Message.myFlash'), array('message' => 'This is a test message', 'element' => 'default', 'params' => array()));

		$Session->setFlash('This is a test message', 'non_existing_layout');
		$this->assertEqual($Session->read('Message.myFlash'), array('message' => 'This is a test message', 'element' => 'default', 'params' => array()));

		$Session->delete('Message');
	}

/**
 * testSessionId method
 *
 * @access public
 * @return void
 */
	function testSessionId() {
		unset($_SESSION);
		$Session =& new SessionComponent();
		$this->assertNull($Session->id());
	}

/**
 * testSessionDestroy method
 *
 * @access public
 * @return void
 */
	function testSessionDestroy() {
		$Session =& new SessionComponent();

		$Session->write('Test', 'some value');
		$this->assertEqual($Session->read('Test'), 'some value');
		$Session->destroy('Test');
		$this->assertNull($Session->read('Test'));
	}
	
/**
 * testSessionTimeout method
 *
 * @access public
 * @return void
 */
	function testSessionTimeout() {

		session_destroy();
		Configure::write('Security.level', 'low');
		$Session =& new SessionComponent();
		$Session->write('Test', 'some value');
		$this->assertEqual($_SESSION['Config']['timeout'], Security::inactiveMins());
		$this->assertEqual($_SESSION['Config']['time'], $Session->sessionTime);
		$this->assertEqual($Session->time, mktime());
		$this->assertEqual($_SESSION['Config']['time'], $Session->time + (Security::inactiveMins() * Configure::read('Session.timeout')));

		session_destroy();
		Configure::write('Security.level', 'medium');
		$Session =& new SessionComponent();
		$Session->write('Test', 'some value');
		$this->assertEqual($_SESSION['Config']['timeout'], Security::inactiveMins());
		$this->assertEqual($_SESSION['Config']['time'], $Session->sessionTime);
		$this->assertEqual($Session->time, mktime());
		$this->assertEqual($_SESSION['Config']['time'], $Session->time + (Security::inactiveMins() * Configure::read('Session.timeout')));
		
		session_destroy();
		Configure::write('Security.level', 'high');
		$Session =& new SessionComponent();
		$Session->write('Test', 'some value');
		$this->assertEqual($_SESSION['Config']['timeout'], Security::inactiveMins());
		$this->assertEqual($_SESSION['Config']['time'], $Session->sessionTime);
		$this->assertEqual($Session->time, mktime());
		$this->assertEqual($_SESSION['Config']['time'], $Session->time + (Security::inactiveMins() * Configure::read('Session.timeout')));

	}
}
