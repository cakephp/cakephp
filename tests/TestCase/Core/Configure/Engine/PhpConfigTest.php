<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core\Configure\Engine;

use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * PhpConfigTest
 */
class PhpConfigTest extends TestCase
{

    /**
     * Test data to serialize and unserialize.
     *
     * @var array
     */
    public $testData = [
        'One' => [
            'two' => 'value',
            'three' => [
                'four' => 'value four'
            ],
            'is_null' => null,
            'bool_false' => false,
            'bool_true' => true,
        ],
        'Asset' => [
            'timestamp' => 'force'
        ],
    ];

    /**
     * Setup.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->path = CONFIG;
    }

    /**
     * Test reading files.
     *
     * @return void
     */
    public function testRead()
    {
        $engine = new PhpConfig($this->path);
        $values = $engine->read('var_test');
        $this->assertEquals('value', $values['Read']);
        $this->assertEquals('buried', $values['Deep']['Deeper']['Deepest']);
    }

    /**
     * Test an exception is thrown by reading files that exist without .php extension.
     *
     * @return void
     */
    public function testReadWithExistentFileWithoutExtension()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new PhpConfig($this->path);
        $engine->read('no_php_extension');
    }

    /**
     * Test an exception is thrown by reading files that don't exist.
     *
     * @return void
     */
    public function testReadWithNonExistentFile()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new PhpConfig($this->path);
        $engine->read('fake_values');
    }

    /**
     * Test reading an empty file.
     *
     * @return void
     */
    public function testReadEmptyFile()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new PhpConfig($this->path);
        $engine->read('empty');
    }

    /**
     * Test reading keys with ../ doesn't work.
     *
     * @return void
     */
    public function testReadWithDots()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new PhpConfig($this->path);
        $engine->read('../empty');
    }

    /**
     * Test reading from plugins.
     *
     * @return void
     */
    public function testReadPluginValue()
    {
        Plugin::load('TestPlugin');
        $engine = new PhpConfig($this->path);
        $result = $engine->read('TestPlugin.load');
        $this->assertTrue(isset($result['plugin_load']));

        Plugin::unload();
    }

    /**
     * Test dumping data to PHP format.
     *
     * @return void
     */
    public function testDump()
    {
        $engine = new PhpConfig(TMP);
        $result = $engine->dump('test', $this->testData);
        $this->assertGreaterThan(0, $result);
        $expected = <<<PHP
<?php
return array (
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
        $this->assertGreaterThan(0, $result);

        $contents = file_get_contents($file);
        $this->assertTextEquals($expected, $contents);
        unlink($file);
    }

    /**
     * Test that dump() makes files read() can read.
     *
     * @return void
     */
    public function testDumpRead()
    {
        $engine = new PhpConfig(TMP);
        $engine->dump('test', $this->testData);
        $result = $engine->read('test');
        unlink(TMP . 'test.php');

        $this->assertEquals($this->testData, $result);
    }
}
