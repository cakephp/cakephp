<?php
/**
 * SessionHelperTest file
 *
 * PHP 5
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
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('View', 'View');
App::uses('SessionHelper', 'View/Helper');

/**
 * SessionHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class SessionHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$controller = null;
		$this->View = new View($controller);
		$this->Session = new SessionHelper($this->View);
		CakeSession::start();

		if (!CakeSession::started()) {
			CakeSession::start();
		}

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
 * @return void
 */
	public function tearDown() {
		$_SESSION = array();
		unset($this->View, $this->Session);
		CakePlugin::unload();
		parent::tearDown();
	}

/**
 * testRead method
 *
 * @return void
 */
	public function testRead() {
		$result = $this->Session->read('Deeply.nested.key');
		$this->assertEquals('value', $result);

		$result = $this->Session->read('test');
		$this->assertEquals('info', $result);
	}

/**
 * testCheck method
 *
 * @return void
 */
	public function testCheck() {
		$this->assertTrue($this->Session->check('test'));

		$this->assertTrue($this->Session->check('Message.flash.element'));

		$this->assertFalse($this->Session->check('Does.not.exist'));

		$this->assertFalse($this->Session->check('Nope'));
	}

/**
 * testFlash method
 *
 * @return void
 */
	public function testFlash() {
		$result = $this->Session->flash('flash');
		$expected = '<div id="flashMessage" class="message">This is a calling</div>';
		$this->assertEquals($expected, $result);
		$this->assertFalse($this->Session->check('Message.flash'));

		$expected = '<div id="classyMessage" class="positive">Recorded</div>';
		$result = $this->Session->flash('classy');
		$this->assertEquals($expected, $result);

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$result = $this->Session->flash('notification');
		$result = str_replace("\r\n", "\n", $result);
		$expected = "<div id=\"notificationLayout\">\n\t<h1>Alert!</h1>\n\t<h3>Notice!</h3>\n\t<p>This is a test of the emergency broadcasting system</p>\n</div>";
		$this->assertEquals($expected, $result);
		$this->assertFalse($this->Session->check('Message.notification'));

		$result = $this->Session->flash('bare');
		$expected = 'Bare message';
		$this->assertEquals($expected, $result);
		$this->assertFalse($this->Session->check('Message.bare'));
	}

/**
 * test flash() with the attributes.
 *
 * @return void
 */
	public function testFlashAttributes() {
		$result = $this->Session->flash('flash', array('params' => array('class' => 'test-message')));
		$expected = '<div id="flashMessage" class="test-message">This is a calling</div>';
		$this->assertEquals($expected, $result);
		$this->assertFalse($this->Session->check('Message.flash'));
	}

/**
 * test setting the element from the attrs.
 *
 * @return void
 */
	public function testFlashElementInAttrs() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$result = $this->Session->flash('flash', array(
			'element' => 'session_helper',
			'params' => array('title' => 'Notice!', 'name' => 'Alert!')
		));
		$expected = "<div id=\"notificationLayout\">\n\t<h1>Alert!</h1>\n\t<h3>Notice!</h3>\n\t<p>This is a calling</p>\n</div>";
		$this->assertTextEquals($expected, $result);
	}

/**
 * test using elements in plugins.
 *
 * @return void
 */
	public function testFlashWithPluginElement() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');

		$result = $this->Session->flash('flash', array(
			'element' => 'plugin_element',
			'params' => array('plugin' => 'TestPlugin')
		));
		$expected = 'this is the plugin element using params[plugin]';
		$this->assertEquals($expected, $result);
	}
}
