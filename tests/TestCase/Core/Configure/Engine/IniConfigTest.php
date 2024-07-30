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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core\Configure\Engine;

use Cake\Core\Configure\Engine\IniConfig;
use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;

/**
 * IniConfigTest
 */
class IniConfigTest extends TestCase
{
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
     * @var string
     */
    protected $path;

    /**
     * setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->path = CONFIG;
    }

    /**
     * test construct
     */
    public function testConstruct(): void
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('acl');

        $this->assertArrayHasKey('admin', $config);
        $this->assertArrayHasKey('groups', $config['paul']);
        $this->assertSame('ads', $config['admin']['deny']);
    }

    /**
     * Test reading files.
     */
    public function testRead(): void
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('nested');
        $this->assertTrue($config['bools']['test_on']);

        $config = $engine->read('nested');
        $this->assertTrue($config['bools']['test_on']);
    }

    /**
     * No other sections should exist.
     */
    public function testReadOnlyOneSection(): void
    {
        $engine = new IniConfig($this->path, 'admin');
        $config = $engine->read('acl');

        $this->assertArrayHasKey('groups', $config);
        $this->assertSame('administrators', $config['groups']);
    }

    /**
     * Test without section.
     */
    public function testReadWithoutSection(): void
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('no_section');

        $expected = [
            'some_key' => 'some_value',
            'bool_key' => true,
        ];
        $this->assertEquals($expected, $config);
    }

    /**
     * Test that names with .'s get exploded into arrays.
     */
    public function testReadValuesWithDots(): void
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('nested');

        $this->assertArrayHasKey('username', $config['database']['db']);
        $this->assertSame('mark', $config['database']['db']['username']);
        $this->assertSame('3', $config['nesting']['one']['two']['three']);
        $this->assertArrayNotHasKey('database.db.username', $config);
        $this->assertArrayNotHasKey('db.username', $config['database']);
    }

    /**
     * Test boolean reading.
     */
    public function testBooleanReading(): void
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('nested');

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
     */
    public function testReadWithExistentFileWithoutExtension(): void
    {
        $this->expectException(CakeException::class);
        $engine = new IniConfig($this->path);
        $engine->read('no_ini_extension');
    }

    /**
     * Test an exception is thrown by reading files that don't exist.
     */
    public function testReadWithNonExistentFile(): void
    {
        $this->expectException(CakeException::class);
        $engine = new IniConfig($this->path);
        $engine->read('fake_values');
    }

    /**
     * Test reading an empty file.
     */
    public function testReadEmptyFile(): void
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('empty');
        $this->assertSame([], $config);
    }

    /**
     * Test reading keys with ../ doesn't work.
     */
    public function testReadWithDots(): void
    {
        $this->expectException(CakeException::class);
        $engine = new IniConfig($this->path);
        $engine->read('../empty');
    }

    /**
     * Test reading from plugins.
     */
    public function testReadPluginValue(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $engine = new IniConfig($this->path);
        $result = $engine->read('TestPlugin.nested');

        $this->assertArrayHasKey('username', $result['database']['db']);
        $this->assertSame('bar', $result['database']['db']['username']);
        $this->assertArrayNotHasKey('database.db.username', $result);
        $this->assertArrayNotHasKey('db.username', $result['database']);

        $result = $engine->read('TestPlugin.nested');
        $this->assertSame('foo', $result['database']['db']['password']);
        $this->clearPlugins();
    }

    /**
     * Test dump method.
     */
    public function testDump(): void
    {
        $engine = new IniConfig(TMP);
        $result = $engine->dump('test', $this->testData);
        $this->assertGreaterThan(0, $result);

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
        $engine = new IniConfig(TMP);
        $engine->dump('test', $this->testData);

        $result = $engine->read('test');
        unlink(TMP . 'test.ini');

        $expected = $this->testData;
        $expected['One']['is_null'] = false;

        $this->assertEquals($expected, $result);
    }
}
