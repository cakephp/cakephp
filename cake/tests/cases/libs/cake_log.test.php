<?php
/**
 * CakeLogTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Log');
App::import('Core', 'log/FileLog');

/**
 * CakeLogTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class CakeLogTest extends CakeTestCase {

/**
 * Start test callback, clears all streams enabled.
 *
 * @return void
 */
	function startTest() {
		$streams = CakeLog::configured();
		foreach ($streams as $stream) {
			CakeLog::drop($stream);
		}
	}

/**
 * test importing loggers from app/libs and plugins.
 *
 * @return void
 */
	function testImportingLoggers() {
		App::build(array(
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);

		$result = CakeLog::config('libtest', array(
			'engine' => 'TestAppLog'
		));
		$this->assertTrue($result);
		$this->assertEqual(CakeLog::configured(), array('libtest'));

		$result = CakeLog::config('plugintest', array(
			'engine' => 'TestPlugin.TestPluginLog'
		));
		$this->assertTrue($result);
		$this->assertEqual(CakeLog::configured(), array('libtest', 'plugintest'));

		App::build();
	}

/**
 * test all the errors from failed logger imports
 *
 * @return void
 */
	function testImportingLoggerFailure() {
		$this->expectError('Missing logger classname');
		CakeLog::config('fail', array());

		$this->expectError('Could not load logger class born to fail');
		CakeLog::config('fail', array('engine' => 'born to fail'));

		$this->expectError('logger class stdClass does not implement a write method.');
		CakeLog::config('fail', array('engine' => 'stdClass'));
	}

/**
 * Test that CakeLog autoconfigures itself to use a FileLogger with the LOGS dir.
 * When no streams are there.
 *
 * @return void
 */
	function testAutoConfig() {
		@unlink(LOGS . 'error.log');
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = CakeLog::configured();
		$this->assertEqual($result, array('default'));
		unlink(LOGS . 'error.log');
	}

/**
 * test configuring log streams
 *
 * @return void
 */
	function testConfig() {
		CakeLog::config('file', array(
			'engine' => 'FileLog',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEqual($result, array('file'));

		@unlink(LOGS . 'error.log');
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning/', $result);
		unlink(LOGS . 'error.log');
	}

/**
 * explict tests for drop()
 *
 * @return void
 **/
	function testDrop() {
		CakeLog::config('file', array(
			'engine' => 'FileLog',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEqual($result, array('file'));

		CakeLog::drop('file');
		$result = CakeLog::configured();
		$this->assertEqual($result, array());
	}

/**
 * testLogFileWriting method
 *
 * @access public
 * @return void
 */
	function testLogFileWriting() {
		@unlink(LOGS . 'error.log');
		$result = CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue($result);
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
 */
	function testLoggingWithErrorHandling() {
		@unlink(LOGS . 'debug.log');
		Configure::write('log', E_ALL & ~E_DEPRECATED & ~E_STRICT);
		Configure::write('debug', 0);

		set_error_handler(array('CakeLog', 'handleError'));
		$out .= '';

		$result = file(LOGS . 'debug.log');
		$this->assertEqual(count($result), 1);
		$this->assertPattern(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} Notice: Notice \(8\): Undefined variable:\s+out in \[.+ line \d+\]$/',
			$result[0]
		);
		@unlink(LOGS . 'debug.log');
	}
}
