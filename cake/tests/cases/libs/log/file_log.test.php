<?php
/**
 * FileLogTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.log
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once LIBS . 'log' . DS . 'file_log.php';

/**
 * CakeLogTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class FileLogTest extends CakeTestCase {

/**
 * testLogFileWriting method
 *
 * @access public
 * @return void
 */
	function testLogFileWriting() {
		@unlink(LOGS . 'error.log');
		$log =& new FileLog();
		$log->write('warning', 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning/', $result);
		unlink(LOGS . 'error.log');

		@unlink(LOGS . 'debug.log');
		$log->write('debug', 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'debug.log'));

		$result = file_get_contents(LOGS . 'debug.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Test warning/', $result);
		unlink(LOGS . 'debug.log');

		@unlink(LOGS . 'random.log');
		$log->write('random', 'Test warning');
		$this->assertTrue(file_exists(LOGS . 'random.log'));

		$result = file_get_contents(LOGS . 'random.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Random: Test warning/', $result);
		unlink(LOGS . 'random.log');
	}

/**
 * test using the path setting to write logs in other places.
 *
 * @return void
 */
	function testPathSetting() {
		$path = TMP . 'tests' . DS;
		@unlink($path . 'error.log');

		$log =& new FileLog(compact('path'));
		$log->write('warning', 'Test warning');
		$this->assertTrue(file_exists($path . 'error.log'));
		unlink($path . 'error.log');
	}

}
?>