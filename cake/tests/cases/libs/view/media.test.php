<?php
/**
 * ThemeViewTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Media', 'Controller'));

if (!class_exists('ErrorHandler')) {
	App::import('Core', array('Error'));
}
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

/**
 * ThemePostsController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class MediaController extends Controller {

/**
 * name property
 *
 * @var string 'Media'
 * @access public
 */
	var $name = 'Media';

/**
 * index download
 *
 * @access public
 * @return void
 */
	function download() {
		$path = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS .'css' . DS;
		$id = 'test_asset.css';
		$extension = 'css';
		$this->set(compact('path', 'id', 'extension'));
	}
	
	function downloadUpper() {
		$path = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS .'img' . DS;
		$id = 'test_2.JPG';
		$extension = 'JPG';
		$this->set(compact('path', 'id', 'extension'));
	}
}

/**
 * TestMediaView class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class TestMediaView extends MediaView {

/**
 * headers public property as a copy from protected property _headers
 *
 * @var array
 * @access public
 */
	var $headers = array();

/**
 * active property to mock the status of a remote connection
 *
 * @var boolean true
 * @access public
 */
	var $active = true;

	function _output() {
		$this->headers = $this->_headers;
	}

/**
 * _isActive method. Usted de $active property to mock an active (true) connection,
 * or an aborted (false) one
 *
 * @access protected
 * @return void
 */
	function _isActive() {
		return $this->active;
	}

/**
 * _clearBuffer method
 *
 * @access protected
 * @return void
 */
	function _clearBuffer() {
		return true;
	}

/**
 * _flushBuffer method
 *
 * @access protected
 * @return void
 */
	function _flushBuffer() {
	}
}

/**
 * ThemeViewTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class MediaViewTest extends CakeTestCase {

/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		Router::reload();
		$this->Controller =& new Controller();
		$this->MediaController =& new MediaController();
		$this->MediaController->viewPath = 'posts';
	}

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		unset($this->MediaView);
		unset($this->MediaController);
		unset($this->Controller);
		ClassRegistry::flush();
	}

/**
 * testRender method
 *
 * @access public
 * @return void
 */
	function testRender() {
		ob_start();
		$this->MediaController->download();
		$this->MediaView =& new TestMediaView($this->MediaController);
		$result = $this->MediaView->render();
		$output = ob_get_clean();

		$this->assertTrue($result !== false);
		$this->assertEqual($output, 'this is the test asset css file');
	}
	
	function testRenderUpperExtesnion() {
		ob_start();
		$this->MediaController->downloadUpper();
		$this->MediaView =& new TestMediaView($this->MediaController);
		$result = $this->MediaView->render();
		$output = ob_get_clean();

		$this->assertTrue($result !== false);
		
		$fileName = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS .'img' . DS . 'test_2.JPG';
		$file = file_get_contents($fileName, 'r');
		
		$this->assertEqual(base64_encode($output), base64_encode($file));		
	}

/**
 * testConnectionAborted method
 *
 * @access public
 * @return void
 */
	function testConnectionAborted() {
		$this->MediaController->download();
		$this->MediaView =& new TestMediaView($this->MediaController);
		$this->MediaView->active = false;
		$result = $this->MediaView->render();
		$this->assertFalse($result);
	}
}
