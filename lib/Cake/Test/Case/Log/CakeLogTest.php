<?php
/**
 * CakeLogTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Log
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeLog', 'Log');
App::uses('FileLog', 'Log/Engine');

/**
 * CakeLogTest class
 *
 * @package       Cake.Test.Case.Log
 */
class CakeLogTest extends CakeTestCase {

/**
 * Start test callback, clears all streams enabled.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
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
	public function testImportingLoggers() {
		App::build(array(
			'Lib' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$result = CakeLog::config('libtest', array(
			'engine' => 'TestAppLog'
		));
		$this->assertTrue($result);
		$this->assertEquals(CakeLog::configured(), array('libtest'));

		$result = CakeLog::config('plugintest', array(
			'engine' => 'TestPlugin.TestPluginLog'
		));
		$this->assertTrue($result);
		$this->assertEquals(CakeLog::configured(), array('libtest', 'plugintest'));

		App::build();
		CakePlugin::unload();
	}

/**
 * test all the errors from failed logger imports
 *
 * @expectedException CakeLogException
 * @return void
 */
	public function testImportingLoggerFailure() {
		CakeLog::config('fail', array());
	}

/**
 * test config() with valid key name
 *
 * @return void
 */
	public function testValidKeyName() {
		CakeLog::config('valid', array('engine' => 'FileLog'));
		$stream = CakeLog::stream('valid');
		$this->assertInstanceOf('FileLog', $stream);
		CakeLog::drop('valid');
	}

/**
 * test config() with invalid key name
 *
 * @expectedException CakeLogException
 * @return void
 */
	public function testInvalidKeyName() {
		CakeLog::config('1nv', array('engine' => 'FileLog'));
	}

/**
 * test that loggers have to implement the correct interface.
 *
 * @expectedException CakeLogException
 * @return void
 */
	public function testNotImplementingInterface() {
		CakeLog::config('fail', array('engine' => 'stdClass'));
	}

/**
 * Test that CakeLog autoconfigures itself to use a FileLogger with the LOGS dir.
 * When no streams are there.
 *
 * @return void
 */
	public function testAutoConfig() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = CakeLog::configured();
		$this->assertEquals(array('error'), $result);
		unlink(LOGS . 'error.log');
	}

/**
 * test configuring log streams
 *
 * @return void
 */
	public function testConfig() {
		CakeLog::config('file', array(
			'engine' => 'FileLog',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEquals(array('file'), $result);

		if (file_exists(LOGS . 'error.log')) {
			@unlink(LOGS . 'error.log');
		}
		CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning/', $result);
		unlink(LOGS . 'error.log');
	}

/**
 * explicit tests for drop()
 *
 * @return void
 **/
	public function testDrop() {
		CakeLog::config('file', array(
			'engine' => 'FileLog',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEquals(array('file'), $result);

		CakeLog::drop('file');
		$result = CakeLog::configured();
		$this->assertEquals(array(), $result);
	}

/**
 * testLogFileWriting method
 *
 * @return void
 */
	public function testLogFileWriting() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		$result = CakeLog::write(LOG_WARNING, 'Test warning');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		unlink(LOGS . 'error.log');

		CakeLog::write(LOG_WARNING, 'Test warning 1');
		CakeLog::write(LOG_WARNING, 'Test warning 2');
		$result = file_get_contents(LOGS . 'error.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1/', $result);
		$this->assertRegExp('/2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 2$/', $result);
		unlink(LOGS . 'error.log');
	}

/**
 * test selective logging
 */
	public function testSelectiveLogging() {
		if (file_exists(LOGS . 'spam.log')) {
			unlink(LOGS . 'spam.log');
		}
		if (file_exists(LOGS . 'eggs.log')) {
			unlink(LOGS . 'eggs.log');
		}
		CakeLog::config('spam', array(
			'engine' => 'FileLog',
			'types' => 'info',
			'file' => 'spam',
		));
		CakeLog::config('eggs', array(
			'engine' => 'FileLog',
			'types' => array('eggs', 'info', 'error', 'warning'),
			'file' => 'eggs',
		));

		$testMessage = 'selective logging';
		CakeLog::write(LOG_WARNING, $testMessage);

		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$this->assertFalse(file_exists(LOGS . 'spam.log'));

		CakeLog::write(LOG_INFO, $testMessage);
		$this->assertTrue(file_exists(LOGS . 'spam.log'));

		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertContains('Info: ' . $testMessage, $contents);
		$contents = file_get_contents(LOGS . 'eggs.log');
		$this->assertContains('Info: ' . $testMessage, $contents);

		if (file_exists(LOGS . 'spam.log')) {
			unlink(LOGS . 'spam.log');
		}
		if (file_exists(LOGS . 'eggs.log')) {
			unlink(LOGS . 'eggs.log');
		}
	}

/**
 * test enable
 * @expectedException CakeLogException
 */
	public function testStreamEnable() {
		CakeLog::config('spam', array(
			'engine' => 'FileLog',
			'file' => 'spam',
			));
		$this->assertTrue(CakeLog::enabled('spam'));
		CakeLog::drop('spam');
		CakeLog::enable('bogus_stream');
	}

/**
 * test disable
 * @expectedException CakeLogException
 */
	public function testStreamDisable() {
		CakeLog::config('spam', array(
			'engine' => 'FileLog',
			'file' => 'spam',
			));
		$this->assertTrue(CakeLog::enabled('spam'));
		CakeLog::disable('spam');
		$this->assertFalse(CakeLog::enabled('spam'));
		CakeLog::drop('spam');
		CakeLog::enable('bogus_stream');
	}

/**
 * test enabled() invalid stream
 * @expectedException CakeLogException
 */
	public function testStreamEnabledInvalid() {
		CakeLog::enabled('bogus_stream');
	}

/**
 * test disable invalid stream
 * @expectedException CakeLogException
 */
	public function testStreamDisableInvalid() {
		CakeLog::disable('bogus_stream');
	}

}
