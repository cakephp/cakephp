<?php
/**
 * ApcEngineTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

/**
 * ApcEngineTest class
 */
class ApcEngineTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!function_exists('apcu_store'), 'APCu is not installed or configured properly.');

        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
            $this->skipIf(!ini_get('apc.enable_cli'), 'APC is not enabled for the CLI.');
        }

        Cache::enable();
        $this->_configCache();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('apc');
        Cache::drop('apc_groups');
    }

    /**
     * Helper method for testing.
     *
     * @param array $config
     * @return void
     */
    protected function _configCache($config = [])
    {
        $defaults = [
            'className' => 'Apc',
            'prefix' => 'cake_',
        ];
        Cache::drop('apc');
        Cache::config('apc', array_merge($defaults, $config));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'apc');
        $expecting = '';
        $this->assertEquals($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'apc');
        $this->assertTrue($result);

        $result = Cache::read('test', 'apc');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        Cache::delete('test', 'apc');
    }

    /**
     * Writing cache entries with duration = 0 (forever) should work.
     *
     * @return void
     */
    public function testReadWriteDurationZero()
    {
        Cache::drop('apc');
        Cache::config('apc', ['engine' => 'Apc', 'duration' => 0, 'prefix' => 'cake_']);
        Cache::write('zero', 'Should save', 'apc');
        sleep(1);

        $result = Cache::read('zero', 'apc');
        $this->assertEquals('Should save', $result);
    }

    /**
     * testExpiry method
     *
     * @return void
     */
    public function testExpiry()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'apc');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'apc');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'apc');
        $this->assertFalse($result);
    }

    /**
     * testDeleteCache method
     *
     * @return void
     */
    public function testDeleteCache()
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'apc');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'apc');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        $result = Cache::write('test_decrement', 5, 'apc');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'apc');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'apc');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'apc');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'apc');
        $this->assertEquals(2, $result);
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        $result = Cache::write('test_increment', 5, 'apc');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'apc');
        $this->assertEquals(6, $result);

        $result = Cache::read('test_increment', 'apc');
        $this->assertEquals(6, $result);

        $result = Cache::increment('test_increment', 2, 'apc');
        $this->assertEquals(8, $result);

        $result = Cache::read('test_increment', 'apc');
        $this->assertEquals(8, $result);
    }

    /**
     * test the clearing of cache keys
     *
     * @return void
     */
    public function testClear()
    {
        apcu_store('not_cake', 'survive');
        Cache::write('some_value', 'value', 'apc');

        $result = Cache::clear(false, 'apc');
        $this->assertTrue($result);
        $this->assertFalse(Cache::read('some_value', 'apc'));
        $this->assertEquals('survive', apcu_fetch('not_cake'));
        apcu_delete('not_cake');
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     *
     * @return void
     */
    public function testGroupsReadWrite()
    {
        Cache::config('apc_groups', [
            'engine' => 'Apc',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'apc_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'apc_groups'));

        apcu_inc('test_group_a');
        $this->assertFalse(Cache::read('test_groups', 'apc_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'apc_groups'));
        $this->assertEquals('value2', Cache::read('test_groups', 'apc_groups'));

        apcu_inc('test_group_b');
        $this->assertFalse(Cache::read('test_groups', 'apc_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'apc_groups'));
        $this->assertEquals('value3', Cache::read('test_groups', 'apc_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::config('apc_groups', [
            'engine' => 'Apc',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'apc_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'apc_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'apc_groups'));

        $this->assertFalse(Cache::read('test_groups', 'apc_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::config('apc_groups', [
            'engine' => 'Apc',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'apc_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'apc_groups'));
        $this->assertFalse(Cache::read('test_groups', 'apc_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'apc_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'apc_groups'));
        $this->assertFalse(Cache::read('test_groups', 'apc_groups'));
    }

    /**
     * Test add
     *
     * @return void
     */
    public function testAdd()
    {
        Cache::delete('test_add_key', 'apc');

        $result = Cache::add('test_add_key', 'test data', 'apc');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'apc');
        $this->assertEquals($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'apc');
        $this->assertFalse($result);
    }
}
