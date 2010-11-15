<?php
/**
 * ErrorHandlerTest file
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
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', array('ErrorHandler', 'Controller', 'Component'));

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class AuthBlueberryUser extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthBlueberryUser'
 * @access public
 */
	public $name = 'AuthBlueberryUser';

/**
 * useTable property
 *
 * @var string
 * @access public
 */
	public $useTable = false;
}

/**
 * BlueberryComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class BlueberryComponent extends Component {

/**
 * testName property
 *
 * @access public
 * @return void
 */
	public $testName = null;

/**
 * initialize method
 *
 * @access public
 * @return void
 */
	function initialize(&$controller) {
		$this->testName = 'BlueberryComponent';
	}
}

/**
 * TestErrorController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestErrorController extends Controller {

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array();

/**
 * components property
 *
 * @access public
 * @return void
 */
	public $components = array('Blueberry');

/**
 * beforeRender method
 *
 * @access public
 * @return void
 */
	function beforeRender() {
		echo $this->Blueberry->testName;
	}

/**
 * index method
 *
 * @access public
 * @return void
 */
	function index() {
		$this->autoRender = false;
		return 'what up';
	}
}

/**
 * MyCustomErrorHandler class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class MyCustomErrorHandler extends ErrorHandler {

/**
 * custom error message type.
 *
 * @return void
 */
	function missingWidgetThing() {
		echo 'widget thing is missing';
	}
}
/**
 * Exception class for testing app error handlers and custom errors.
 *
 * @package cake.test.cases.libs
 */
class MissingWidgetThingException extends NotFoundException { }


/**
 * ErrorHandlerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ErrorHandlerTest extends CakeTestCase {

	var $_restoreError = false;
/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	function setUp() {
		App::build(array(
			'views' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS,
				TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS
			)
		), true);
		Router::reload();

		$request = new CakeRequest(null, false);
		$request->base = '';
		Router::setRequestInfo($request);
		$this->_debug = Configure::read('debug');
		$this->_error = Configure::read('Error');
		Configure::write('debug', 2);
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		Configure::write('debug', $this->_debug);
		Configure::write('Error', $this->_error);
		App::build();
		if ($this->_restoreError) {
			restore_error_handler();
		}
	}

/**
 * test error handling when debug is on, an error should be printed from Debugger.
 *
 * @return void
 */
	function testHandleErrorDebugOn() {
		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		ob_start();
		$wrong .= '';
		$result = ob_get_clean();

		$this->assertPattern('/<pre class="cake-debug">/', $result);
		$this->assertPattern('/<b>Notice<\/b>/', $result);
		$this->assertPattern('/variable:\s+wrong/', $result);
	}

/**
 * Test that errors go into CakeLog when debug = 0.
 *
 * @return void
 */
	function testHandleErrorDebugOff() {
		Configure::write('debug', 0);
		Configure::write('Error.trace', false);
		if (file_exists(LOGS . 'debug.log')) {
			@unlink(LOGS . 'debug.log');
		}

		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertEqual(count($result), 1);
		$this->assertPattern(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} Notice: Notice \(8\): Undefined variable:\s+out in \[.+ line \d+\]$/',
			$result[0]
		);
		@unlink(LOGS . 'debug.log');
	}

/**
 * Test that errors going into CakeLog include traces.
 *
 * @return void
 */
	function testHandleErrorLoggingTrace() {
		Configure::write('debug', 0);
		Configure::write('Error.trace', true);
		if (file_exists(LOGS . 'debug.log')) {
			@unlink(LOGS . 'debug.log');
		}

		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertPattern(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} Notice: Notice \(8\): Undefined variable:\s+out in \[.+ line \d+\]$/',
			$result[0]
		);
		$this->assertPattern('/^Trace:/', $result[1]);
		$this->assertPattern('/^ErrorHandlerTest\:\:testHandleErrorLoggingTrace\(\)/', $result[2]);
		@unlink(LOGS . 'debug.log');
	}

/**
 * test handleException generating a page.
 *
 * @return void
 */
	function testHandleException() {
		if ($this->skipIf(file_exists(APP . 'app_error.php'), 'App error exists cannot run.')) {
			return;
		}
		if ($this->skipIf(PHP_SAPI == 'cli', 'This integration test can not be run in cli.')) {
			return;
		}
		$error = new NotFoundException('Kaboom!');
		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertPattern('/Kaboom!/', $result, 'message missing.');
	}

}
