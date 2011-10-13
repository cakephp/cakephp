<?php
/**
 * MediaViewTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('MediaView', 'View');
App::uses('CakeResponse', 'Network');

/**
 * MediaViewTest class
 *
 * @package       Cake.Test.Case.View
 */
class MediaViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
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
	public function tearDown() {
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
 * @return void
 */
	public function testRender() {
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS .'css' . DS,
			'id' => 'test_asset.css',
			'extension' => 'css',
		);
		$this->MediaView->expects($this->exactly(2))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->response->expects($this->exactly(1))
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

		$this->MediaView->response->expects($this->at(2))
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
 * testRenderWithUnknownFileTypeGeneric method
 *
 * @return void
 */
	public function testRenderWithUnknownFileTypeGeneric() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Some generic browser';
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS,
			'id' => 'no_section.ini',
			'extension' => 'ini',
		);
		$this->MediaView->expects($this->exactly(2))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->response->expects($this->exactly(1))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->at(1))
			->method('header')
			->with(array(
				'Date' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
				'Expires' => '0',
				'Cache-Control' => 'private, must-revalidate, post-check=0, pre-check=0',
				'Pragma' => 'no-cache'
			));

		$this->MediaView->response->expects($this->once())
			->method('download')
			->with('no_section.ini');

		$this->MediaView->response->expects($this->at(3))
			->method('header')
			->with(array(
				'Accept-Ranges' => 'bytes'
			));

		$this->MediaView->response->expects($this->at(4))
			->method('header')
			->with('Content-Length', 35);

		$this->MediaView->response->expects($this->once())->method('send');
		$this->MediaView->expects($this->once())->method('_clearBuffer');
		$this->MediaView->expects($this->once())->method('_flushBuffer');

		ob_start();
		$result = $this->MediaView->render();
		$output = ob_get_clean();
		$this->assertEqual("some_key = some_value\nbool_key = 1\n", $output);
		$this->assertTrue($result !== false);
		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}

/**
 * testRenderWithUnknownFileTypeOpera method
 *
 * @return void
 */
	public function testRenderWithUnknownFileTypeOpera() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS,
			'id' => 'no_section.ini',
			'extension' => 'ini',
		);
		$this->MediaView->expects($this->exactly(2))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->response->expects($this->at(0))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->at(1))
			->method('header')
			->with(array(
				'Date' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
				'Expires' => '0',
				'Cache-Control' => 'private, must-revalidate, post-check=0, pre-check=0',
				'Pragma' => 'no-cache'
			));

		$this->MediaView->response->expects($this->at(2))
			->method('type')
			->with('application/octetstream')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->once())
			->method('download')
			->with('no_section.ini');

		$this->MediaView->response->expects($this->at(4))
			->method('header')
			->with(array(
				'Accept-Ranges' => 'bytes'
			));

		$this->MediaView->response->expects($this->at(5))
			->method('header')
			->with('Content-Length', 35);

		$this->MediaView->response->expects($this->once())->method('send');
		$this->MediaView->expects($this->once())->method('_clearBuffer');
		$this->MediaView->expects($this->once())->method('_flushBuffer');

		ob_start();
		$result = $this->MediaView->render();
		$output = ob_get_clean();
		$this->assertEqual("some_key = some_value\nbool_key = 1\n", $output);
		$this->assertTrue($result !== false);
		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}

/**
 * testRenderWithUnknownFileTypeIE method
 *
 * @return void
 */
	public function testRenderWithUnknownFileTypeIE() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS,
			'id' => 'no_section.ini',
			'extension' => 'ini',
		);
		$this->MediaView->expects($this->exactly(2))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->response->expects($this->at(0))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->at(1))
			->method('header')
			->with(array(
				'Date' => gmdate('D, d M Y H:i:s', time()) . ' GMT',
				'Expires' => '0',
				'Cache-Control' => 'private, must-revalidate, post-check=0, pre-check=0',
				'Pragma' => 'no-cache'
			));

		$this->MediaView->response->expects($this->at(2))
			->method('type')
			->with('application/force-download')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->once())
			->method('download')
			->with('no_section.ini');

		$this->MediaView->response->expects($this->at(4))
			->method('header')
			->with(array(
				'Accept-Ranges' => 'bytes'
			));

		$this->MediaView->response->expects($this->at(5))
			->method('header')
			->with('Content-Length', 35);

		$this->MediaView->response->expects($this->once())->method('send');
		$this->MediaView->expects($this->once())->method('_clearBuffer');
		$this->MediaView->expects($this->once())->method('_flushBuffer');

		ob_start();
		$result = $this->MediaView->render();
		$output = ob_get_clean();
		$this->assertEqual("some_key = some_value\nbool_key = 1\n", $output);
		$this->assertTrue($result !== false);
		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}

/**
 * testConnectionAborted method
 *
 * @return void
 */
	public function testConnectionAborted() {
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS .'css' . DS,
			'id' => 'test_asset.css',
			'extension' => 'css',
		);

		$this->MediaView->expects($this->once())
			->method('_isActive')
			->will($this->returnValue(false));

		$this->MediaView->response->expects($this->never())
			->method('type');

		$result = $this->MediaView->render();
		$this->assertFalse($result);
	}

/**
 * testConnectionAbortedOnBuffering method
 *
 * @return void
 */
	public function testConnectionAbortedOnBuffering() {
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS .'css' . DS,
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

/**
 * Test downloading files with UPPERCASE extensions.
 *
 * @return void
 */
	function testRenderUpperExtesnion() {
		$this->MediaView->viewVars = array(
			'path' =>  CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS .'img' . DS,
			'id' => 'test_2.JPG',
			'extension' => 'JPG',
		);

		$this->MediaView->response->expects($this->any())
			->method('type')
			->with('jpg')
			->will($this->returnArgument(0));

		$this->MediaView->expects($this->at(0))
			->method('_isActive')
			->will($this->returnValue(true));

		$this->MediaView->render();
	}

}
