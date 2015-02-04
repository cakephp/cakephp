<?php
/**
 * FlashComponentTest file
 *
 * Series of tests for flash component.
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
 * @since         CakePHP(tm) v 2.7.0-dev
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('FlashComponent', 'Controller/Component');
App::uses('ComponentCollection', 'Controller');

/**
 * FlashComponentTest class
 *
 * @package		Cake.Test.Case.Controller.Component
 */
class FlashComponentTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Components = new ComponentCollection();
		$this->Flash = new FlashComponent($this->Components);
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
 * testSet method
 *
 * @return void
 */
	public function testSet() {
		$this->assertNull(CakeSession::read('Flash.flash'));

		$this->Flash->set('This is a test message');
		$expected = array(
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'Flash/default',
			'params' => array()
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', array(
			'element' => 'test',
			'params' => array('foo' => 'bar')
		));
		$expected = array(
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'Flash/test',
			'params' => array('foo' => 'bar')
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', array('element' => 'MyPlugin.alert'));
		$expected = array(
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'MyPlugin.Flash/alert',
			'params' => array()
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->set('This is a test message', array('key' => 'foobar'));
		$expected = array(
			'message' => 'This is a test message',
			'key' => 'foobar',
			'element' => 'Flash/default',
			'params' => array()
		);
		$result = CakeSession::read('Flash.foobar');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithException method
 *
 * @return void
 */
	public function testSetWithException() {
		$this->assertNull(CakeSession::read('Flash.flash'));

		$this->Flash->set(new Exception('This is a test message', 404));
		$expected = array(
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'Flash/default',
			'params' => array('code' => 404)
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result);
	}

/**
 * testSetWithComponentConfiguration method
 *
 * @return void
 */
	public function testSetWithComponentConfiguration() {
		$this->assertNull(CakeSession::read('Flash.flash'));

		$FlashWithSettings = $this->Components->load('Flash', array('element' => 'test'));
		$FlashWithSettings->set('This is a test message');
		$expected = array(
			'message' => 'This is a test message',
			'key' => 'flash',
			'element' => 'Flash/test',
			'params' => array()
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result);
	}

/**
 * Test magic call method.
 *
 * @return void
 */
	public function testCall() {
		$this->assertNull(CakeSession::read('Flash.flash'));

		$this->Flash->success('It worked');
		$expected = array(
			'message' => 'It worked',
			'key' => 'flash',
			'element' => 'Flash/success',
			'params' => array()
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result);

		$this->Flash->error('It did not work', array('element' => 'error_thing'));
		$expected = array(
			'message' => 'It did not work',
			'key' => 'flash',
			'element' => 'Flash/error',
			'params' => array()
		);
		$result = CakeSession::read('Flash.flash');
		$this->assertEquals($expected, $result, 'Element is ignored in magic call.');
	}
}
