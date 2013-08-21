<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleErrorHandler;
use Cake\Error;
use Cake\TestSuite\TestCase;

/**
 * ConsoleErrorHandler Test case.
 */
class ConsoleErrorHandlerTest extends TestCase {

/**
 * setup, create mocks
 *
 * @return Mock object
 */
	public function setUp() {
		parent::setUp();
		$this->stderr = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$this->Error = new ConsoleErrorHandler(['stderr' => $this->stderr]);
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
 * test that the console error handler can deal with Exceptions.
 *
 * @return void
 */
	public function testHandleError() {
		$content = "<error>Notice Error:</error> This is a notice error in [/some/file, line 275]\n";
		$this->stderr->expects($this->once())->method('write')
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
		$this->stderr->expects($this->once())->method('write')
			->with($content);

		$this->Error->handleError(E_USER_ERROR, 'This is a fatal error', '/some/file', 275);
	}

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	public function testCakeErrors() {
		$exception = new Error\MissingActionException('Missing action');
		$this->stderr->expects($this->once())->method('write')
			->with($this->stringContains('Missing action'));

		$result = $this->Error->handleException($exception);
		$this->assertEquals(404, $result);
	}

/**
 * test a non Cake Exception exception.
 *
 * @return void
 */
	public function testNonCakeExceptions() {
		$exception = new \InvalidArgumentException('Too many parameters.');

		$this->stderr->expects($this->once())->method('write')
			->with($this->stringContains('Too many parameters.'));

		$result = $this->Error->handleException($exception);
		$this->assertEquals(1, $result);
	}

/**
 * test a Error404 exception.
 *
 * @return void
 */
	public function testError404Exception() {
		$exception = new Error\NotFoundException('dont use me in cli.');

		$this->stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		$result = $this->Error->handleException($exception);
		$this->assertEquals(404, $result);
	}

/**
 * test a Error500 exception.
 *
 * @return void
 */
	public function testError500Exception() {
		$exception = new Error\InternalErrorException('dont use me in cli.');

		$this->stderr->expects($this->once())->method('write')
			->with($this->stringContains('dont use me in cli.'));

		$result = $this->Error->handleException($exception);
		$this->assertEquals(500, $result);
	}

}
