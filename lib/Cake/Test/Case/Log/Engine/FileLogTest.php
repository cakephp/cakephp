<?php
/**
 * FileLogTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Log.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('FileLog', 'Log/Engine');

/**
 * CakeLogTest class
 *
 * @package       Cake.Test.Case.Log.Engine
 */
class FileLogTest extends CakeTestCase {

/**
 * testLogFileWriting method
 *
 * @return void
 */
	public function testLogFileWriting() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		$log = new FileLog();
		$log->write('warning', 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning/', $result);
		unlink(LOGS . 'error.log');

		if (file_exists(LOGS . 'debug.log')) {
			unlink(LOGS . 'debug.log');
		}
		$log->write('debug', 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Test warning/', $result);
		unlink(LOGS . 'debug.log');

		if (file_exists(LOGS . 'random.log')) {
			unlink(LOGS . 'random.log');
		}
		$log->write('random', 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'random.log'));

		$result = file_get_contents(LOGS . 'random.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Random: Test warning/', $result);
		unlink(LOGS . 'random.log');
	}

/**
 * test using the path setting to write logs in other places.
 *
 * @return void
 */
	public function testPathSetting() {
		$path = TMP . 'tests' . DS;
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}

		$log = new FileLog(compact('path'));
		$log->write('warning', 'Test warning');
		$this->assertTrue(file_exists($path . 'error.log'));
		unlink($path . 'error.log');
	}

}
