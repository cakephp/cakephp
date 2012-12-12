<?php
/**
 * CakePHP(tm) <http://book.cakephp.org/2.0/en/development/testing.html>
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
namespace Cake\Test\TestCase\Log;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Engine\FileLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
 * LogTest class
 *
 * @package       Cake.Test.Case.Log
 */
class LogTest extends TestCase {

/**
 * Start test callback, clears all streams enabled.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Log::reset();
	}

/**
 * test importing loggers from app/libs and plugins.
 *
 * @return void
 */
	public function testImportingLoggers() {
		App::build(array(
			'Lib' => array(CAKE . 'Test/TestApp/Lib/'),
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Configure::write('App.namespace', 'TestApp');
		Plugin::load('TestPlugin');

		Configure::write('Log.libtest', array(
			'engine' => 'TestAppLog'
		));
		Configure::write('Log.plugintest', array(
			'engine' => 'TestPlugin.TestPluginLog'
		));

		$result = Log::engine('libtest');
		$this->assertInstanceOf('TestApp\Log\Engine\TestAppLog', $result);
		$this->assertContains('libtest', Log::configured());

		$result = Log::engine('plugintest');
		$this->assertInstanceOf('TestPlugin\Log\Engine\TestPluginLog', $result);
		$this->assertContains('libtest', Log::configured());
		$this->assertContains('plugintest', Log::configured());

		Log::write(LOG_INFO, 'TestPluginLog is not a BaseLog descendant');

		App::build();
		Plugin::unload();
	}

/**
 * test all the errors from failed logger imports
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testImportingLoggerFailure() {
		Configure::write('Log.fail', array());
		Log::engine('fail');
	}

/**
 * test config() with valid key name
 *
 * @return void
 */
	public function testValidKeyName() {
		Configure::write('Log.valid', array('engine' => 'FileLog'));
		$stream = Log::engine('valid');
		$this->assertInstanceOf('Cake\Log\Engine\FileLog', $stream);
	}

/**
 * test that loggers have to implement the correct interface.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testNotImplementingInterface() {
		Configure::write('Log.fail', array('engine' => '\stdClass'));
		Log::engine('fail');
	}

/**
 * explicit tests for drop()
 *
 * @return void
 **/
	public function testDrop() {
		Configure::write('Log.file', array(
			'engine' => 'FileLog',
			'path' => LOGS
		));
		$result = Log::configured();
		$this->assertContains('file', $result);

		Configure::delete('Log.file');
		Log::drop('file');

		$result = Log::configured();
		$this->assertNotContains('file', $result);
	}

/**
 * Test that engine() throws an exception when adding an
 * adapter with the wrong type.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testEngineInjectErrorOnWrongType() {
		Log::engine('test', new \StdClass);
	}

/**
 * Test that engine() can add logger instances.
 *
 * @return void
 */
	public function testEngineInjectInstance() {
		$logger = new FileLog();
		Log::engine('test', $logger);
		$result = Log::engine('test');
		$this->assertSame($logger, $result);
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
		$result = Log::write(LOG_WARNING, 'Test warning');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		unlink(LOGS . 'error.log');

		Log::write(LOG_WARNING, 'Test warning 1');
		Log::write(LOG_WARNING, 'Test warning 2');
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
		Configure::write('Log.spam', array(
			'engine' => 'FileLog',
			'types' => 'debug',
			'file' => 'spam',
		));
		Configure::write('Log.eggs', array(
			'engine' => 'FileLog',
			'types' => array('eggs', 'debug', 'error', 'warning'),
			'file' => 'eggs',
		));

		$testMessage = 'selective logging';
		Log::write(LOG_WARNING, $testMessage);

		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$this->assertFalse(file_exists(LOGS . 'spam.log'));

		Log::write(LOG_DEBUG, $testMessage);
		$this->assertTrue(file_exists(LOGS . 'spam.log'));

		$contents = file_get_contents(LOGS . 'spam.log');
		$this->assertContains('Debug: ' . $testMessage, $contents);
		$contents = file_get_contents(LOGS . 'eggs.log');
		$this->assertContains('Debug: ' . $testMessage, $contents);

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
 * @expectedException Cake\Error\Exception
 */
	public function testStreamEnable() {
		Configure::write('Log.spam', array(
			'engine' => 'FileLog',
			'file' => 'spam',
		));
		$this->assertTrue(Log::enabled('spam'));
		Log::drop('spam');
		Log::enable('bogus_stream');
	}

/**
 * test disable
 *
 * @expectedException Cake\Error\Exception
 */
	public function testStreamDisable() {
		Configure::write('Log.spam', array(
			'engine' => 'FileLog',
			'file' => 'spam',
		));
		$this->assertTrue(Log::enabled('spam'));
		Log::disable('spam');
		$this->assertFalse(Log::enabled('spam'));
		Log::drop('spam');
		Log::enable('bogus_stream');
	}

/**
 * test enabled() invalid stream
 *
 * @expectedException Cake\Error\Exception
 */
	public function testStreamEnabledInvalid() {
		Log::enabled('bogus_stream');
	}

/**
 * test disable invalid stream
 *
 * @expectedException Cake\Error\Exception
 */
	public function testStreamDisableInvalid() {
		Log::disable('bogus_stream');
	}

	protected function _resetLogConfig() {
		Configure::write('Log.debug', array(
			'engine' => 'FileLog',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		Configure::write('Log.error', array(
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
 * test scoped logging
 *
 * @return void
 */
	public function testScopedLogging() {
		$this->_deleteLogs();
		$this->_resetLogConfig();
		Configure::write('Log.shops', array(
			'engine' => 'FileLog',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
		));

		Log::write('info', 'info message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		Log::write('warning', 'warning message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		Log::write('error', 'error message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		Log::drop('shops');
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
		Configure::write('Log.shops', array(
			'engine' => 'FileLog',
			'types' => array('info', 'debug', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops',
		));

		Log::info('info message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		Log::error('error message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		Log::warning('warning message', 'orders');
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));
		$this->assertFalse(file_exists(LOGS . 'debug.log'));

		$this->_deleteLogs();

		Log::drop('shops');
	}

/**
 * Test that scopes are exclusive and don't bleed.
 *
 * @return void
 */
	public function testScopedLoggingExclusive() {
		$this->_deleteLogs();

		Configure::write('Log.shops', array(
			'engine' => 'FileLog',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('transactions', 'orders'),
			'file' => 'shops.log',
		));
		Configure::write('Log.eggs', array(
			'engine' => 'FileLog',
			'types' => array('info', 'notice', 'warning'),
			'scopes' => array('eggs'),
			'file' => 'eggs.log',
		));

		Log::write('info', 'transactions message', 'transactions');
		$this->assertFalse(file_exists(LOGS . 'eggs.log'));
		$this->assertTrue(file_exists(LOGS . 'shops.log'));

		$this->_deleteLogs();

		Log::write('info', 'eggs message', 'eggs');
		$this->assertTrue(file_exists(LOGS . 'eggs.log'));
		$this->assertFalse(file_exists(LOGS . 'shops.log'));
	}

/**
 * test convenience methods
 */
	public function testConvenienceMethods() {
		$this->_deleteLogs();

		Configure::write('Log.debug', array(
			'engine' => 'FileLog',
			'types' => array('notice', 'info', 'debug'),
			'file' => 'debug',
		));
		Configure::write('Log.error', array(
			'engine' => 'FileLog',
			'types' => array('emergency', 'alert', 'critical', 'error', 'warning'),
			'file' => 'error',
		));

		$testMessage = 'emergency message';
		Log::emergency($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertRegExp('/(Emergency|Critical): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'alert message';
		Log::alert($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertRegExp('/(Alert|Critical): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'critical message';
		Log::critical($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Critical: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'error message';
		Log::error($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Error: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'warning message';
		Log::warning($testMessage);
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains('Warning: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'debug.log'));
		$this->_deleteLogs();

		$testMessage = 'notice message';
		Log::notice($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertRegExp('/(Notice|Debug): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();

		$testMessage = 'info message';
		Log::info($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertRegExp('/(Info|Debug): ' . $testMessage . '/', $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();

		$testMessage = 'debug message';
		Log::debug($testMessage);
		$contents = file_get_contents(LOGS . 'debug.log');
		$this->assertContains('Debug: ' . $testMessage, $contents);
		$this->assertFalse(file_exists(LOGS . 'error.log'));
		$this->_deleteLogs();
	}

/**
 * Test that write() returns false on an unhandled message.
 *
 * @return false
 */
	public function testWriteUnhandled() {
		Log::drop('error');
		Log::drop('debug');

		$result = Log::write('error', 'Bad stuff', 'unpossible');
		$this->assertFalse($result);
	}

}
