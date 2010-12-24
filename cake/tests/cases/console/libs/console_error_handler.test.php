<?php
/**
 * ConsoleErrorHandler Test case
 *
 * PHP versions 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once CONSOLE_LIBS . 'console_error_handler.php';

/**
 * ConsoleErrorHandler Test case.
 *
 * @package cake.tests.cases.console
 */
class ConsoleErrorHandlerTest extends CakeTestCase {

/**
 * setup, create mocks
 *
 * @return Mock object
 */
	function setUp() {
		parent::setUp();
		ConsoleErrorHandler::$stderr = $this->getMock('ConsoleOutput', array(), array(), '', false);
	}

/**
 * teardown
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
	}

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	function testHandleError() {
		$content = "<error>Notice Error:</error> This is a notice error in [/some/file, line 275]\n";
		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($content);

		ConsoleErrorHandler::handleError(E_NOTICE, 'This is a notice error', '/some/file', 275);
	}

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	function testCakeErrors() {
		$exception = new MissingActionException('Missing action');
		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('Missing action'));

		ConsoleErrorHandler::handleException($exception);
	}

/**
 * test a non CakeException exception.
 *
 * @return void
 */
	function testNonCakeExceptions() {
		$exception = new InvalidArgumentException('Too many parameters.');

		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('Too many parameters.'));
		
		ConsoleErrorHandler::handleException($exception);
	}

/**
 * test a Error404 exception.
 *
 * @return void
 */
	function testError404Exception() {
		$exception = new NotFoundException('dont use me in cli.');
		
		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		ConsoleErrorHandler::handleException($exception);
	}

/**
 * test a Error500 exception.
 *
 * @return void
 */
	function testError500Exception() {
		$exception = new InternalErrorException('dont use me in cli.');

		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		ConsoleErrorHandler::handleException($exception);
	}

}