<?php
/**
 * SessionHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('Controller', 'View'));
App::import('Helper', array('Session'));

/**
 * SessionHelperTest class
 *
 * @package       cake.tests.cases.libs.view.helpers
 */
class SessionHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$controller = null;
		$this->View = new View($controller);
		$this->Session = new SessionHelper($this->View);

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
		unset($this->View, $this->Session);
		parent::tearDown();
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

}
