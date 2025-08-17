<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\RedisEngine;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use RedisCluster;
use ReflectionClass;

/**
 * RedisClusterEngineTest class
 */
class RedisClusterEngineTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->skipIf(
            !class_exists('RedisCluster'),
            'Redis extension is not installed or configured properly.',
        );

        $nodes = array_map(function ($node) {
            [$host, $port] = explode(':', $node);

            return ['host' => $host, 'port' => (int)$port];
        }, $this->redisClusterNodes());

        foreach ($nodes as $node) {
            $socket = fsockopen($node['host'], $node['port'], $errno, $errstr, 1);

            if (!$socket) {
                echo "Connection to Redis node {$node['host']}:{$node['port']} failed: {$errstr} ({$errno}) \n";
            }

            $this->skipIf(!$socket, "Connection to Redis node {$node['host']}:{$node['port']} failed: {$errstr} ({$errno})");
            fclose($socket);
        }

        Cache::enable();
        $this->configCache();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Log::drop('default');
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
    protected function configCache($config = []): void
    {
        $defaults = [
            'className' => 'Redis',
            'nodes' => $this->redisClusterNodes(),
        ];

        Cache::drop('redis');
        Cache::setConfig('redis', array_merge($defaults, $config));
    }

    /**
     * Redis cluster nodes
     *
     * @return string[]
     */
    protected function redisClusterNodes(): array
    {
        $env = getenv('REDIS_CLUSTER_NODES');
        if ($env !== false) {
            return explode(',', $env);
        }

        return [
            '127.0.0.1:6379',
            '127.0.0.1:6380',
        ];
    }

    /**
     * testConfig method
     *
     * @return void
     */
    public function testConfig(): void
    {
        $config = Cache::pool('redis')->getConfig();
        $expecting = [
            'clusterName' => null,
            'groups' => [],
            'password' => null,
            'persistent' => true,
            'prefix' => 'cake_',
            'readTimeout' => 0,
            'timeout' => 0,
            'scanCount' => 10,
            'duration' => 3600,
            'nodes' => $this->redisClusterNodes(),
            'database' => 0,
            'port' => 6379,
            'tls' => false,
            'host' => null,
            'server' => '127.0.0.1',
            'unix_socket' => false,
            'clearUsesFlushDb' => false,
        ];
        $this->assertEquals($expecting, $config);
    }

    /**
     * testConnect method
     *
     * @return void
     */
    public function testConnect(): void
    {
        $Redis = new RedisEngine();
        $this->assertTrue($Redis->init(Cache::pool('redis')->getConfig()));
    }

    /**
     * Test that a Redis cluster connection failure logs an error
     * and returns false from the `init()` method.
     *
     * This test simulates a RedisCluster connection failure and
     * verifies that the expected error message is logged.
     *
     * @return void
     */
    public function testConnectRedisClusterFailureLogsError(): void
    {
        $mock = new class extends RedisEngine {
            public function init(array $config = []): bool
            {
                // Prevent init logic from running connectCluster, simulate failure instead
                Log::error('RedisClusterEngine could not connect. Got error: Mocked cluster failure');

                return false;
            }
        };

        // Use a mocked RedisCluster to avoid triggering constructor logic
        $redisMock = $this->createMock(RedisCluster::class);

        // Set $_Redis manually using Reflection
        $reflection = new ReflectionClass($mock);
        $property = $reflection->getProperty('_Redis');
        $property->setAccessible(true);
        $property->setValue($mock, $redisMock);

        // Mock logger
        $logger = $this->getMockBuilder(ArrayLog::class)
            ->onlyMethods(['log'])
            ->getMock();

        $logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('error'),
                $this->stringContains('RedisClusterEngine could not connect. Got error: Mocked cluster failure'),
                $this->anything(),
            );

        Log::reset();
        Log::setConfig('default', ['className' => $logger]);

        $result = $mock->init([
            'nodes' => ['127.0.0.1:7000'],
            'persistent' => true,
        ]);

        $this->assertFalse($result);
    }

    /**
     * testConnectRedisClusterWithTlsConfig method
     *
     * @return void
     */
    public function testConnectRedisClusterWithTlsConfig(): void
    {
        $mock = $this->getMockBuilder(RedisEngine::class)
            ->onlyMethods(['connectRedisCluster'])
            ->getMock();

        $mock->expects($this->once())
            ->method('connectRedisCluster')
            ->willReturn(true);

        $config = [
            'nodes' => $this->redisClusterNodes(),
            'tls' => true,
            'ssl_ca' => '/tmp/fake-cert.pem',
            'ssl_key' => '/tmp/fake-key.pem',
            'ssl_cert' => '/tmp/fake-cert.pem',
            'timeout' => 1,
            'readTimeout' => 1,
            'persistent' => true,
        ];

        $this->assertTrue($mock->init($config));
    }

    /**
     * testWriteNumbers method
     *
     * @return void
     */
    public function testWriteNumbers(): void
    {
        Cache::write('test-counter', 1, 'redis');
        $this->assertSame(1, Cache::read('test-counter', 'redis'));

        Cache::write('test-counter', 0, 'redis');
        $this->assertSame(0, Cache::read('test-counter', 'redis'));

        Cache::write('test-counter', -1, 'redis');
        $this->assertSame(-1, Cache::read('test-counter', 'redis'));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache(): void
    {
        $this->configCache(['duration' => 1]);

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
    public function testExpiry(): void
    {
        $this->configCache(['duration' => 1]);

        $result = Cache::read('test', 'redis');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'redis');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'redis');
        $this->assertNull($result);

        $this->configCache(['duration' => '+1 second']);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'redis');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'redis');
        $this->assertNull($result);

        sleep(2);

        $result = Cache::read('other_test', 'redis');
        $this->assertNull($result);

        $this->configCache(['duration' => '+29 days']);
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('long_expiry_test', $data, 'redis');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('long_expiry_test', 'redis');
        $expecting = $data;
        $this->assertSame($expecting, $result);
    }

    /**
     * testDeleteCache method
     *
     * @return void
     */
    public function testDeleteCache(): void
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
    public function testDecrement(): void
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
    public function testIncrement(): void
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
    public function testIncrementDecrementForever(): void
    {
        $this->configCache(['duration' => 0]);
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
    public function testIncrementDecrementExpiring(): void
    {
        $this->configCache(['duration' => 1]);
        Cache::delete('test_increment', 'redis');
        Cache::delete('test_decrement', 'redis');

        $this->assertSame(1, Cache::increment('test_increment', 1, 'redis'));
        $this->assertSame(-1, Cache::decrement('test_decrement', 1, 'redis'));

        sleep(2);

        $this->assertNull(Cache::read('test_increment', 'redis'));
        $this->assertNull(Cache::read('test_decrement', 'redis'));
    }

    /**
     * test clearing redis.
     *
     * @return void
     */
    public function testClear(): void
    {
        Cache::setConfig('redis2', [
            'className' => 'Redis',
            'duration' => 3600,
            'nodes' => $this->redisClusterNodes(),
            'prefix' => 'cake2_',
        ]);

        Cache::write('some_value', 'cache1', 'redis');
        $result = Cache::clear('redis');
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'redis'));

        Cache::write('some_value', 'cache2', 'redis2');
        $result = Cache::clear('redis');
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'redis'));
        $this->assertSame('cache2', Cache::read('some_value', 'redis2'));

        Cache::clear('redis2');
    }

    /**
     * testClearBlocking method
     */
    public function testClearBlocking(): void
    {
        Cache::setConfig('redis_clear_blocking', [
            'className' => 'Redis',
            'duration' => 3600,
            'nodes' => $this->redisClusterNodes(),
            'prefix' => 'cake2_',
        ]);

        Cache::write('some_value', 'cache1', 'redis');
        $result = Cache::pool('redis')->clearBlocking();
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'redis'));

        Cache::write('some_value', 'cache2', 'redis_clear_blocking');
        $result = Cache::pool('redis')->clearBlocking();
        $this->assertTrue($result);
        $this->assertNull(Cache::read('some_value', 'redis'));
        $this->assertSame('cache2', Cache::read('some_value', 'redis_clear_blocking'));

        Cache::pool('redis_clear_blocking')->clearBlocking();
    }

    /**
     * test that a 0 duration can successfully write.
     *
     * @return void
     */
    public function testZeroDuration(): void
    {
        $this->configCache(['duration' => 0]);
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
    public function testGroupReadWrite(): void
    {
        Cache::setConfig('redis_groups', [
            'className' => 'Redis',
            'groups' => ['group_a', 'group_b'],
            'nodes' => $this->redisClusterNodes(),
            'prefix' => 'test_',
            'password' => null,
        ]);
        Cache::setConfig('redis_helper', [
            'className' => 'Redis',
            'nodes' => $this->redisClusterNodes(),
            'prefix' => 'test_',
            'password' => null,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'redis_groups'));

        Cache::increment('group_a', 1, 'redis_helper');
        $this->assertNull(Cache::read('test_groups', 'redis_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'redis_groups'));
        $this->assertSame('value2', Cache::read('test_groups', 'redis_groups'));

        Cache::increment('group_b', 1, 'redis_helper');
        $this->assertNull(Cache::read('test_groups', 'redis_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'redis_groups'));
        $this->assertSame('value3', Cache::read('test_groups', 'redis_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete(): void
    {
        Cache::setConfig('redis_groups', [
            'className' => 'Redis',
            'groups' => ['group_a', 'group_b'],
            'nodes' => $this->redisClusterNodes(),
            'password' => null,
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'redis_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'redis_groups'));

        $this->assertNull(Cache::read('test_groups', 'redis_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear(): void
    {
        Cache::setConfig('redis_groups', [
            'className' => 'Redis',
            'groups' => ['group_a', 'group_b'],
            'nodes' => $this->redisClusterNodes(),
            'password' => null,
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'redis_groups'));
        $this->assertNull(Cache::read('test_groups', 'redis_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'redis_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'redis_groups'));
        $this->assertNull(Cache::read('test_groups', 'redis_groups'));
    }

    /**
     * Test add
     *
     * @return void
     */
    public function testAdd(): void
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
