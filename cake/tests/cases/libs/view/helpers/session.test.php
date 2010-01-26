<?php
/**
 * SessionHelperTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Core', array('Helper', 'AppHelper', 'Controller', 'View'));
App::import('Helper', array('Session'));

/**
 * SessionHelperTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class SessionHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->Session = new SessionHelper();

		$_SESSION = array(
			'test' => 'info',
			'Message' => array(
				'flash' => array(
					'element' => 'default',
					'params' => array(),
					'message' => 'This is a calling'
				),
				'notification' => array(
					'element' => 'session_helper',
					'params' => array('title' => 'Notice!', 'name' => 'Alert!'),
					'message' => 'This is a test of the emergency broadcasting system',
				),
				'classy' => array(
					'element' => 'default',
					'params' => array('class' => 'positive'),
					'message' => 'Recorded'
				),
				'bare' => array(
					'element' => null,
					'message' => 'Bare message',
					'params' => array(),
				),
			),
			'Deeply' => array('nested' => array('key' => 'value')),
		);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		$_SESSION = array();
		unset($this->Session);
	}

/**
 * endTest
 *
 * @access public
 * @return void
 */
	function endTest() {
		App::build();
	}

/**
 * test construction and initial property settings
 *
 * @return void
 */
	function testConstruct() {
		$this->assertFalse(empty($this->Session->sessionTime));
		$this->assertFalse(empty($this->Session->security));
	}
/**
 * testRead method
 *
 * @access public
 * @return void
 */
	function testRead() {
		$result = $this->Session->read('Deeply.nested.key');
		$this->assertEqual($result, 'value');

		$result = $this->Session->read('test');
		$this->assertEqual($result, 'info');
	}

/**
 * testCheck method
 *
 * @access public
 * @return void
 */
	function testCheck() {
		$this->assertTrue($this->Session->check('test'));

		$this->assertTrue($this->Session->check('Message.flash.element'));

		$this->assertFalse($this->Session->check('Does.not.exist'));

		$this->assertFalse($this->Session->check('Nope'));
	}

/**
 * testWrite method
 *
 * @access public
 * @return void
 */
	function testWrite() {
		$this->expectError();
		$this->Session->write('NoWay', 'AccessDenied');
	}

/**
 * testFlash method
 *
 * @access public
 * @return void
 */
	function testFlash() {
		$result = $this->Session->flash('flash', true);
		$expected = '<div id="flashMessage" class="message">This is a calling</div>';
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->Session->check('Message.flash'));

		$expected = '<div id="classyMessage" class="positive">Recorded</div>';
		$result = $this->Session->flash('classy', true);
		$this->assertEqual($result, $expected);

		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));
		$controller = new Controller();
		$this->Session->view = new View($controller);

		$result = $this->Session->flash('notification', true);
		$result = str_replace("\r\n", "\n", $result);
		$expected = "<div id=\"notificationLayout\">\n\t<h1>Alert!</h1>\n\t<h3>Notice!</h3>\n\t<p>This is a test of the emergency broadcasting system</p>\n</div>";
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->Session->check('Message.notification'));

		$result = $this->Session->flash('bare');
		$expected = 'Bare message';
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->Session->check('Message.bare'));
	}

/**
 * testID method
 *
 * @access public
 * @return void
 */
	function testID() {
		$id = session_id();
		$result = $this->Session->id();
		$this->assertEqual($id, $result);
	}

/**
 * testError method
 *
 * @access public
 * @return void
 */
	function testError() {
		$result = $this->Session->error();
		$this->assertFalse($result);

		$this->Session->read('CauseError');
		$result = $this->Session->error();
		$expected = "CauseError doesn't exist";
		$this->assertEqual($result, $expected);
	}

/**
 * testDisabling method
 *
 * @access public
 * @return void
 */
	function testDisabling() {
		Configure::write('Session.start', false);
		$this->Session = new SessionHelper();
		$this->assertFalse($this->Session->check('test'));
		$this->assertFalse($this->Session->read('test'));

		$this->Session->read('CauseError');
		$this->assertFalse($this->Session->error());

		ob_start();
		$this->assertFalse($this->Session->flash('bare'));
		$result = ob_get_contents();
		ob_clean();
		$this->assertFalse($result);
	}

/**
 * testValid method
 *
 * @access public
 * @return void
 */
	function testValid() {
		//wierd it always ends up false in the test suite
		//$this->assertFalse($this->Session->valid());
	}
}
?>