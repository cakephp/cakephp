<?php
/**
 * ConsoleErrorHandler Test case
 *
 * PHP versions 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ConsoleErrorHandler', 'Console');

/**
 * ConsoleErrorHandler Test case.
 *
 * @package       Cake.Test.Case.Console
 */
class ConsoleErrorHandlerTest extends CakeTestCase {

/**
 * setup, create mocks
 *
 * @return Mock object
 */
	public function setUp() {
		parent::setUp();
		$this->Error = $this->getMock('ConsoleErrorHandler', array('_stop'));
		ConsoleErrorHandler::$stderr = $this->getMock('ConsoleOutput', array(), array(), '', false);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Error);
		parent::tearDown();
	}

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	public function testHandleError() {
		$content = "<error>Notice Error:</error> This is a notice error in [/some/file, line 275]\n";
		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($content);

		$this->Error->handleError(E_NOTICE, 'This is a notice error', '/some/file', 275);
	}

/**
 * test that the console error handler can deal with fatal errors.
 *
 * @return void
 */
	public function testHandleFatalError() {
		$content = "<error>Fatal Error Error:</error> This is a fatal error in [/some/file, line 275]\n";
		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($content);

		$this->Error->expects($this->once())
			->method('_stop')
			->with(1);

		$this->Error->handleError(E_USER_ERROR, 'This is a fatal error', '/some/file', 275);
	}

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	public function testCakeErrors() {
		$exception = new MissingActionException('Missing action');
		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('Missing action'));

		$this->Error->expects($this->once())
			->method('_stop')
			->with(404);

		$this->Error->handleException($exception);
	}

/**
 * test a non CakeException exception.
 *
 * @return void
 */
	public function testNonCakeExceptions() {
		$exception = new InvalidArgumentException('Too many parameters.');

		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('Too many parameters.'));

		$this->Error->expects($this->once())
			->method('_stop')
			->with(1);

		$this->Error->handleException($exception);
	}

/**
 * test a Error404 exception.
 *
 * @return void
 */
	public function testError404Exception() {
		$exception = new NotFoundException('dont use me in cli.');

		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		$this->Error->expects($this->once())
			->method('_stop')
			->with(404);

		$this->Error->handleException($exception);
	}

/**
 * test a Error500 exception.
 *
 * @return void
 */
	public function testError500Exception() {
		$exception = new InternalErrorException('dont use me in cli.');

		ConsoleErrorHandler::$stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		$this->Error->expects($this->once())
			->method('_stop')
			->with(500);

		$this->Error->handleException($exception);
	}

}
