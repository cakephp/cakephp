<?php
/**
 * IniReaderTest
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'config/IniReader');

class IniReaderTest extends CakeTestCase {

/**
 * The test file that will be read.
 *
 * @var string
 */
	var $file;

/**
 * setup
 *
 * @return void
 */
	function setup() {
		parent::setup();
		$this->path = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS;
	}

/**
 * test constrction
 *
 * @return void
 */
	function testConstruct() {
		$reader = new IniReader($this->path);
		$config = $reader->read('acl.ini.php');

		$this->assertTrue(isset($config['admin']));
		$this->assertTrue(isset($config['paul']['groups']));
		$this->assertEquals('ads', $config['admin']['deny']);
	}

/**
 * no other sections should exist.
 *
 * @return void
 */
	function testReadingOnlyOneSection() {
		$reader = new IniReader($this->path, 'admin');
		$config = $reader->read('acl.ini.php');

		$this->assertTrue(isset($config['groups']));
		$this->assertEquals('administrators', $config['groups']);
	}

/**
 * test that names with .'s get exploded into arrays.
 *
 * @return void
 */
	function testReadingValuesWithDots() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested.ini');

		$this->assertTrue(isset($config['database']['db']['username']));
		$this->assertEquals('mark', $config['database']['db']['username']);
		$this->assertEquals(3, $config['nesting']['one']['two']['three']);
	}

/**
 * test boolean reading
 *
 * @return void
 */
	function testBooleanReading() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested.ini');

		$this->assertTrue($config['bools']['test_on']);
		$this->assertFalse($config['bools']['test_off']);

		$this->assertTrue($config['bools']['test_yes']);
		$this->assertFalse($config['bools']['test_no']);

		$this->assertTrue($config['bools']['test_true']);
		$this->assertFalse($config['bools']['test_false']);

		$this->assertFalse($config['bools']['test_null']);
	}
}