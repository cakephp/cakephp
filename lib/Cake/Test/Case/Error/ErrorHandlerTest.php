<?php
/**
 * ErrorHandlerTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Error
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ErrorHandler', 'Error');
App::uses('Controller', 'Controller');
App::uses('Router', 'Routing');

/**
 * ErrorHandlerTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ErrorHandlerTest extends CakeTestCase {

	protected $_restoreError = false;

/**
 * setup create a request object to get out of router later.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'View' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS
			)
		), App::RESET);
		Router::reload();

		$request = new CakeRequest(null, false);
		$request->base = '';
		Router::setRequestInfo($request);
		Configure::write('debug', 2);

		CakeLog::disable('stdout');
		CakeLog::disable('stderr');
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		if ($this->_restoreError) {
			restore_error_handler();
		}
		CakeLog::enable('stdout');
		CakeLog::enable('stderr');
	}

/**
 * test error handling when debug is on, an error should be printed from Debugger.
 *
 * @return void
 */
	public function testHandleErrorDebugOn() {
		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		ob_start();
		$wrong .= '';
		$result = ob_get_clean();

		$this->assertRegExp('/<pre class="cake-error">/', $result);
		$this->assertRegExp('/<b>Notice<\/b>/', $result);
		$this->assertRegExp('/variable:\s+wrong/', $result);
	}

/**
 * provides errors for mapping tests.
 *
 * @return void
 */
	public static function errorProvider() {
		return array(
			array(E_USER_NOTICE, 'Notice'),
			array(E_USER_WARNING, 'Warning'),
		);
	}

/**
 * test error mappings
 *
 * @dataProvider errorProvider
 * @return void
 */
	public function testErrorMapping($error, $expected) {
		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		ob_start();
		trigger_error('Test error', $error);

		$result = ob_get_clean();
		$this->assertRegExp('/<b>' . $expected . '<\/b>/', $result);
	}

/**
 * test error prepended by @
 *
 * @return void
 */
	public function testErrorSuppressed() {
		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		ob_start();
		//@codingStandardsIgnoreStart
		@include 'invalid.file';
		//@codingStandardsIgnoreEnd
		$result = ob_get_clean();
		$this->assertTrue(empty($result));
	}

/**
 * Test that errors go into CakeLog when debug = 0.
 *
 * @return void
 */
	public function testHandleErrorDebugOff() {
		Configure::write('debug', 0);
		Configure::write('Error.trace', false);
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertEquals(1, count($result));
		$this->assertRegExp(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} (Notice|Debug): Notice \(8\): Undefined variable:\s+out in \[.+ line \d+\]$/',
			$result[0]
		);
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
	}

/**
 * Test that errors going into CakeLog include traces.
 *
 * @return void
 */
	public function testHandleErrorLoggingTrace() {
		Configure::write('debug', 0);
		Configure::write('Error.trace', true);
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		set_error_handler('ErrorHandler::handleError');
		$this->_restoreError = true;

		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertRegExp(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} (Notice|Debug): Notice \(8\): Undefined variable:\s+out in \[.+ line \d+\]$/',
			$result[0]
		);
		$this->assertRegExp('/^Trace:/', $result[1]);
		$this->assertRegExp('/^ErrorHandlerTest\:\:testHandleErrorLoggingTrace\(\)/', $result[2]);
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
	}

/**
 * test handleException generating a page.
 *
 * @return void
 */
	public function testHandleException() {
		$this->skipIf(file_exists(APP . 'app_error.php'), 'App error exists cannot run.');

		$error = new NotFoundException('Kaboom!');
		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');
	}

/**
 * test handleException generating log.
 *
 * @return void
 */
	public function testHandleExceptionLog() {
		$this->skipIf(file_exists(APP . 'app_error.php'), 'App error exists cannot run.');

		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		Configure::write('Exception.log', true);
		$error = new NotFoundException('Kaboom!');

		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');

		$log = file(LOGS . 'error.log');
		$this->assertRegExp('/\[NotFoundException\] Kaboom!/', $log[0], 'message missing.');
		$this->assertRegExp('/\#0.*ErrorHandlerTest->testHandleExceptionLog/', $log[1], 'Stack trace missing.');
	}

/**
 * tests it is possible to load a plugin exception renderer
 *
 * @return void
 */
	public function testLoadPluginHandler() {
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
			)
		), App::RESET);
		CakePlugin::load('TestPlugin');
		Configure::write('Exception.renderer', 'TestPlugin.TestPluginExceptionRenderer');
		$error = new NotFoundException('Kaboom!');
		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertEquals('Rendered by test plugin', $result);
		CakePlugin::unload();
	}

/**
 * test handleFatalError generating a page.
 *
 * @return void
 */
	public function testHandleFatalErrorPage() {
		$this->skipIf(file_exists(APP . 'app_error.php'), 'App error exists cannot run.');

		$originalDebugLevel = Configure::read('debug');
		$line = __LINE__;

		ob_start();
		Configure::write('debug', 1);
		ErrorHandler::handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
		$result = ob_get_clean();
		$this->assertContains('Something wrong', $result, 'message missing.');
		$this->assertContains(__FILE__, $result, 'filename missing.');
		$this->assertContains((string)$line, $result, 'line missing.');

		ob_start();
		Configure::write('debug', 0);
		ErrorHandler::handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
		$result = ob_get_clean();
		$this->assertNotContains('Something wrong', $result, 'message must not appear.');
		$this->assertNotContains(__FILE__, $result, 'filename must not appear.');
		$this->assertContains('An Internal Error Has Occurred', $result);

		Configure::write('debug', $originalDebugLevel);
	}

/**
 * test handleException generating log.
 *
 * @return void
 */
	public function testHandleFatalErrorLog() {
		$this->skipIf(file_exists(APP . 'app_error.php'), 'App error exists cannot run.');

		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}

		ob_start();
		ErrorHandler::handleFatalError(E_ERROR, 'Something wrong', __FILE__, __LINE__);
		ob_clean();

		$log = file(LOGS . 'error.log');
		$this->assertContains(__FILE__, $log[0], 'missing filename');
		$this->assertContains('[FatalErrorException] Something wrong', $log[1], 'message missing.');
	}

}
