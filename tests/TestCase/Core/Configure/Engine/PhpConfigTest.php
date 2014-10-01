<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core\Configure\Engine;

use Cake\Core\App;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class PhpConfigTest
 *
 */
class PhpConfigTest extends TestCase {

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
		$this->path = CONFIG;
	}

/**
 * Test reading files.
 *
 * @return void
 */
	public function testRead() {
		$engine = new PhpConfig($this->path);
		$values = $engine->read('var_test');
		$this->assertEquals('value', $values['Read']);
		$this->assertEquals('buried', $values['Deep']['Deeper']['Deepest']);

		$values = $engine->read('var_test.php');
		$this->assertEquals('value', $values['Read']);
	}

/**
 * Test an exception is thrown by reading files that exist without .php extension.
 *
 * @expectedException \Cake\Core\Exception\Exception
 * @return void
 */
	public function testReadWithExistentFileWithoutExtension() {
		$engine = new PhpConfig($this->path);
		$engine->read('no_php_extension');
	}

/**
 * Test an exception is thrown by reading files that don't exist.
 *
 * @expectedException \Cake\Core\Exception\Exception
 * @return void
 */
	public function testReadWithNonExistentFile() {
		$engine = new PhpConfig($this->path);
		$engine->read('fake_values');
	}

/**
 * Test reading an empty file.
 *
 * @expectedException \Cake\Core\Exception\Exception
 * @return void
 */
	public function testReadEmptyFile() {
		$engine = new PhpConfig($this->path);
		$engine->read('empty');
	}

/**
 * Test reading keys with ../ doesn't work.
 *
 * @expectedException \Cake\Core\Exception\Exception
 * @return void
 */
	public function testReadWithDots() {
		$engine = new PhpConfig($this->path);
		$engine->read('../empty');
	}

/**
 * Test reading from plugins.
 *
 * @return void
 */
	public function testReadPluginValue() {
		Plugin::load('TestPlugin');
		$engine = new PhpConfig($this->path);
		$result = $engine->read('TestPlugin.load');
		$this->assertTrue(isset($result['plugin_load']));

		$result = $engine->read('TestPlugin.load.php');
		$this->assertTrue(isset($result['plugin_load']));
		Plugin::unload();
	}

/**
 * Test dumping data to PHP format.
 *
 * @return void
 */
	public function testDump() {
		$engine = new PhpConfig(TMP);
		$result = $engine->dump('test.php', $this->testData);
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
		$engine = new PhpConfig(TMP);
		$engine->dump('test.php', $this->testData);
		$result = $engine->read('test.php');
		unlink(TMP . 'test.php');

		$this->assertEquals($this->testData, $result);
	}

}
