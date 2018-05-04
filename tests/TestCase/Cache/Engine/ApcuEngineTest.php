<?php
/**
 * ApcuEngineTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

/**
 * ApcuEngineTest class
 */
class ApcuEngineTest extends TestCase
{
    /**
     * useRequestTime original value
     *
     * @var bool
     */
    static protected $useRequestTime = null;

    /**
     * Ensure use_request_time is turned off
     *
     * If use_request_time is on, all cache entries are inserted with the same
     * timestamp and ttl comparisons within the same request are effectively
     * meaningless
     */
    public static function setUpBeforeClass()
    {
        static::$useRequestTime = ini_get('apc.use_request_time');
        ini_set('apc.use_request_time', 0);
    }

    /**
     * Reset apc.user_request_time to original value
     *
     */
    public static function teardownAfterClass()
    {
        ini_set('apc.use_request_time', static::$useRequestTime);
    }

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
            $this->skipIf(!ini_get('apc.enable_cli'), 'APCu is not enabled for the CLI.');
        }

        Cache::enable();
        $this->_configCache();
        Cache::clearAll();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('apcu');
        Cache::drop('apcu_groups');
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
            'className' => 'Apcu',
            'prefix' => 'cake_',
            'warnOnWriteFailures' => true,
        ];
        Cache::drop('apcu');
        Cache::setConfig('apcu', array_merge($defaults, $config));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'apcu');
        $expecting = '';
        $this->assertEquals($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'apcu');
        $this->assertTrue($result);

        $result = Cache::read('test', 'apcu');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        Cache::delete('test', 'apcu');
    }

    /**
     * Writing cache entries with duration = 0 (forever) should work.
     *
     * @return void
     */
    public function testReadWriteDurationZero()
    {
        Cache::drop('apcu');
        Cache::setConfig('apcu', ['engine' => 'Apcu', 'duration' => 0, 'prefix' => 'cake_']);
        Cache::write('zero', 'Should save', 'apcu');
        sleep(1);

        $result = Cache::read('zero', 'apcu');
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

        $result = Cache::read('test', 'apcu');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'apcu');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'apcu');
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
        $result = Cache::write('delete_test', $data, 'apcu');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'apcu');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        $result = Cache::write('test_decrement', 5, 'apcu');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'apcu');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'apcu');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'apcu');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'apcu');
        $this->assertEquals(2, $result);
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        $result = Cache::write('test_increment', 5, 'apcu');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'apcu');
        $this->assertEquals(6, $result);

        $result = Cache::read('test_increment', 'apcu');
        $this->assertEquals(6, $result);

        $result = Cache::increment('test_increment', 2, 'apcu');
        $this->assertEquals(8, $result);

        $result = Cache::read('test_increment', 'apcu');
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
        Cache::write('some_value', 'value', 'apcu');

        $result = Cache::clear(false, 'apcu');
        $this->assertTrue($result);
        $this->assertFalse(Cache::read('some_value', 'apcu'));
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
        Cache::setConfig('apcu_groups', [
            'engine' => 'Apcu',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'apcu_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'apcu_groups'));

        apcu_inc('test_group_a');
        $this->assertFalse(Cache::read('test_groups', 'apcu_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'apcu_groups'));
        $this->assertEquals('value2', Cache::read('test_groups', 'apcu_groups'));

        apcu_inc('test_group_b');
        $this->assertFalse(Cache::read('test_groups', 'apcu_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'apcu_groups'));
        $this->assertEquals('value3', Cache::read('test_groups', 'apcu_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::setConfig('apcu_groups', [
            'engine' => 'Apcu',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'apcu_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'apcu_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'apcu_groups'));

        $this->assertFalse(Cache::read('test_groups', 'apcu_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::setConfig('apcu_groups', [
            'engine' => 'Apcu',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'warnOnWriteFailures' => true,
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'apcu_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'apcu_groups'));
        $this->assertFalse(Cache::read('test_groups', 'apcu_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'apcu_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'apcu_groups'));
        $this->assertFalse(Cache::read('test_groups', 'apcu_groups'));
    }

    /**
     * Test add
     *
     * @return void
     */
    public function testAdd()
    {
        Cache::delete('test_add_key', 'apcu');

        $result = Cache::add('test_add_key', 'test data', 'apcu');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'apcu');
        $this->assertEquals($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'apcu');
        $this->assertFalse($result);
    }
}
