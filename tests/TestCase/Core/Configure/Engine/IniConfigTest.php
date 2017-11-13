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

use Cake\Core\Configure\Engine\IniConfig;
use Cake\Core\Plugin;
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
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->path = CONFIG;
    }

    /**
     * test construct
     *
     * @return void
     */
    public function testConstruct()
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('acl');

        $this->assertTrue(isset($config['admin']));
        $this->assertTrue(isset($config['paul']['groups']));
        $this->assertEquals('ads', $config['admin']['deny']);
    }

    /**
     * Test reading files.
     *
     * @return void
     */
    public function testRead()
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('nested');
        $this->assertTrue($config['bools']['test_on']);

        $config = $engine->read('nested');
        $this->assertTrue($config['bools']['test_on']);
    }

    /**
     * No other sections should exist.
     *
     * @return void
     */
    public function testReadOnlyOneSection()
    {
        $engine = new IniConfig($this->path, 'admin');
        $config = $engine->read('acl');

        $this->assertTrue(isset($config['groups']));
        $this->assertEquals('administrators', $config['groups']);
    }

    /**
     * Test without section.
     *
     * @return void
     */
    public function testReadWithoutSection()
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('no_section');

        $expected = [
            'some_key' => 'some_value',
            'bool_key' => true
        ];
        $this->assertEquals($expected, $config);
    }

    /**
     * Test that names with .'s get exploded into arrays.
     *
     * @return void
     */
    public function testReadValuesWithDots()
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('nested');

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
    public function testBooleanReading()
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
     *
     * @return void
     */
    public function testReadWithExistentFileWithoutExtension()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new IniConfig($this->path);
        $engine->read('no_ini_extension');
    }

    /**
     * Test an exception is thrown by reading files that don't exist.
     *
     * @return void
     */
    public function testReadWithNonExistentFile()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new IniConfig($this->path);
        $engine->read('fake_values');
    }

    /**
     * Test reading an empty file.
     *
     * @return void
     */
    public function testReadEmptyFile()
    {
        $engine = new IniConfig($this->path);
        $config = $engine->read('empty');
        $this->assertEquals([], $config);
    }

    /**
     * Test reading keys with ../ doesn't work.
     *
     * @return void
     */
    public function testReadWithDots()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $engine = new IniConfig($this->path);
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
        $engine = new IniConfig($this->path);
        $result = $engine->read('TestPlugin.nested');

        $this->assertTrue(isset($result['database']['db']['username']));
        $this->assertEquals('bar', $result['database']['db']['username']);
        $this->assertFalse(isset($result['database.db.username']));
        $this->assertFalse(isset($result['database']['db.username']));

        $result = $engine->read('TestPlugin.nested');
        $this->assertEquals('foo', $result['database']['db']['password']);
        Plugin::unload();
    }

    /**
     * Test dump method.
     *
     * @return void
     */
    public function testDump()
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
     *
     * @return void
     */
    public function testDumpRead()
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
