<?php
/**
 * ErrorHandlerTest file
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
		Router::reload();

		$request = new Request();
		$request->base = '';
		Router::setRequestInfo($request);
		Configure::write('debug', true);

		$this->_logger = $this->getMock('Cake\Log\LogInterface');

		Log::reset();
		Log::config('error_test', [
			'engine' => $this->_logger
		]);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Log::reset();
		if ($this->_restoreError) {
			restore_error_handler();
			restore_exception_handler();
		}
	}

/**
 * test error handling when debug is on, an error should be printed from Debugger.
 *
 * @return void
 */
	public function testHandleErrorDebugOn() {
		$errorHandler = new ErrorHandler();
		$errorHandler->register();
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
		$errorHandler = new ErrorHandler();
		$errorHandler->register();
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
		$errorHandler = new ErrorHandler();
		$errorHandler->register();
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
		Configure::write('debug', false);
		$errorHandler = new ErrorHandler();
		$errorHandler->register();
		$this->_restoreError = true;

		$this->_logger->expects($this->once())
			->method('write')
			->with('notice', 'Notice (8): Undefined variable: out in [' . __FILE__ . ', line ' . (__LINE__ + 2) . ']');

		$out .= '';
	}

/**
 * Test that errors going into Cake Log include traces.
 *
 * @return void
 */
	public function testHandleErrorLoggingTrace() {
		Configure::write('debug', false);
		$errorHandler = new ErrorHandler(['trace' => true]);
		$errorHandler->register();
		$this->_restoreError = true;

		$this->_logger->expects($this->once())
			->method('write')
			->with('notice', $this->logicalAnd(
				$this->stringContains('Notice (8): Undefined variable: out in '),
				$this->stringContains('Trace:'),
				$this->stringContains(__NAMESPACE__ . '\ErrorHandlerTest::testHandleErrorLoggingTrace()')
			));

		$out .= '';
	}

/**
 * test handleException generating a page.
 *
 * @return void
 */
	public function testHandleException() {
		$error = new Error\NotFoundException('Kaboom!');
		$errorHandler = new ErrorHandler();

		ob_start();
		$errorHandler->handleException($error);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');
	}

/**
 * test handleException generating log.
 *
 * @return void
 */
	public function testHandleExceptionLog() {
		$errorHandler = new ErrorHandler(['log' => true]);

		$error = new Error\NotFoundException('Kaboom!');

		$this->_logger->expects($this->once())
			->method('write')
			->with('error', $this->logicalAnd(
				$this->stringContains('[Cake\Error\NotFoundException] Kaboom!'),
				$this->stringContains('ErrorHandlerTest->testHandleExceptionLog')
			));

		ob_start();
		$errorHandler->handleException($error);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');
	}

/**
 * test handleException generating log.
 *
 * @return void
 */
	public function testHandleExceptionLogSkipping() {
		$notFound = new Error\NotFoundException('Kaboom!');
		$forbidden = new Error\ForbiddenException('Fooled you!');

		$this->_logger->expects($this->once())
			->method('write')
			->with(
				'error',
				$this->stringContains('[Cake\Error\ForbiddenException] Fooled you!')
			);

		$errorHandler = new ErrorHandler([
			'log' => true,
			'skipLog' => ['Cake\Error\NotFoundException']
		]);

		ob_start();
		$errorHandler->handleException($notFound);
		$result = ob_get_clean();
		$this->assertRegExp('/Kaboom!/', $result, 'message missing.');

		ob_start();
		$errorHandler->handleException($forbidden);
		$result = ob_get_clean();
		$this->assertRegExp('/Fooled you!/', $result, 'message missing.');
	}

/**
 * tests it is possible to load a plugin exception renderer
 *
 * @return void
 */
	public function testLoadPluginHandler() {
		Plugin::load('TestPlugin');
		$errorHandler = new ErrorHandler([
			'exceptionRenderer' => 'TestPlugin.TestPluginExceptionRenderer',
		]);

		$error = new Error\NotFoundException('Kaboom!');
		ob_start();
		$errorHandler->handleException($error);
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
		$errorHandler = new ErrorHandler();
		Configure::write('debug', true);
		ob_start();
		ob_start();
		$errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
		$result = ob_get_clean();
		$this->assertContains('Something wrong', $result, 'message missing.');
		$this->assertContains(__FILE__, $result, 'filename missing.');
		$this->assertContains((string)$line, $result, 'line missing.');

		ob_start();
		ob_start();
		Configure::write('debug', false);
		$errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, $line);
		$result = ob_get_clean();
		$this->assertNotContains('Something wrong', $result, 'message must not appear.');
		$this->assertNotContains(__FILE__, $result, 'filename must not appear.');
		$this->assertContains('An Internal Error Has Occurred', $result);
	}

/**
 * test handleFatalError generating log.
 *
 * @return void
 */
	public function testHandleFatalErrorLog() {
		$this->_logger->expects($this->at(0))
			->method('write')
			->with('error', $this->logicalAnd(
				$this->stringContains(__FILE__ . ', line ' . (__LINE__ + 10)),
				$this->stringContains('Fatal Error (1)'),
				$this->stringContains('Something wrong')
			));
		$this->_logger->expects($this->at(1))
			->method('write')
			->with('error', $this->stringContains('[Cake\Error\FatalErrorException] Something wrong'));

		$errorHandler = new ErrorHandler(['log' => true]);
		ob_start();
		$errorHandler->handleFatalError(E_ERROR, 'Something wrong', __FILE__, __LINE__);
		ob_clean();
	}

}
