<?php
declare(strict_types=1);

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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core\Configure\Engine;

use Cake\Core\Configure\Engine\JsonConfig;
use Cake\TestSuite\TestCase;

/**
 * JsonConfigTest
 */
class JsonConfigTest extends TestCase
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Test data to serialize and unserialize.
     *
     * @var array
     */
    protected $testData = [
        'One' => [
            'two' => 'value',
            'three' => [
                'four' => 'value four',
            ],
            'is_null' => null,
            'bool_false' => false,
            'bool_true' => true,
        ],
        'Asset' => [
            'timestamp' => 'force',
        ],
    ];

    /**
     * Setup.
     *
     * @return void
     */
    public function setUp(): void
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
        $engine = new JsonConfig($this->path);
        $values = $engine->read('json_test');
        $this->assertSame('value', $values['Json']);
        $this->assertSame('buried', $values['Deep']['Deeper']['Deepest']);
    }

    /**
     * Test an exception is thrown by reading files that exist without .php extension.
     *
     * @return void
     */
    public function testReadWithExistentFileWithoutExtension()
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $engine = new JsonConfig($this->path);
        $engine->read('no_json_extension');
    }

    /**
     * Test an exception is thrown by reading files that don't exist.
     *
     * @return void
     */
    public function testReadWithNonExistentFile()
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $engine = new JsonConfig($this->path);
        $engine->read('fake_values');
    }

    /**
     * Test reading an empty file.
     *
     * @return void
     */
    public function testReadEmptyFile()
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $this->expectExceptionMessage('config file "empty.json"');
        $engine = new JsonConfig($this->path);
        $config = $engine->read('empty');
    }

    /**
     * Test an exception is thrown by reading files that contain invalid JSON.
     *
     * @return void
     */
    public function testReadWithInvalidJson()
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $this->expectExceptionMessage('Error parsing JSON string fetched from config file "invalid.json"');
        $engine = new JsonConfig($this->path);
        $engine->read('invalid');
    }

    /**
     * Test reading keys with ../ doesn't work.
     *
     * @return void
     */
    public function testReadWithDots()
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $engine = new JsonConfig($this->path);
        $engine->read('../empty');
    }

    /**
     * Test reading from plugins.
     *
     * @return void
     */
    public function testReadPluginValue()
    {
        $this->loadPlugins(['TestPlugin']);
        $engine = new JsonConfig($this->path);
        $result = $engine->read('TestPlugin.load');
        $this->assertArrayHasKey('plugin_load', $result);

        $this->clearPlugins();
    }

    /**
     * Test dumping data to JSON format.
     *
     * @return void
     */
    public function testDump()
    {
        $engine = new JsonConfig(TMP);
        $result = $engine->dump('test', $this->testData);
        $this->assertGreaterThan(0, $result);
        $expected = '{
    "One": {
        "two": "value",
        "three": {
            "four": "value four"
        },
        "is_null": null,
        "bool_false": false,
        "bool_true": true
    },
    "Asset": {
        "timestamp": "force"
    }
}';
        $file = TMP . 'test.json';
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
        $engine = new JsonConfig(TMP);
        $engine->dump('test', $this->testData);
        $result = $engine->read('test');
        unlink(TMP . 'test.json');

        $this->assertEquals($this->testData, $result);
    }
}
