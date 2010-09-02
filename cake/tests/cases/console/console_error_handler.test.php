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
App::import('Core', 'ConsoleErrorHandler');

class TestConsoleErrorHandler extends ConsoleErrorHandler {
	public $output = array();

/**
 * Override stderr() so it doesn't do bad things.
 *
 * @param string $line 
 * @return void
 */
	function stderr($line) {
		$this->output[] = $line;
	}
}


/**
 * ConsoleErrorHandler Test case.
 *
 * @package cake.tests.cases.console
 */
class ConsoleErrorHandlerTest extends CakeTestCase {

/**
 * test that the console error handler can deal with CakeExceptions.
 *
 * @return void
 */
	function testCakeErrors() {
		$exception = new MissingActionException('Missing action');
		$error = new TestConsoleErrorHandler($exception);
		$error->render();

		$result = $error->output;
		$this->assertEquals(1, count($result));
		$this->assertEquals('Missing action', $result[0]);
	}

/**
 * test a non CakeException exception.
 *
 * @return void
 */
	function testNonCakeExceptions() {
		$exception = new InvalidArgumentException('Too many parameters.');
		$error = new TestConsoleErrorHandler($exception);
		$error->render();

		$result = $error->output;
		$this->assertEquals(1, count($result));
		$this->assertEquals('Too many parameters.', $result[0]);
	}
}