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
use Cake\Core\Exception\CakeException;
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
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->path = CONFIG;
    }

    /**
     * Test reading files.
     */
    public function testRead(): void
    {
        $engine = new JsonConfig($this->path);
        $values = $engine->read('json_test');
        $this->assertSame('value', $values['Json']);
        $this->assertSame('buried', $values['Deep']['Deeper']['Deepest']);
    }

    /**
     * Test an exception is thrown by reading files that exist without .php extension.
     */
    public function testReadWithExistentFileWithoutExtension(): void
    {
        $this->expectException(CakeException::class);
        $engine = new JsonConfig($this->path);
        $engine->read('no_json_extension');
    }

    /**
     * Test an exception is thrown by reading files that don't exist.
     */
    public function testReadWithNonExistentFile(): void
    {
        $this->expectException(CakeException::class);
        $engine = new JsonConfig($this->path);
        $engine->read('fake_values');
    }

    /**
     * Test reading an empty file.
     */
    public function testReadEmptyFile(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('config file `empty.json`');
        $engine = new JsonConfig($this->path);
        $engine->read('empty');
    }

    /**
     * Test an exception is thrown by reading files that contain invalid JSON.
     */
    public function testReadWithInvalidJson(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Error parsing JSON string fetched from config file `invalid.json`');
        $engine = new JsonConfig($this->path);
        $engine->read('invalid');
    }

    /**
     * Test reading keys with ../ doesn't work.
     */
    public function testReadWithDots(): void
    {
        $this->expectException(CakeException::class);
        $engine = new JsonConfig($this->path);
        $engine->read('../empty');
    }

    /**
     * Test reading from plugins.
     */
    public function testReadPluginValue(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $engine = new JsonConfig($this->path);
        $result = $engine->read('TestPlugin.load');
        $this->assertArrayHasKey('plugin_load', $result);

        $this->clearPlugins();
    }

    /**
     * Test dumping data to JSON format.
     */
    public function testDump(): void
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
     */
    public function testDumpRead(): void
    {
        $engine = new JsonConfig(TMP);
        $engine->dump('test', $this->testData);

        $result = $engine->read('test');
        unlink(TMP . 'test.json');

        $this->assertEquals($this->testData, $result);
    }
}
