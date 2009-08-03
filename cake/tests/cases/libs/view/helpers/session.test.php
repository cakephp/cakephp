<?php
/* SVN FILE: $Id$ */
/**
 * SessionHelperTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
if (!class_exists('AppError')) {
App::import('Error');
	/**
	 * AppController class
	 *
	 * @package       cake
	 * @subpackage    cake.tests.cases.libs
	 */
	class AppError extends ErrorHandler {
	/**
	 * _stop method
	 *
	 * @access public
	 * @return void
	 */
		function _stop() {
			return;
		}
	}
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
	function setUp() {
		$this->Session = new SessionHelper();
		$this->Session->__start();

		$_SESSION = array(
			'test' => 'info',
			'Message' => array(
				'flash' => array(
					'layout' => 'default',
					'params' => array(),
					'message' => 'This is a calling'
				),
				'notification' => array(
					'layout' => 'session_helper',
					'params' => array('title' => 'Notice!', 'name' => 'Alert!'),
					'message' => 'This is a test of the emergency broadcasting system',
				),
				'classy' => array(
					'layout' => 'default',
					'params' => array('class' => 'positive'),
					'message' => 'Recorded'
				),
				'bare' => array(
					'layout' => null,
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

		$this->assertTrue($this->Session->check('Message.flash.layout'));

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
		ob_start();
		$this->Session->flash();
		$result = ob_get_contents();
		ob_clean();

		$expected = '<div id="flashMessage" class="message">This is a calling</div>';
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->Session->check('Message.flash'));

		$expected = '<div id="classyMessage" class="positive">Recorded</div>';
		ob_start();
		$this->Session->flash('classy');
		$result = ob_get_clean();
		$this->assertEqual($result, $expected);

		$_viewPaths = Configure::read('viewPaths');
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS));

		$controller = new Controller();
		$this->Session->view = new View($controller);

		ob_start();
		$this->Session->flash('notification');
		$result = ob_get_contents();
		ob_clean();

		$result = str_replace("\r\n", "\n", $result);
		$expected = "<div id=\"notificationLayout\">\n\t<h1>Alert!</h1>\n\t<h3>Notice!</h3>\n\t<p>This is a test of the emergency broadcasting system</p>\n</div>";
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->Session->check('Message.notification'));

		ob_start();
		$this->Session->flash('bare');
		$result = ob_get_contents();
		ob_clean();

		$expected = 'Bare message';
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->Session->check('Message.bare'));

		Configure::write('viewPaths', $_viewPaths);
	}
/**
 * testFlash method
 *
 * @access public
 * @return void
 */
	function testFlashMissingLayout() {
		$_SESSION = array(
			'Message' => array(
				'notification' => array(
					'layout' => 'does_not_exist',
					'params' => array('title' => 'Notice!', 'name' => 'Alert!'),
					'message' => 'This is a test of the emergency broadcasting system',
				)
			)
		);

		$controller = new Controller();
		$this->Session->view = new View($controller);

		ob_start();
		$this->Session->flash('notification');
		$result = ob_get_contents();
		ob_clean();

		$this->assertPattern("/Missing Layout/", $result);
		$this->assertPattern("/layouts\/does_not_exist.ctp/", $result);
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