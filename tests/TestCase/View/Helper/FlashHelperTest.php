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
			'Message' => array(
				'flash' => array(
					'key' => 'flash',
					'message' => 'This is a calling',
					'element' => null,
					'class' => 'info',
					'params' => array()
				),
				'notification' => array(
					'key' => 'notification',
					'message' => 'This is a test of the emergency broadcasting system',
					'element' => 'flash_helper',
					'class' => 'info',
					'params' => array(
						'title' => 'Notice!',
						'name' => 'Alert!'
					)
				),
				'classy' => array(
					'key' => 'classy',
					'message' => 'Recorded',
					'element' => null,
					'class' => 'positive',
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
		$_SESSION = [];
		unset($this->View, $this->Session);
		Plugin::unload();
		parent::tearDown();
	}

/**
 * testFlash method
 *
 * @return void
 */
	public function testFlash() {
		$result = $this->Flash->render();
		$expected = '<div id="flash-message" class="message-info">This is a calling</div>';
		$this->assertEquals($expected, $result);

		$expected = '<div id="classy-message" class="message-positive">Recorded</div>';
		$result = $this->Flash->render('classy');
		$this->assertEquals($expected, $result);

		$result = $this->Flash->render('notification');
		
		$children = [
			['tag' => 'h1', 'content' => 'Alert!'],
			['tag' => 'h3', 'content' => 'Notice!'],
			['tag' => 'p', 'content' => 'This is a test of the emergency broadcasting system']
		];

		$expected = [
			'tag' => 'div',
			'id' => 'notificationLayout',
			'child' => []
		];

		$expected['child'] = ['tag' => 'h1', 'content' => 'Alert!'];
		$this->assertTag($expected, $result);

		$expected['child'] = ['tag' => 'h3', 'content' => 'Notice!'];
		$this->assertTag($expected, $result);

		$expected['child'] = ['tag' => 'p', 'content' => 'This is a test of the emergency broadcasting system'];
		$this->assertTag($expected, $result);
	}

/**
 * test flash() with the attributes.
 *
 * @return void
 */
	public function testFlashAttributes() {
		$result = $this->Flash->render('flash', array('class' => 'crazy'));
		$expected = '<div id="flash-message" class="message-crazy">This is a calling</div>';
		$this->assertEquals($expected, $result);
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

		$children = [
			['tag' => 'h1', 'content' => 'Alert!'],
			['tag' => 'h3', 'content' => 'Notice!'],
			['tag' => 'p', 'content' => 'This is a test of the emergency broadcasting system']
		];

		$expected = [
			'tag' => 'div',
			'id' => 'notificationLayout',
			'child' => []
		];

		$expected['child'] = ['tag' => 'h1', 'content' => 'Alert!'];
		$this->assertTag($expected, $result);

		$expected['child'] = ['tag' => 'h3', 'content' => 'Notice!'];
		$this->assertTag($expected, $result);

		$expected['child'] = ['tag' => 'p', 'content' => 'This is a test of the emergency broadcasting system'];
		$this->assertTag($expected, $result);
	}

/**
 * test using elements in plugins.
 *
 * @return void
 */
	public function testFlashWithPluginElement() {
		Plugin::load('TestPlugin');

		$result = $this->Flash->render('flash', array('element' => 'TestPlugin.plugin_element'));
		$expected = 'this is the plugin element using params[plugin]';
		$this->assertEquals($expected, $result);
	}
}
