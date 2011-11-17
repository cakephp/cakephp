<?php
/**
 * CakeLogTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
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
			'libs' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), true);
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
		$this->assertEquals($result, array('default'));
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
		$this->assertEquals($result, array('file'));

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
 * explict tests for drop()
 *
 * @return void
 **/
	public function testDrop() {
		CakeLog::config('file', array(
			'engine' => 'FileLog',
			'path' => LOGS
		));
		$result = CakeLog::configured();
		$this->assertEquals($result, array('file'));

		CakeLog::drop('file');
		$result = CakeLog::configured();
		$this->assertEquals($result, array());
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

}
