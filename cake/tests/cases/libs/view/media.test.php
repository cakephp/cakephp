<?php
/**
 * ThemeViewTest file
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
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('Media', 'Controller', 'CakeResponse'));

/**
 * MediaViewTest class
 *
 * @package       cake.tests.cases.libs
 */
class MediaViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$controller = new Controller();
		$this->MediaView = $this->getMock('MediaView', array('_isActive', '_clearBuffer', '_flushBuffer'));
		$this->MediaView->response = $this->getMock('CakeResponse');
	}

/**
 * endTest method
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->MediaView);
	}

/**
 * tests that rendering a file that does not exists throws an exception
 *
 * @expectedException NotFoundException
 * @return void
 */
	public function testRenderNotFound() {
		$this->MediaView->viewVars = array(
			'path' => '/some/missing/folder',
			'id' => 'file.jpg'
		);
		$this->MediaView->render();
	}

/**
 * testRender method
 *
 * @access public
 * @return void
 */
	function testRender() {
		$this->MediaView->viewVars = array(
			'path' =>  TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS .'css' . DS,
			'id' => 'test_asset.css',
			'extension' => 'css',
		);
		$this->MediaView->expects($this->exactly(2))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->response->expects($this->exactly(2))
			->method('type')
			->with('css')
			->will($this->returnArgument(0));

		$this->MediaView->response->expects($this->at(1))
			->method('header')
			->with(array(
				'Date' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
				'Expires' => '0',
				'Cache-Control' => 'private, must-revalidate, post-check=0, pre-check=0',
				'Pragma' => 'no-cache'
			));

		$this->MediaView->response->expects($this->at(3))
			->method('header')
			->with(array(
				'Content-Length' => 31
			));
		$this->MediaView->response->expects($this->once())->method('send');
		$this->MediaView->expects($this->once())->method('_clearBuffer');
		$this->MediaView->expects($this->once())->method('_flushBuffer');

		ob_start();
		$result = $this->MediaView->render();
		$output = ob_get_clean();
		$this->assertEqual('this is the test asset css file', $output);
		$this->assertTrue($result !== false);
	}

/**
 * testConnectionAborted method
 *
 * @access public
 * @return void
 */
	function testConnectionAborted() {
		$this->MediaView->viewVars = array(
			'path' =>  TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS .'css' . DS,
			'id' => 'test_asset.css',
			'extension' => 'css',
		);

		$this->MediaView->expects($this->once())
			->method('_isActive')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->once())
			->method('type')
			->with('css')
			->will($this->returnArgument(0));

		$result = $this->MediaView->render();
		$this->assertFalse($result);
	}

/**
 * testConnectionAbortedOnBuffering method
 *
 * @access public
 * @return void
 */
	function testConnectionAbortedOnBuffering() {
		$this->MediaView->viewVars = array(
			'path' =>  TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS .'css' . DS,
			'id' => 'test_asset.css',
			'extension' => 'css',
		);

		$this->MediaView->expects($this->at(0))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->response->expects($this->any())
			->method('type')
			->with('css')
			->will($this->returnArgument(0));

		$this->MediaView->expects($this->at(1))
			->method('_isActive')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->once())->method('send');
		$this->MediaView->expects($this->once())->method('_clearBuffer');
		$this->MediaView->expects($this->never())->method('_flushBuffer');

		$result = $this->MediaView->render();
		$this->assertFalse($result);
	}
}
