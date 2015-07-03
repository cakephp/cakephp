<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class FakeConnection
{
}

/**
 * ConnectionManager Test
 */
class ConnectionManagerTest extends TestCase
{

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
        ConnectionManager::drop('test_variant');
        ConnectionManager::dropAlias('other_name');
    }

    /**
     * Data provider for valid config data sets.
     *
     * @return array
     */
    public static function configProvider()
    {
        return [
            'Array of data using classname key.' => [[
                'className' => __NAMESPACE__ . '\FakeConnection',
                'instance' => 'Sqlite',
                'database' => ':memory:',
            ]],
            'Direct instance' => [new FakeConnection],
        ];
    }

    /**
     * Test the various valid config() calls.
     *
     * @dataProvider configProvider
     * @return void
     */
    public function testConfigVariants($settings)
    {
        $this->assertNotContains('test_variant', ConnectionManager::configured(), 'test_variant config should not exist.');
        ConnectionManager::config('test_variant', $settings);

        $ds = ConnectionManager::get('test_variant');
        $this->assertInstanceOf(__NAMESPACE__ . '\FakeConnection', $ds);
        $this->assertContains('test_variant', ConnectionManager::configured());
    }

    /**
     * Test invalid classes cause exceptions
     *
     * @expectedException \Cake\Datasource\Exception\MissingDatasourceException
     */
    public function testConfigInvalidOptions()
    {
        ConnectionManager::config('test_variant', [
            'className' => 'Herp\Derp'
        ]);
        ConnectionManager::get('test_variant');
    }

    /**
     * Test for errors on duplicate config.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot reconfigure existing key "test_variant"
     * @return void
     */
    public function testConfigDuplicateConfig()
    {
        $settings = [
            'className' => __NAMESPACE__ . '\FakeConnection',
            'database' => ':memory:',
        ];
        ConnectionManager::config('test_variant', $settings);
        ConnectionManager::config('test_variant', $settings);
    }

    /**
     * Test get() failing on missing config.
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @expectedExceptionMessage The datasource configuration "test_variant" was not found.
     * @return void
     */
    public function testGetFailOnMissingConfig()
    {
        ConnectionManager::get('test_variant');
    }

    /**
     * Test loading configured connections.
     *
     * @return void
     */
    public function testGet()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(empty($config), 'No test config, skipping');

        $ds = ConnectionManager::get('test');
        $this->assertSame($ds, ConnectionManager::get('test'));
        $this->assertInstanceOf('Cake\Database\Connection', $ds);
        $this->assertEquals('test', $ds->configName());
    }

    /**
     * Test loading connections without aliases
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @expectedExceptionMessage The datasource configuration "other_name" was not found.
     * @return void
     */
    public function testGetNoAlias()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(empty($config), 'No test config, skipping');

        ConnectionManager::alias('test', 'other_name');
        ConnectionManager::get('other_name', false);
    }

    /**
     * Test that configured() finds configured sources.
     *
     * @return void
     */
    public function testConfigured()
    {
        ConnectionManager::config('test_variant', [
            'className' => __NAMESPACE__ . '\FakeConnection',
            'database' => ':memory:'
        ]);
        $results = ConnectionManager::configured();
        $this->assertContains('test_variant', $results);
    }

    /**
     * testGetPluginDataSource method
     *
     * @return void
     */
    public function testGetPluginDataSource()
    {
        Plugin::load('TestPlugin');
        $name = 'test_variant';
        $config = ['className' => 'TestPlugin.TestSource', 'foo' => 'bar'];
        ConnectionManager::config($name, $config);
        $connection = ConnectionManager::get($name);

        $this->assertInstanceOf('TestPlugin\Datasource\TestSource', $connection);
        unset($config['className']);
        $this->assertSame($config + ['name' => 'test_variant'], $connection->config());
    }

    /**
     * Tests that a connection configuration can be deleted in runtime
     *
     * @return void
     */
    public function testDrop()
    {
        ConnectionManager::config('test_variant', [
            'className' => __NAMESPACE__ . '\FakeConnection',
            'database' => ':memory:'
        ]);
        $result = ConnectionManager::configured();
        $this->assertContains('test_variant', $result);

        $this->assertTrue(ConnectionManager::drop('test_variant'));
        $result = ConnectionManager::configured();
        $this->assertNotContains('test_variant', $result);

        $this->assertFalse(ConnectionManager::drop('probably_does_not_exist'), 'Should return false on failure.');
    }

    /**
     * Test aliasing connections.
     *
     * @return void
     */
    public function testAlias()
    {
        ConnectionManager::config('test_variant', [
            'className' => __NAMESPACE__ . '\FakeConnection',
            'database' => ':memory:'
        ]);
        ConnectionManager::alias('test_variant', 'other_name');
        $result = ConnectionManager::get('test_variant');
        $this->assertSame($result, ConnectionManager::get('other_name'));
    }

    /**
     * Test alias() raises an error when aliasing an undefined connection.
     *
     * @expectedException \Cake\Datasource\Exception\MissingDatasourceConfigException
     * @return void
     */
    public function testAliasError()
    {
        $this->assertNotContains('test_kaboom', ConnectionManager::configured());
        ConnectionManager::alias('test_kaboom', 'other_name');
    }
}
