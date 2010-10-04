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
 * @package       cake
 * @subpackage    cake.cake.tests.cases.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once CAKE . 'console' . DS . 'console_error_handler.php';

/**
 * ConsoleErrorHandler Test case.
 *
 * @package cake.tests.cases.console
 */
class ConsoleErrorHandlerTest extends CakeTestCase {

/**
 * Factory method for error handlers with stderr() mocked out.
 *
 * @return Mock object
 */
	function getErrorHandler($exception) {
		$error = new ConsoleErrorHandler($exception);
		$error->stderr = $this->getMock('ConsoleOutput');
		return $error;
	}

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	function testCakeErrors() {
		$exception = new MissingActionException('Missing action');
		$error = $this->getErrorHandler($exception);

		$error->stderr->expects($this->once())->method('write')
			->with($this->stringContains('Missing action'));

		$error->render();
	}

/**
 * test a non CakeException exception.
 *
 * @return void
 */
	function testNonCakeExceptions() {
		$exception = new InvalidArgumentException('Too many parameters.');
		$error = $this->getErrorHandler($exception);

		$error->stderr->expects($this->once())->method('write')
			->with($this->stringContains('Too many parameters.'));
		
		$error->render();
	}

/**
 * test a Error404 exception.
 *
 * @return void
 */
	function testError404Exception() {
		$exception = new NotFoundException('dont use me in cli.');
		$error = $this->getErrorHandler($exception);

		$error->stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		$error->render();
	}

/**
 * test a Error500 exception.
 *
 * @return void
 */
	function testError500Exception() {
		$exception = new InternalErrorException('dont use me in cli.');
		$error = $this->getErrorHandler($exception);

		$error->stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		$error->render();
	}

/**
 * test that ConsoleErrorHandler has a stderr file handle.
 *
 * @return void
 */
	function testStdErrFilehandle() {
		$exception = new InternalErrorException('dont use me in cli.');
		$error = new ConsoleErrorHandler($exception);

		$this->assertType('ConsoleOutput', $error->stderr, 'No handle.');
	}
}