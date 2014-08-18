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
namespace Cake\Test\TestCase\View\Helper;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\FlashHelper;
use Cake\View\View;

/**
 * FlashHelperTest class
 *
 */
class FlashHelperTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View();
		$session = new Session();
		$this->View->request = new Request(['session' => $session]);
		$this->Flash = new FlashHelper($this->View);

		$session->write(array(
			'Flash' => array(
				'flash' => array(
					'key' => 'flash',
					'message' => 'This is a calling',
					'element' => 'Flash/default',
					'params' => array()
				),
				'notification' => array(
					'key' => 'notification',
					'message' => 'This is a test of the emergency broadcasting system',
					'element' => 'flash_helper',
					'params' => array(
						'title' => 'Notice!',
						'name' => 'Alert!'
					)
				),
				'classy' => array(
					'key' => 'classy',
					'message' => 'Recorded',
					'element' => 'flash_classy',
					'params' => array()
				)
			)
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->View, $this->Flash);
	}

/**
 * testFlash method
 *
 * @return void
 */
	public function testFlash() {
		$result = $this->Flash->render();
		$expected = '<div class="message">This is a calling</div>';
		$this->assertContains($expected, $result);

		$expected = '<div id="classy-message">Recorded</div>';
		$result = $this->Flash->render('classy');
		$this->assertEquals($expected, $result);

		$result = $this->Flash->render('notification');
		$expected = [
			'div' => ['id' => 'notificationLayout'],
			'<h1', 'Alert!', '/h1',
			'<h3', 'Notice!', '/h3',
			'<p', 'This is a test of the emergency broadcasting system', '/p',
			'/div'
		];
		$this->assertHtml($expected, $result);
		$this->assertNull($this->Flash->render('non-existent'));
	}

/**
 * testFlashThrowsException
 *
 * @expectedException \UnexpectedValueException
 */
	public function testFlashThrowsException() {
		$this->View->request->session()->write('Flash.foo', 'bar');
		$this->Flash->render('foo');
	}

/**
 * test setting the element from the attrs.
 *
 * @return void
 */
	public function testFlashElementInAttrs() {
		$result = $this->Flash->render('notification', array(
			'element' => 'flash_helper',
			'params' => array('title' => 'Notice!', 'name' => 'Alert!')
		));

		$expected = [
			'div' => ['id' => 'notificationLayout'],
			'<h1', 'Alert!', '/h1',
			'<h3', 'Notice!', '/h3',
			'<p', 'This is a test of the emergency broadcasting system', '/p',
			'/div'
		];
		$this->assertHtml($expected, $result);
	}

/**
 * test using elements in plugins.
 *
 * @return void
 */
	public function testFlashWithPluginElement() {
		Plugin::load('TestPlugin');

		$result = $this->Flash->render('flash', array('element' => 'TestPlugin.Flash/plugin_element'));
		$expected = 'this is the plugin element';
		$this->assertEquals($expected, $result);
	}
}
