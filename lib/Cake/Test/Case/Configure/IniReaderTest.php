<?php
/**
 * IniReaderTest
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
 * @package       Cake.Test.Case.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('IniReader', 'Configure');

class IniReaderTest extends CakeTestCase {

/**
 * Test data to serialize and unserialize.
 *
 * @var array
 */
	public $testData = array(
		'One' => array(
			'two' => 'value',
			'three' => array(
				'four' => 'value four'
			),
			'is_null' => null,
			'bool_false' => false,
			'bool_true' => true,
		),
		'Asset' => array(
			'timestamp' => 'force'
		),
	);

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->path = CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS;
	}

/**
 * test construct
 *
 * @return void
 */
	public function testConstruct() {
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
	public function testReadingOnlyOneSection() {
		$reader = new IniReader($this->path, 'admin');
		$config = $reader->read('acl.ini.php');

		$this->assertTrue(isset($config['groups']));
		$this->assertEquals('administrators', $config['groups']);
	}

/**
 * test without section
 *
 * @return void
 */
	public function testReadingWithoutSection() {
		$reader = new IniReader($this->path);
		$config = $reader->read('no_section.ini');

		$expected = array(
			'some_key' => 'some_value',
			'bool_key' => true
		);
		$this->assertEquals($expected, $config);
	}

/**
 * test that names with .'s get exploded into arrays.
 *
 * @return void
 */
	public function testReadingValuesWithDots() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested.ini');

		$this->assertTrue(isset($config['database']['db']['username']));
		$this->assertEquals('mark', $config['database']['db']['username']);
		$this->assertEquals(3, $config['nesting']['one']['two']['three']);
		$this->assertFalse(isset($config['database.db.username']));
		$this->assertFalse(isset($config['database']['db.username']));
	}

/**
 * test boolean reading
 *
 * @return void
 */
	public function testBooleanReading() {
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

/**
 * test read file without extension
 *
 * @return void
 */
	public function testReadingWithoutExtension() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested');
		$this->assertTrue($config['bools']['test_on']);
	}

/**
 * test dump method.
 *
 * @return void
 */
	public function testDump() {
		$reader = new IniReader(TMP);
		$result = $reader->dump('test.ini', $this->testData);
		$this->assertTrue($result > 0);

		$expected = <<<INI
[One]
two = value
three.four = value four
is_null = null
bool_false = false
bool_true = true
[Asset]
timestamp = force
INI;
		$file = TMP . 'test.ini';
		$result = file_get_contents($file);
		unlink($file);

		$this->assertTextEquals($expected, $result);
	}

/**
 * Test that dump() makes files read() can read.
 *
 * @return void
 */
	public function testDumpRead() {
		$reader = new IniReader(TMP);
		$reader->dump('test.ini', $this->testData);
		$result = $reader->read('test.ini');
		unlink(TMP . 'test.ini');

		$expected = $this->testData;
		$expected['One']['is_null'] = false;

		$this->assertEquals($expected, $result);
	}

}
