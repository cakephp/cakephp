<?php
/**
 * SessionHelperTest file
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\SessionHelper;
use Cake\View\View;

/**
 * SessionHelperTest class
 *
 */
class SessionHelperTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View();
		$this->Session = new SessionHelper($this->View);
		Session::start();

		if (!Session::started()) {
			Session::start();
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
		Plugin::unload();
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
		$expected = '<div id="flash-message" class="message">This is a calling</div>';
		$this->assertEquals($expected, $result);
		$this->assertFalse($this->Session->check('Message.flash'));

		$expected = '<div id="classy-message" class="positive">Recorded</div>';
		$result = $this->Session->flash('classy');
		$this->assertEquals($expected, $result);

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
		$expected = '<div id="flash-message" class="test-message">This is a calling</div>';
		$this->assertEquals($expected, $result);
		$this->assertFalse($this->Session->check('Message.flash'));
	}

/**
 * test setting the element from the attrs.
 *
 * @return void
 */
	public function testFlashElementInAttrs() {
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
		Plugin::load('TestPlugin');

		$result = $this->Session->flash('flash', array('element' => 'TestPlugin.plugin_element'));
		$expected = 'this is the plugin element using params[plugin]';
		$this->assertEquals($expected, $result);
	}
}
