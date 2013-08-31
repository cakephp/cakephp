<?php
/**
 * IniEngineTest
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
 * @package       Cake.Test.Case.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Configure\Engine;

use Cake\Configure\IniEngine;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class IniEngineTest
 *
 * @package       Cake.Test.Case.Configure
 */
class IniEngineTest extends TestCase {

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
		$this->path = CAKE . 'Test/TestApp/Config' . DS;
	}

/**
 * test construct
 *
 * @return void
 */
	public function testConstruct() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('acl.ini');

		$this->assertTrue(isset($config['admin']));
		$this->assertTrue(isset($config['paul']['groups']));
		$this->assertEquals('ads', $config['admin']['deny']);
	}

/**
 * Test reading files.
 *
 * @return void
 */
	public function testRead() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('nested');
		$this->assertTrue($config['bools']['test_on']);

		$config = $engine->read('nested.ini');
		$this->assertTrue($config['bools']['test_on']);
	}

/**
 * No other sections should exist.
 *
 * @return void
 */
	public function testReadOnlyOneSection() {
		$engine = new IniEngine($this->path, 'admin');
		$config = $engine->read('acl.ini');

		$this->assertTrue(isset($config['groups']));
		$this->assertEquals('administrators', $config['groups']);
	}

/**
 * Test reading acl.ini.php.
 *
 * @return void
 */
	public function testReadSpecialAclIniPhp() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('acl.ini.php');

		$this->assertTrue(isset($config['admin']));
		$this->assertTrue(isset($config['paul']['groups']));
		$this->assertEquals('ads', $config['admin']['deny']);
	}

/**
 * Test without section.
 *
 * @return void
 */
	public function testReadWithoutSection() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('no_section.ini');

		$expected = array(
			'some_key' => 'some_value',
			'bool_key' => true
		);
		$this->assertEquals($expected, $config);
	}

/**
 * Test that names with .'s get exploded into arrays.
 *
 * @return void
 */
	public function testReadValuesWithDots() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('nested.ini');

		$this->assertTrue(isset($config['database']['db']['username']));
		$this->assertEquals('mark', $config['database']['db']['username']);
		$this->assertEquals(3, $config['nesting']['one']['two']['three']);
		$this->assertFalse(isset($config['database.db.username']));
		$this->assertFalse(isset($config['database']['db.username']));
	}

/**
 * Test boolean reading.
 *
 * @return void
 */
	public function testBooleanReading() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('nested.ini');

		$this->assertTrue($config['bools']['test_on']);
		$this->assertFalse($config['bools']['test_off']);

		$this->assertTrue($config['bools']['test_yes']);
		$this->assertFalse($config['bools']['test_no']);

		$this->assertTrue($config['bools']['test_true']);
		$this->assertFalse($config['bools']['test_false']);

		$this->assertFalse($config['bools']['test_null']);
	}

/**
 * Test an exception is thrown by reading files that exist without .ini extension.
 *
 * @expectedException Cake\Error\ConfigureException
 * @return void
 */
	public function testReadWithExistentFileWithoutExtension() {
		$engine = new IniEngine($this->path);
		$engine->read('no_ini_extension');
	}

/**
 * Test an exception is thrown by reading files that don't exist.
 *
 * @expectedException Cake\Error\ConfigureException
 * @return void
 */
	public function testReadWithNonExistentFile() {
		$engine = new IniEngine($this->path);
		$engine->read('fake_values');
	}

/**
 * Test reading an empty file.
 *
 * @return void
 */
	public function testReadEmptyFile() {
		$engine = new IniEngine($this->path);
		$config = $engine->read('empty');
		$this->assertEquals(array(), $config);
	}

/**
 * Test reading keys with ../ doesn't work.
 *
 * @expectedException Cake\Error\ConfigureException
 * @return void
 */
	public function testReadWithDots() {
		$engine = new IniEngine($this->path);
		$engine->read('../empty');
	}

/**
 * Test reading from plugins.
 *
 * @return void
 */
	public function testReadPluginValue() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Plugin::load('TestPlugin');
		$engine = new IniEngine($this->path);
		$result = $engine->read('TestPlugin.nested');

		$this->assertTrue(isset($result['database']['db']['username']));
		$this->assertEquals('bar', $result['database']['db']['username']);
		$this->assertFalse(isset($result['database.db.username']));
		$this->assertFalse(isset($result['database']['db.username']));

		$result = $engine->read('TestPlugin.nested.ini');
		$this->assertEquals('foo', $result['database']['db']['password']);
		Plugin::unload();
	}

/**
 * Test reading acl.ini.php from plugins.
 *
 * @return void
 */
	public function testReadPluginSpecialAclIniPhpValue() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Plugin::load('TestPlugin');
		$engine = new IniEngine($this->path);
		$result = $engine->read('TestPlugin.acl.ini.php');

		$this->assertTrue(isset($result['admin']));
		$this->assertTrue(isset($result['paul']['groups']));
		$this->assertEquals('ads', $result['admin']['deny']);
		Plugin::unload();
	}

/**
 * Test dump method.
 *
 * @return void
 */
	public function testDump() {
		$engine = new IniEngine(TMP);
		$result = $engine->dump('test.ini', $this->testData);
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

		$result = $engine->dump('test', $this->testData);
		$this->assertTrue($result > 0);

		$contents = file_get_contents($file);
		$this->assertTextEquals($expected, $contents);
		unlink($file);
	}

/**
 * Test that dump() makes files read() can read.
 *
 * @return void
 */
	public function testDumpRead() {
		$engine = new IniEngine(TMP);
		$engine->dump('test.ini', $this->testData);
		$result = $engine->read('test.ini');
		unlink(TMP . 'test.ini');

		$expected = $this->testData;
		$expected['One']['is_null'] = false;

		$this->assertEquals($expected, $result);
	}

}
