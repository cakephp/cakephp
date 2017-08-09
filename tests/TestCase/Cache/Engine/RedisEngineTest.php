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
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\RedisEngine;
use Cake\TestSuite\TestCase;

/**
 * RedisEngineTest class
 */
class RedisEngineTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!class_exists('Redis'), 'Redis extension is not installed or configured properly.');
        $this->skipIf(version_compare(PHP_VERSION, '7.2.0dev', '>='), 'Redis is misbehaving in PHP7.2');

        // @codingStandardsIgnoreStart
        $socket = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 1);
        // @codingStandardsIgnoreEnd
        $this->skipIf(!$socket, 'Redis is not running.');
        fclose($socket);

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
        Cache::drop('redis');
        Cache::drop('redis_groups');
        Cache::drop('redis_helper');
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
            'className' => 'Redis',
            'prefix' => 'cake_',
            'duration' => 3600
        ];
        Cache::drop('redis');
        Cache::config('redis', array_merge($defaults, $config));
    }

    /**
     * testConfig method
     *
     * @return void
     */
    public function testConfig()
    {
        $config = Cache::engine('redis')->config();
        $expecting = [
            'prefix' => 'cake_',
            'duration' => 3600,
            'probability' => 100,
            'groups' => [],
            'server' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 0,
            'persistent' => true,
            'password' => false,
            'database' => 0,
            'unix_socket' => false,
            'host' => null,
        ];
        $this->assertEquals($expecting, $config);
    }

    /**
     * testConfigDsn method
     *
     * @return void
     */
    public function testConfigDsn()
    {
        Cache::config('redis_dsn', [
            'url' => 'redis://localhost:6379?database=1&prefix=redis_'
        ]);

        $config = Cache::engine('redis_dsn')->config();
        $expecting = [
            'prefix' => 'redis_',
            'duration' => 3600,
            'probability' => 100,
            'groups' => [],
            'server' => 'localhost',
            'port' => 6379,
            'timeout' => 0,
            'persistent' => true,
            'password' => false,
            'database' => '1',
            'unix_socket' => false,
            'host' => 'localhost',
            'scheme' => 'redis',
        ];
        $this->assertEquals($expecting, $config);

        Cache::drop('redis_dsn');
    }

    /**
     * testConnect method
     *
     * @return void
     */
    public function testConnect()
    {
        $Redis = new RedisEngine();
        $this->assertTrue($Redis->init(Cache::engine('redis')->config()));
    }

    /**
     * testMultiDatabaseOperations method
     *
     * @return void
     */
    public function testMultiDatabaseOperations()
    {
        Cache::config('redisdb0', [
            'engine' => 'Redis',
            'prefix' => 'cake2_',
            'duration' => 3600,
            'persistent' => false,
        ]);

        Cache::config('redisdb1', [
            'engine' => 'Redis',
            'database' => 1,
            'prefix' => 'cake2_',
            'duration' => 3600,
            'persistent' => false,
        ]);

        $result = Cache::write('save_in_0', true, 'redisdb0');
        $exist = Cache::read('save_in_0', 'redisdb0');
        $this->assertTrue($result);
        $this->assertTrue($exist);

        $result = Cache::write('save_in_1', true, 'redisdb1');
        $this->assertTrue($result);
        $exist = Cache::read('save_in_0', 'redisdb1');
        $this->assertFalse($exist);
        $exist = Cache::read('save_in_1', 'redisdb1');
        $this->assertTrue($exist);

        Cache::delete('save_in_0', 'redisdb0');
        $exist = Cache::read('save_in_0', 'redisdb0');
        $this->assertFalse($exist);

        Cache::delete('save_in_1', 'redisdb1');
        $exist = Cache::read('save_in_1', 'redisdb1');
        $this->assertFalse($exist);

        Cache::drop('redisdb0');
        Cache::drop('redisdb1');
    }

    /**
     * test write numbers method
     *
     * @return void
     */
    public function testWriteNumbers()
    {
        $result = Cache::write('test-counter', 1, 'redis');
        $this->assertSame(1, Cache::read('test-counter', 'redis'));

        $result = Cache::write('test-counter', 0, 'redis');
        $this->assertSame(0, Cache::read('test-counter', 'redis'));

        $result = Cache::write('test-counter', -1, 'redis');
        $this->assertSame(-1, Cache::read('test-counter', 'redis'));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'redis');
        $expecting = '';
        $this->assertEquals($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'redis');
        $this->assertTrue($result);

        $result = Cache::read('test', 'redis');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        $data = [1, 2, 3];
        $this->assertTrue(Cache::write('array_data', $data, 'redis'));
        $this->assertEquals($data, Cache::read('array_data', 'redis'));

        Cache::delete('test', 'redis');
    }

    /**
     * testExpiry method
     *
     * @return void
     */
    public function testExpiry()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'redis');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'redis');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'redis');
        $this->assertFalse($result);

        $this->_configCache(['duration' => '+1 second']);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'redis');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'redis');
        $this->assertFalse($result);

        sleep(2);

        $result = Cache::read('other_test', 'redis');
        $this->assertFalse($result);

        $this->_configCache(['duration' => '+29 days']);
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('long_expiry_test', $data, 'redis');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('long_expiry_test', 'redis');
        $expecting = $data;
        $this->assertEquals($expecting, $result);
    }

    /**
     * testDeleteCache method
     *
     * @return void
     */
    public function testDeleteCache()
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'redis');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'redis');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        Cache::delete('test_decrement', 'redis');
        $result = Cache::write('test_decrement', 5, 'redis');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'redis');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'redis');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'redis');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'redis');
        $this->assertEquals(2, $result);
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        Cache::delete('test_increment', 'redis');
        $result = Cache::increment('test_increment', 1, 'redis');
        $this->assertEquals(1, $result);

        $result = Cache::read('test_increment', 'redis');
        $this->assertEquals(1, $result);

        $result = Cache::increment('test_increment', 2, 'redis');
        $this->assertEquals(3, $result);

        $result = Cache::read('test_increment', 'redis');
        $this->assertEquals(3, $result);
    }

    /**
     * Test that increment() and decrement() can live forever.
     *
     * @return void
     */
    public function testIncrementDecrementForvever()
    {
        $this->_configCache(['duration' => 0]);
        Cache::delete('test_increment', 'redis');
        Cache::delete('test_decrement', 'redis');

        $result = Cache::increment('test_increment', 1, 'redis');
        $this->assertEquals(1, $result);

        $result = Cache::decrement('test_decrement', 1, 'redis');
        $this->assertEquals(-1, $result);

        $this->assertEquals(1, Cache::read('test_increment', 'redis'));
        $this->assertEquals(-1, Cache::read('test_decrement', 'redis'));
    }

    /**
     * Test that increment and decrement set ttls.
     *
     * @return void
     */
    public function testIncrementDecrementExpiring()
    {
        $this->_configCache(['duration' => 1]);
        Cache::delete('test_increment', 'redis');
        Cache::delete('test_decrement', 'redis');

        $this->assertSame(1, Cache::increment('test_increment', 1, 'redis'));
        $this->assertSame(-1, Cache::decrement('test_decrement', 1, 'redis'));

        sleep(2);

        $this->assertFalse(Cache::read('test_increment', 'redis'));
        $this->assertFalse(Cache::read('test_decrement', 'redis'));
    }

    /**
     * test clearing redis.
     *
     * @return void
     */
    public function testClear()
    {
        Cache::config('redis2', [
            'engine' => 'Redis',
            'prefix' => 'cake2_',
            'duration' => 3600
        ]);

        Cache::write('some_value', 'cache1', 'redis');
        $result = Cache::clear(true, 'redis');
        $this->assertTrue($result);
        $this->assertEquals('cache1', Cache::read('some_value', 'redis'));

        Cache::write('some_value', 'cache2', 'redis2');
        $result = Cache::clear(false, 'redis');
        $this->assertTrue($result);
        $this->assertFalse(Cache::read('some_value', 'redis'));
        $this->assertEquals('cache2', Cache::read('some_value', 'redis2'));

        Cache::clear(false, 'redis2');
    }

    /**
     * test that a 0 duration can successfully write.
     *
     * @return void
     */
    public function testZeroDuration()
    {
        $this->_configCache(['duration' => 0]);
        $result = Cache::write('test_key', 'written!', 'redis');

        $this->assertTrue($result);
        $result = Cache::read('test_key', 'redis');
        $this->assertEquals('written!', $result);
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     *
     * @return void
     */
    public function testGroupReadWrite()
    {
        Cache::config('redis_groups', [
            'engine' => 'Redis',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);
        Cache::config('redis_helper', [
            'engine' => 'Redis',
            'duration' => 3600,
            'prefix' => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'redis_groups'));

        Cache::increment('group_a', 1, 'redis_helper');
        $this->assertFalse(Cache::read('test_groups', 'redis_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'redis_groups'));
        $this->assertEquals('value2', Cache::read('test_groups', 'redis_groups'));

        Cache::increment('group_b', 1, 'redis_helper');
        $this->assertFalse(Cache::read('test_groups', 'redis_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'redis_groups'));
        $this->assertEquals('value3', Cache::read('test_groups', 'redis_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::config('redis_groups', [
            'engine' => 'Redis',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b']
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'redis_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'redis_groups'));

        $this->assertFalse(Cache::read('test_groups', 'redis_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::config('redis_groups', [
            'engine' => 'Redis',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b']
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'redis_groups'));
        $this->assertFalse(Cache::read('test_groups', 'redis_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'redis_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'redis_groups'));
        $this->assertFalse(Cache::read('test_groups', 'redis_groups'));
    }

    /**
     * Test add
     *
     * @return void
     */
    public function testAdd()
    {
        Cache::delete('test_add_key', 'redis');

        $result = Cache::add('test_add_key', 'test data', 'redis');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'redis');
        $this->assertEquals($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'redis');
        $this->assertFalse($result);
    }
}
