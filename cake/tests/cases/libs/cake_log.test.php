<?php
/**
 * CakeLogTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Log');

/**
 * CakeLogTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class CakeLogTest extends CakeTestCase {

/**
 * Test that CakeLog autoconfigures itself to use a FileLogger with the LOGS dir.
 * When no streams are there.
 *
 * @return void
 **/
	function testAutoConfig() {
		$streams = CakeLog::streams();
		foreach ($streams as $stream) {
			CakeLog::removeStream($stream);
		}

		@unlink(LOGS . 'error.log');
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = CakeLog::streams();
		$this->assertEqual($result, array('default'));
	}

/**
 * testLogFileWriting method
 *
 * @access public
 * @return void
 */
	function testLogFileWriting() {
		@unlink(LOGS . 'error.log');
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		unlink(LOGS . 'error.log');

		CakeLog::write(LOG_WARNING, 'Test warning 1');
		CakeLog::write(LOG_WARNING, 'Test warning 2');
		$result = file_get_contents(LOGS . 'error.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1/', $result);
		$this->assertPattern('/2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 2$/', $result);
		unlink(LOGS . 'error.log');
	}

/**
 * Test logging with the error handler.
 *
 * @return void
 **/
	function testLoggingWithErrorHandling() {
		@unlink(LOGS . 'debug.log');
		Configure::write('log', E_ALL & ~E_DEPRECATED);
		Configure::write('debug', 0);

		set_error_handler(array('CakeLog', 'handleError'));
		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertEqual(count($result), 1);
		$this->assertPattern(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} Notice: Notice \(8\): Undefined variable: out in \[.+ line \d{2}\]$/',
			$result[0]
		);
		@unlink(LOGS . 'debug.log');
	}
}
?>