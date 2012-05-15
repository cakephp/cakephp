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

		CakeLog::write(LOG_INFO, 'TestPluginLog is not a BaseLog descendant');

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
		$this->assertEquals(array('default'), $result);

		$testMessage = 'custom message';
		CakeLog::write('custom', $testMessage);
		$content = file_get_contents(LOGS . 'custom.log');
		$this->assertContains($testMessage, $content);
		unlink(LOGS . 'error.log');
		unlink(LOGS . 'custom.log');
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
 * test selective logging by level/type
 *
 * @return void
 */
	public function testSelectiveLoggingByLevel() {
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
 *
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
 *
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
 *
 * @expectedException CakeLogException
 */
	public function testStreamEnabledInvalid() {
		CakeLog::enabled('bogus_stream');
	}

/**
 * test disable invalid stream
 *
 * @expectedException CakeLogException
 */
	public function testStreamDisableInvalid() {
		CakeLog::disable('bogus_stream');
	}

	protected function _resetLogConfig() {
		CakeLog::config('debug', array(
			'engine' => 'FileLog',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		CakeLog::config('error', array(
			'engine' => 'FileLog',
			'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
			'file' => 'error',
		));
	}

	protected function _deleteLogs() {
		if (file_exists(LOGS . 'shops.log')) {
			unlink(LOGS . 'shops.log');
		}
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
		if (file_exists(LOGS . 'bogus.log')) {
			unlink(LOGS . 'bogus.log');
		}
		if (file_exists(LOGS . 'spam.log')) {
			unlink(LOGS . 'spam.log');
		}
		if (file_exists(LOGS . 'eggs.log')) {
			unlink(LOGS . 'eggs.log');
		}
	}

/**
 * test backward compatible scoped logging
 */
	public function testScopedLoggingBC() {
		$this->_deleteLogs();

		$this->_resetLogConfig();
		CakeLog::config('shops', array(
			'engine' => 'FileLog',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
			));

		CakeLog::write('info', 'info message');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('transactions', 'transaction message');
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'transactions.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('error', 'error message');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('orders', 'order message');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'orders.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('warning', 'warning message');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::drop('shops');
	}

/**
 * test scoped logging
 *
 * @return void
 */
	public function testScopedLogging() {
		if (file_exists(LOGS . 'shops.log')) {
			unlink(LOGS . 'shops.log');
		}
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		$this->_resetLogConfig();
		CakeLog::config('shops', array(
			'engine' => 'FileLog',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
			));

		CakeLog::write('info', 'info message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('transactions', 'transaction message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'transactions.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::write('error', 'error message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('orders', 'order message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'orders.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::write('warning', 'warning message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::drop('shops');
	}

/**
 * test bogus type and scope
 *
 */
	public function testBogusTypeAndScope() {
		$this->_resetLogConfig();
		$this->_deleteLogs();

		CakeLog::write('bogus', 'bogus message');
		$this->assertTrue(file_exists(LOGS . 'bogus.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		CakeLog::write('bogus', 'bogus message', 'bogus');
		$this->assertTrue(file_exists(LOGS . 'bogus.log'));
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		CakeLog::write('error', 'bogus message', 'bogus');
		$this->assertFalse(file_exists(LOGS . 'bogus.log'));
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();
	}

/**
 * test scoped logging with convenience methods
 */
	public function testConvenienceScopedLogging() {
		if (file_exists(LOGS . 'shops.log')) {
			unlink(LOGS . 'shops.log');
		}
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}

		$this->_resetLogConfig();
		CakeLog::config('shops', array(
			'engine' => 'FileLog',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
			));

		CakeLog::info('info message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::error('error message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		CakeLog::warning('warning message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		CakeLog::drop('shops');
	}

/**
 * test convenience methods
 */
	public function testConvenienceMethods() {
		$this->_deleteLogs();

		CakeLog::config('debug', array(
			'engine' => 'FileLog',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		CakeLog::config('error', array(
			'engine' => 'FileLog',
			'types' => array('emergency', 'alert', 'critical', 'error', 'warning'),
			'file' => 'error',
		));

		$testMessage = 'emergency message';
		CakeLog::emergency($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Emergency: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'alert message';
		CakeLog::alert($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Alert: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'critical message';
		CakeLog::critical($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Critical: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'error message';
		CakeLog::error($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Error: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'warning message';
		CakeLog::warning($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Warning: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'notice message';
		CakeLog::notice($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertContains('Notice: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();

		$testMessage = 'info message';
		CakeLog::info($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertContains('Info: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();

		$testMessage = 'debug message';
		CakeLog::debug($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertContains('Debug: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();
	}

/**
 * test levels customization
 */
	public function testLevelCustomization() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Log level tests not supported on Windows.');

		$levels = CakeLog::defaultLevels();
		$this->assertNotEmpty($levels);
		$result = array_keys($levels);
		$this->assertEquals(array(0, 1, 2, 3, 4, 5, 6, 7), $result);

		$levels = CakeLog::levels(array('foo', 'bar'));
		CakeLog::defaultLevels();
		$this->assertEquals('foo', $levels[8]);
		$this->assertEquals('bar', $levels[9]);

		$levels = CakeLog::levels(array(11 => 'spam', 'bar' => 'eggs'));
		CakeLog::defaultLevels();
		$this->assertEquals('spam', $levels[8]);
		$this->assertEquals('eggs', $levels[9]);

		$levels = CakeLog::levels(array(11 => 'spam', 'bar' => 'eggs'), false);
		CakeLog::defaultLevels();
		$this->assertEquals(array('spam', 'eggs'), $levels);

		$levels = CakeLog::levels(array('ham', 9 => 'spam', '12' => 'fam'), false);
		CakeLog::defaultLevels();
		$this->assertEquals(array('ham', 'spam', 'fam'), $levels);
	}

/**
 * Test writing log files with custom levels
 */
	public function testCustomLevelWrites() {
		$this->_deleteLogs();
		$this->_resetLogConfig();

		$levels = CakeLog::levels(array('spam', 'eggs'));

		$testMessage = 'error message';
		CakeLog::write('error', $testMessage);
		CakeLog::defaultLevels();
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Error: ' . $testMessage, $contents);

		CakeLog::config('spam', array(
			'engine' => 'FileLog',
			'file' => 'spam.log',
			'types' => 'spam',
			));
		CakeLog::config('eggs', array(
			'engine' => 'FileLog',
			'file' => 'eggs.log',
			'types' => array('spam', 'eggs'),
			));

		$testMessage = 'spam message';
		CakeLog::write('spam', $testMessage);
		CakeLog::defaultLevels();
		$this->assertTrue(file_exists(LOGS . 'spam.log'));
		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertContains('Spam: ' . $testMessage, $contents);

		$testMessage = 'egg message';
		CakeLog::write('eggs', $testMessage);
		CakeLog::defaultLevels();
		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertNotContains('Eggs: ' . $testMessage, $contents);
		$contents = file_get_contents(LOGS . 'eggs.log');
		$this->assertContains('Eggs: ' . $testMessage, $contents);

		CakeLog::drop('spam');
		CakeLog::drop('eggs');

		$this->_deleteLogs();
	}

}
