<?php
/**
 * PhpConfigReaderTest
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

App::uses('PhpReader', 'Configure');

/**
 * Class PhpReaderTest
 *
 * @package       Cake.Test.Case.Configure
 */
class PhpReaderTest extends CakeTestCase {

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
 * Setup.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->path = CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS;
	}

/**
 * Test reading files.
 *
 * @return void
 */
	public function testRead() {
		$reader = new PhpReader($this->path);
		$values = $reader->read('var_test');
		$this->assertEquals('value', $values['Read']);
		$this->assertEquals('buried', $values['Deep']['Deeper']['Deepest']);

		$values = $reader->read('var_test.php');
		$this->assertEquals('value', $values['Read']);
	}

/**
 * Test an exception is thrown by reading files that exist without .php extension.
 *
 * @expectedException ConfigureException
 * @return void
 */
	public function testReadWithExistentFileWithoutExtension() {
		$reader = new PhpReader($this->path);
		$reader->read('no_php_extension');
	}

/**
 * Test an exception is thrown by reading files that don't exist.
 *
 * @expectedException ConfigureException
 * @return void
 */
	public function testReadWithNonExistentFile() {
		$reader = new PhpReader($this->path);
		$reader->read('fake_values');
	}

/**
 * Test reading an empty file.
 *
 * @expectedException ConfigureException
 * @return void
 */
	public function testReadEmptyFile() {
		$reader = new PhpReader($this->path);
		$reader->read('empty');
	}

/**
 * Test reading keys with ../ doesn't work.
 *
 * @expectedException ConfigureException
 * @return void
 */
	public function testReadWithDots() {
		$reader = new PhpReader($this->path);
		$reader->read('../empty');
	}

/**
 * Test reading from plugins.
 *
 * @return void
 */
	public function testReadPluginValue() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');
		$reader = new PhpReader($this->path);
		$result = $reader->read('TestPlugin.load');
		$this->assertTrue(isset($result['plugin_load']));

		$result = $reader->read('TestPlugin.load.php');
		$this->assertTrue(isset($result['plugin_load']));
		CakePlugin::unload();
	}

/**
 * Test dumping data to PHP format.
 *
 * @return void
 */
	public function testDump() {
		$reader = new PhpReader(TMP);
		$result = $reader->dump('test.php', $this->testData);
		$this->assertTrue($result > 0);
		$expected = <<<PHP
<?php
\$config = array (
  'One' => 
  array (
    'two' => 'value',
    'three' => 
    array (
      'four' => 'value four',
    ),
    'is_null' => NULL,
    'bool_false' => false,
    'bool_true' => true,
  ),
  'Asset' => 
  array (
    'timestamp' => 'force',
  ),
);
PHP;
		$file = TMP . 'test.php';
		$contents = file_get_contents($file);

		unlink($file);
		$this->assertTextEquals($expected, $contents);

		$result = $reader->dump('test', $this->testData);
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
		$reader = new PhpReader(TMP);
		$reader->dump('test.php', $this->testData);
		$result = $reader->read('test.php');
		unlink(TMP . 'test.php');

		$this->assertEquals($this->testData, $result);
	}

}
