<?php
/**
 * ErrorHandlerTest file
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
 * @package       Cake.Test.Case.Error
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Error;
use Cake\Error\ErrorHandler;
use Cake\Log\Log;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * ErrorHandlerTest class
 *
 * @package       Cake.Test.Case.Error
 */
class ErrorHandlerTest extends TestCase {

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
				CAKE . 'Test/TestApp/View/'
			)
		), App::RESET);
		Router::reload();

		$request = new Request();
		$request->base = '';
		Router::setRequestInfo($request);
		Configure::write('debug', 2);

		Log::disable('stdout');
		Log::disable('stderr');
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
		Log::enable('stdout');
		Log::enable('stderr');
	}

/**
 * test error handling when debug is on, an error should be printed from Debugger.
 *
 * @return void
 */
	public function testHandleErrorDebugOn() {
		set_error_handler('Cake\Error\ErrorHandler::handleError');
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
		set_error_handler('Cake\Error\ErrorHandler::handleError');
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
		set_error_handler('Cake\Error\ErrorHandler::handleError');
		$this->_restoreError = true;

		ob_start();
		//@codingStandardsIgnoreStart
		@include 'invalid.file';
		//@codingStandardsIgnoreEnd
		$result = ob_get_clean();
		$this->assertTrue(empty($result));
	}

/**
 * Test that errors go into Cake Log when debug = 0.
 *
 * @return void
 */
	public function testHandleErrorDebugOff() {
		Configure::write('debug', 0);
		Configure::write('Error.trace', false);
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		set_error_handler('Cake\Error\ErrorHandler::handleError');
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
 * Test that errors going into Cake Log include traces.
 *
 * @return void
 */
	public function testHandleErrorLoggingTrace() {
		Configure::write('debug', 0);
		Configure::write('Error.trace', true);
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		set_error_handler('Cake\Error\ErrorHandler::handleError');
		$this->_restoreError = true;

		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertRegExp(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} (Notice|Debug): Notice \(8\): Undefined variable:\s+out in \[.+ line \d+\]$/',
			$result[0]
		);
		$this->assertRegExp('/^Trace:/', $result[1]);
		$this->assertRegExp('/^' . preg_quote(__NAMESPACE__, '/') . '\\\ErrorHandlerTest\:\:testHandleErrorLoggingTrace\(\)/', $result[2]);
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
		$error = new Error\NotFoundException('Kaboom!');
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
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		Configure::write('Exception.log', true);
		$error = new Error\NotFoundException('Kaboom!');

		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');

		$log = file(LOGS . 'error.log');
		$this->assertContains('[Cake\Error\NotFoundException] Kaboom!', $log[0], 'message missing.');
		$this->assertContains('ErrorHandlerTest->testHandleExceptionLog', $log[2], 'Stack trace missing.');
	}

/**
 * test handleException generating log.
 *
 * @return void
 */
	public function testHandleExceptionLogSkipping() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		Configure::write('Exception.log', true);
		Configure::write('Exception.skipLog', array('Cake\Error\NotFoundException'));
		$notFound = new Error\NotFoundException('Kaboom!');
		$forbidden = new Error\ForbiddenException('Fooled you!');

		ob_start();
		ErrorHandler::handleException($notFound);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');

		ob_start();
		ErrorHandler::handleException($forbidden);
		$result = ob_get_clean();
		$this->assertRegExp('/Fooled you!/', $result, 'message missing.');

		$log = file(LOGS . 'error.log');
		$this->assertNotContains('[Cake\Error\NotFoundException] Kaboom!', $log[0], 'message should not be logged.');
		$this->assertContains('[Cake\Error\ForbiddenException] Fooled you!', $log[0], 'message missing.');
	}

/**
 * tests it is possible to load a plugin exception renderer
 *
 * @return void
 */
	public function testLoadPluginHandler() {
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test/TestApp/Plugin/'
			)
		), App::RESET);
		Plugin::load('TestPlugin');
		Configure::write('Exception.renderer', 'TestPlugin.TestPluginExceptionRenderer');
		$error = new Error\NotFoundException('Kaboom!');
		ob_start();
		ErrorHandler::handleException($error);
		$result = ob_get_clean();
		$this->assertEquals('Rendered by test plugin', $result);
		Plugin::unload();
	}

/**
 * test handleFatalError generating a page.
 *
 * These tests start two buffers as handleFatalError blows the outer one up.
 *
 * @return void
 */
	public function testHandleFatalErrorPage() {
		$line = __LINE__;

		ob_start();
		ob_start();
		Configure::write('debug', 1);
		ErrorHandler::handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
		$result = ob_get_clean();
		$this->assertContains('Something wrong', $result, 'message missing.');
		$this->assertContains(__FILE__, $result, 'filename missing.');
		$this->assertContains((string)$line, $result, 'line missing.');

		ob_start();
		ob_start();
		Configure::write('debug', 0);
		ErrorHandler::handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
		$result = ob_get_clean();
		$this->assertNotContains('Something wrong', $result, 'message must not appear.');
		$this->assertNotContains(__FILE__, $result, 'filename must not appear.');
		$this->assertContains('An Internal Error Has Occurred', $result);
	}

/**
 * test handleException generating log.
 *
 * @return void
 */
	public function testHandleFatalErrorLog() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}

		ob_start();
		ErrorHandler::handleFatalError(E_ERROR, 'Something wrong', __FILE__, __LINE__);
		ob_clean();

		$log = file(LOGS . 'error.log');
		$this->assertContains(__FILE__, $log[0], 'missing filename');
		$this->assertContains('[Cake\Error\FatalErrorException] Something wrong', $log[1], 'message missing.');
	}

}
