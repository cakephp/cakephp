<?php
/**
 * ConsoleLogTest file
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
 * @package       Cake.Test.Case.Log.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ConsoleLog', 'Log/Engine');

class TestConsoleLog extends ConsoleLog {

}

class TestCakeLog extends CakeLog {

	public static function replace($key, &$engine) {
		self::$_Collection->{$key} = $engine;
	}

}

/**
 * ConsoleLogTest class
 *
 * @package       Cake.Test.Case.Log.Engine
 */
class ConsoleLogTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		CakeLog::config('debug', array(
			'engine' => 'FileLog',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		CakeLog::config('error', array(
			'engine' => 'FileLog',
			'types' => array('error', 'warning'),
			'file' => 'error',
		));
	}

	public function tearDown() {
		parent::tearDown();
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
	}

/**
 * Test writing to ConsoleOutput
 */
	public function testConsoleOutputWrites() {
		TestCakeLog::config('test_console_log', array(
			'engine' => 'TestConsoleLog',
			));

		$mock = $this->getMock('TestConsoleLog', array('write'), array(
			array('types' => 'error'),
			));
		TestCakeLog::replace('test_console_log', $mock);

		$message = 'Test error message';
		$mock->expects($this->once())
			->method('write');
		TestCakeLog::write(LOG_ERR, $message);
	}

/**
 * Test logging to both ConsoleLog and FileLog
 */
	public function testCombinedLogWriting() {
		TestCakeLog::config('test_console_log', array(
			'engine' => 'TestConsoleLog',
			));
		$mock = $this->getMock('TestConsoleLog', array('write'), array(
			array('types' => 'error'),
			));
		TestCakeLog::replace('test_console_log', $mock);

		// log to both file and console
		$message = 'Test error message';
		$mock->expects($this->once())
			->method('write');
		TestCakeLog::write(LOG_ERR, $message);
		$this->assertTrue(file_exists(LOGS . 'error.log'), 'error.log missing');
		$logOutput = file_get_contents(LOGS . 'error.log');
		$this->assertContains($message, $logOutput);

		// TestConsoleLog is only interested in `error` type
		$message = 'Test info message';
		$mock->expects($this->never())
			->method('write');
		TestCakeLog::write(LOG_INFO, $message);

		// checks that output is correctly written in the correct logfile
		$this->assertTrue(file_exists(LOGS . 'error.log'), 'error.log missing');
		$this->assertTrue(file_exists(LOGS . 'debug.log'), 'debug.log missing');
		$logOutput = file_get_contents(LOGS . 'error.log');
		$this->assertNotContains($message, $logOutput);
		$logOutput = file_get_contents(LOGS . 'debug.log');
		$this->assertContains($message, $logOutput);
	}

/**
 * test default value of stream 'outputAs'
 */
	public function testDefaultOutputAs() {
		TestCakeLog::config('test_console_log', array(
			'engine' => 'TestConsoleLog',
			));
		if (DS === '\\' && !(bool)env('ANSICON')) {
			$expected = ConsoleOutput::PLAIN;
		} else {
			$expected = ConsoleOutput::COLOR;
		}
		$stream = TestCakeLog::stream('test_console_log');
		$config = $stream->config();
		$this->assertEquals($expected, $config['outputAs']);
	}

}
