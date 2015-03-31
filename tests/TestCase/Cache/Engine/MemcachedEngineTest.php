<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\MemcachedEngine;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use \Memcached;

/**
 * Class TestMemcachedEngine
 *
 */
class TestMemcachedEngine extends MemcachedEngine
{

    /**
     * public accessor to _parseServerString
     *
     * @param string $server
     * @return array
     */
    public function parseServerString($server)
    {
        return $this->_parseServerString($server);
    }

    public function setMemcached($memcached)
    {
        $this->_Memcached = $memcached;
    }

    public function getMemcached()
    {
        return $this->_Memcached;
    }
}

/**
 * MemcachedEngineTest class
 *
 */
class MemcachedEngineTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!class_exists('Memcached'), 'Memcached is not installed or configured properly.');

        // @codingStandardsIgnoreStart
        $socket = @fsockopen('127.0.0.1', 11211, $errno, $errstr, 1);
        // @codingStandardsIgnoreEnd
        $this->skipIf(!$socket, 'Memcached is not running.');
        fclose($socket);

        $this->_configCache();
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
            'className' => 'Memcached',
            'prefix' => 'cake_',
            'duration' => 3600
        ];
        Cache::drop('memcached');
        Cache::config('memcached', array_merge($defaults, $config));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('memcached');
        Cache::drop('memcached2');
        Cache::drop('memcached_groups');
        Cache::drop('memcached_helper');
        Cache::drop('compressed_memcached');
        Cache::drop('long_memcached');
        Cache::drop('short_memcached');
    }

    /**
     * testConfig method
     *
     * @return void
     */
    public function testConfig()
    {
        $config = Cache::engine('memcached')->config();
        unset($config['path']);
        $expecting = [
            'prefix' => 'cake_',
            'duration' => 3600,
            'probability' => 100,
            'servers' => ['127.0.0.1'],
            'persistent' => false,
            'compress' => false,
            'username' => null,
            'password' => null,
            'groups' => [],
            'serialize' => 'php',
            'options' => [],
            'host' => null,
            'port' => null,
        ];
        $this->assertEquals($expecting, $config);
    }

    /**
     * testCompressionSetting method
     *
     * @return void
     */
    public function testCompressionSetting()
    {
        $Memcached = new TestMemcachedEngine();
        $Memcached->init([
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'compress' => false
        ]);

        $this->assertFalse($Memcached->getMemcached()->getOption(\Memcached::OPT_COMPRESSION));

        $MemcachedCompressed = new TestMemcachedEngine();
        $MemcachedCompressed->init([
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'compress' => true
        ]);

        $this->assertTrue($MemcachedCompressed->getMemcached()->getOption(\Memcached::OPT_COMPRESSION));
    }

    /**
     * test setting options
     *
     * @return void
     */
    public function testOptionsSetting()
    {
        $memcached = new TestMemcachedEngine();
        $memcached->init([
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'options' => [
                Memcached::OPT_BINARY_PROTOCOL => true
            ]
        ]);
        $this->assertEquals(1, $memcached->getMemcached()->getOption(Memcached::OPT_BINARY_PROTOCOL));
    }

    /**
     * test accepts only valid serializer engine
     *
     * @return  void
     */
    public function testInvalidSerializerSetting()
    {
        $Memcached = new TestMemcachedEngine();
        $config = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'invalid_serializer'
        ];

        $this->setExpectedException(
            'InvalidArgumentException',
            'invalid_serializer is not a valid serializer engine for Memcached'
        );
        $Memcached->init($config);
    }

    /**
     * testPhpSerializerSetting method
     *
     * @return void
     */
    public function testPhpSerializerSetting()
    {
        $Memcached = new TestMemcachedEngine();
        $config = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'php'
        ];

        $Memcached->init($config);
        $this->assertEquals(Memcached::SERIALIZER_PHP, $Memcached->getMemcached()->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testJsonSerializerSetting method
     *
     * @return void
     */
    public function testJsonSerializerSetting()
    {
        $this->skipIf(
            !Memcached::HAVE_JSON,
            'Memcached extension is not compiled with json support'
        );

        $Memcached = new TestMemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'json'
        ];

        $Memcached->init($config);
        $this->assertEquals(Memcached::SERIALIZER_JSON, $Memcached->getMemcached()->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testIgbinarySerializerSetting method
     *
     * @return void
     */
    public function testIgbinarySerializerSetting()
    {
        $this->skipIf(
            !Memcached::HAVE_IGBINARY,
            'Memcached extension is not compiled with igbinary support'
        );

        $Memcached = new TestMemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'igbinary'
        ];

        $Memcached->init($config);
        $this->assertEquals(Memcached::SERIALIZER_IGBINARY, $Memcached->getMemcached()->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testMsgpackSerializerSetting method
     *
     * @return void
     */
    public function testMsgpackSerializerSetting()
    {
        $this->skipIf(
            !defined('Memcached::HAVE_MSGPACK') || !Memcached::HAVE_MSGPACK,
            'Memcached extension is not compiled with msgpack support'
        );

        $Memcached = new TestMemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'msgpack'
        ];

        $Memcached->init($config);
        $this->assertEquals(Memcached::SERIALIZER_MSGPACK, $Memcached->getMemcached()->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testJsonSerializerThrowException method
     *
     * @return void
     */
    public function testJsonSerializerThrowException()
    {
        $this->skipIf(
            Memcached::HAVE_JSON,
            'Memcached extension is compiled with json support'
        );

        $Memcached = new TestMemcachedEngine();
        $config = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'json'
        ];

        $this->setExpectedException(
            'InvalidArgumentException',
            'Memcached extension is not compiled with json support'
        );
        $Memcached->init($config);
    }

    /**
     * testMsgpackSerializerThrowException method
     *
     * @return void
     */
    public function testMsgpackSerializerThrowException()
    {
        $this->skipIf(
            defined('Memcached::HAVE_MSGPACK') && Memcached::HAVE_MSGPACK,
            'Memcached extension is compiled with msgpack support'
        );

        $Memcached = new TestMemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'msgpack'
        ];

        $this->setExpectedException(
            'InvalidArgumentException',
            'msgpack is not a valid serializer engine for Memcached'
        );
        $Memcached->init($config);
    }

    /**
     * testIgbinarySerializerThrowException method
     *
     * @return void
     */
    public function testIgbinarySerializerThrowException()
    {
        $this->skipIf(
            Memcached::HAVE_IGBINARY,
            'Memcached extension is compiled with igbinary support'
        );

        $Memcached = new TestMemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'serialize' => 'igbinary'
        ];

        $this->setExpectedException(
            'InvalidArgumentException',
            'Memcached extension is not compiled with igbinary support'
        );
        $Memcached->init($config);
    }

    /**
     * test using authentication without memcached installed with SASL support
     * throw an exception
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Memcached extension is not build with SASL support
     * @return void
     */
    public function testSaslAuthException()
    {
        $MemcachedEngine = new TestMemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => false,
            'username' => 'test',
            'password' => 'password'
        ];

        $MemcachedEngine->init($config);
    }

    /**
     * testConfig method
     *
     * @return void
     */
    public function testMultipleServers()
    {
        $servers = ['127.0.0.1:11211', '127.0.0.1:11222'];
        $available = true;
        $Memcached = new \Memcached();

        foreach ($servers as $server) {
            list($host, $port) = explode(':', $server);
            //@codingStandardsIgnoreStart
            if (!$Memcached->addServer($host, $port)) {
                $available = false;
            }
            //@codingStandardsIgnoreEnd
        }

        $this->skipIf(!$available, 'Need memcached servers at ' . implode(', ', $servers) . ' to run this test.');

        $Memcached = new MemcachedEngine();
        $Memcached->init(['engine' => 'Memcached', 'servers' => $servers]);

        $config = $Memcached->config();
        $this->assertEquals($config['servers'], $servers);
        Cache::drop('dual_server');
    }

    /**
     * test connecting to an ipv6 server.
     *
     * @return void
     */
    public function testConnectIpv6()
    {
        $Memcached = new MemcachedEngine();
        $result = $Memcached->init([
            'prefix' => 'cake_',
            'duration' => 200,
            'engine' => 'Memcached',
            'servers' => [
                '[::1]:11211'
            ]
        ]);
        $this->assertTrue($result);
    }

    /**
     * test domain starts with u
     *
     * @return void
     */
    public function testParseServerStringWithU()
    {
        $Memcached = new TestMemcachedEngine();
        $result = $Memcached->parseServerString('udomain.net:13211');
        $this->assertEquals(['udomain.net', '13211'], $result);
    }

    /**
     * test non latin domains.
     *
     * @return void
     */
    public function testParseServerStringNonLatin()
    {
        $Memcached = new TestMemcachedEngine();
        $result = $Memcached->parseServerString('schülervz.net:13211');
        $this->assertEquals(['schülervz.net', '13211'], $result);

        $result = $Memcached->parseServerString('sülül:1111');
        $this->assertEquals(['sülül', '1111'], $result);
    }

    /**
     * test unix sockets.
     *
     * @return void
     */
    public function testParseServerStringUnix()
    {
        $Memcached = new TestMemcachedEngine();
        $result = $Memcached->parseServerString('unix:///path/to/memcachedd.sock');
        $this->assertEquals(['unix:///path/to/memcachedd.sock', 0], $result);
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'memcached');
        $expecting = '';
        $this->assertEquals($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'memcached');
        $this->assertTrue($result);

        $result = Cache::read('test', 'memcached');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        Cache::delete('test', 'memcached');
    }

    /**
     * testReadMany method
     *
     * @return void
     */
    public function testReadMany()
    {
        $this->_configCache(['duration' => 2]);
        $data = [
            'App.falseTest' => false,
            'App.trueTest' => true,
            'App.nullTest' => null,
            'App.zeroTest' => 0,
            'App.zeroTest2' => '0'
        ];
        foreach ($data as $key => $value) {
            Cache::write($key, $value, 'memcached');
        }

        $read = Cache::readMany(array_merge(array_keys($data), ['App.doesNotExist']), 'memcached');

        $this->assertSame($read['App.falseTest'], false);
        $this->assertSame($read['App.trueTest'], true);
        $this->assertSame($read['App.nullTest'], null);
        $this->assertSame($read['App.zeroTest'], 0);
        $this->assertSame($read['App.zeroTest2'], '0');
        $this->assertSame($read['App.doesNotExist'], false);
    }

    /**
     * testWriteMany method
     *
     * @return void
     */
    public function testWriteMany()
    {
        $this->_configCache(['duration' => 2]);
        $data = [
            'App.falseTest' => false,
            'App.trueTest' => true,
            'App.nullTest' => null,
            'App.zeroTest' => 0,
            'App.zeroTest2' => '0'
        ];
        Cache::writeMany($data, 'memcached');

        $this->assertSame(Cache::read('App.falseTest', 'memcached'), false);
        $this->assertSame(Cache::read('App.trueTest', 'memcached'), true);
        $this->assertSame(Cache::read('App.nullTest', 'memcached'), null);
        $this->assertSame(Cache::read('App.zeroTest', 'memcached'), 0);
        $this->assertSame(Cache::read('App.zeroTest2', 'memcached'), '0');
    }

    /**
     * testExpiry method
     *
     * @return void
     */
    public function testExpiry()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'memcached');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'memcached');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'memcached');
        $this->assertFalse($result);

        $this->_configCache(['duration' => '+1 second']);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'memcached');
        $this->assertTrue($result);

        sleep(3);
        $result = Cache::read('other_test', 'memcached');
        $this->assertFalse($result);

        $result = Cache::read('other_test', 'memcached');
        $this->assertFalse($result);

        $this->_configCache(['duration' => '+29 days']);
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('long_expiry_test', $data, 'memcached');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('long_expiry_test', 'memcached');
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
        $result = Cache::write('delete_test', $data, 'memcached');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'memcached');
        $this->assertTrue($result);
    }

    /**
     * testDeleteMany method
     *
     * @return void
     */
    public function testDeleteMany()
    {
        $this->skipIf(defined('HHVM_VERSION'), 'HHVM does not implement deleteMulti');
        $this->_configCache();
        $data = [
            'App.falseTest' => false,
            'App.trueTest' => true,
            'App.nullTest' => null,
            'App.zeroTest' => 0,
            'App.zeroTest2' => '0',
        ];
        foreach ($data as $key => $value) {
            Cache::write($key, $value, 'memcached');
        }
        Cache::write('App.keepTest', 'keepMe', 'memcached');

        Cache::deleteMany(array_merge(array_keys($data), ['App.doesNotExist']), 'memcached');

        $this->assertSame(Cache::read('App.falseTest', 'memcached'), false);
        $this->assertSame(Cache::read('App.trueTest', 'memcached'), false);
        $this->assertSame(Cache::read('App.nullTest', 'memcached'), false);
        $this->assertSame(Cache::read('App.zeroTest', 'memcached'), false);
        $this->assertSame(Cache::read('App.zeroTest2', 'memcached'), false);
        $this->assertSame(Cache::read('App.keepTest', 'memcached'), 'keepMe');
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        $result = Cache::write('test_decrement', 5, 'memcached');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'memcached');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'memcached');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'memcached');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'memcached');
        $this->assertEquals(2, $result);

        Cache::delete('test_decrement', 'memcached');
    }

    /**
     * test decrementing compressed keys
     *
     * @return void
     */
    public function testDecrementCompressedKeys()
    {
        Cache::config('compressed_memcached', [
            'engine' => 'Memcached',
            'duration' => '+2 seconds',
            'servers' => ['127.0.0.1:11211'],
            'compress' => true
        ]);

        $result = Cache::write('test_decrement', 5, 'compressed_memcached');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'compressed_memcached');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'compressed_memcached');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'compressed_memcached');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'compressed_memcached');
        $this->assertEquals(2, $result);

        Cache::delete('test_decrement', 'compressed_memcached');
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        $result = Cache::write('test_increment', 5, 'memcached');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'memcached');
        $this->assertEquals(6, $result);

        $result = Cache::read('test_increment', 'memcached');
        $this->assertEquals(6, $result);

        $result = Cache::increment('test_increment', 2, 'memcached');
        $this->assertEquals(8, $result);

        $result = Cache::read('test_increment', 'memcached');
        $this->assertEquals(8, $result);

        Cache::delete('test_increment', 'memcached');
    }

    /**
     * test incrementing compressed keys
     *
     * @return void
     */
    public function testIncrementCompressedKeys()
    {
        Cache::config('compressed_memcached', [
            'engine' => 'Memcached',
            'duration' => '+2 seconds',
            'servers' => ['127.0.0.1:11211'],
            'compress' => true
        ]);

        $result = Cache::write('test_increment', 5, 'compressed_memcached');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'compressed_memcached');
        $this->assertEquals(6, $result);

        $result = Cache::read('test_increment', 'compressed_memcached');
        $this->assertEquals(6, $result);

        $result = Cache::increment('test_increment', 2, 'compressed_memcached');
        $this->assertEquals(8, $result);

        $result = Cache::read('test_increment', 'compressed_memcached');
        $this->assertEquals(8, $result);

        Cache::delete('test_increment', 'compressed_memcached');
    }

    /**
     * test that configurations don't conflict, when a file engine is declared after a memcached one.
     *
     * @return void
     */
    public function testConfigurationConflict()
    {
        Cache::config('long_memcached', [
            'engine' => 'Memcached',
            'duration' => '+2 seconds',
            'servers' => ['127.0.0.1:11211'],
        ]);
        Cache::config('short_memcached', [
            'engine' => 'Memcached',
            'duration' => '+1 seconds',
            'servers' => ['127.0.0.1:11211'],
        ]);

        $this->assertTrue(Cache::write('duration_test', 'yay', 'long_memcached'));
        $this->assertTrue(Cache::write('short_duration_test', 'boo', 'short_memcached'));

        $this->assertEquals('yay', Cache::read('duration_test', 'long_memcached'), 'Value was not read %s');
        $this->assertEquals('boo', Cache::read('short_duration_test', 'short_memcached'), 'Value was not read %s');

        sleep(1);
        $this->assertEquals('yay', Cache::read('duration_test', 'long_memcached'), 'Value was not read %s');

        sleep(2);
        $this->assertFalse(Cache::read('short_duration_test', 'short_memcached'), 'Cache was not invalidated %s');
        $this->assertFalse(Cache::read('duration_test', 'long_memcached'), 'Value did not expire %s');

        Cache::delete('duration_test', 'long_memcached');
        Cache::delete('short_duration_test', 'short_memcached');
    }

    /**
     * test clearing memcached.
     *
     * @return void
     */
    public function testClear()
    {
        Cache::config('memcached2', [
            'engine' => 'Memcached',
            'prefix' => 'cake2_',
            'duration' => 3600
        ]);

        Cache::write('some_value', 'cache1', 'memcached');
        $result = Cache::clear(true, 'memcached');
        $this->assertTrue($result);
        $this->assertEquals('cache1', Cache::read('some_value', 'memcached'));

        Cache::write('some_value', 'cache2', 'memcached2');
        $result = Cache::clear(false, 'memcached');
        $this->assertTrue($result);
        $this->assertFalse(Cache::read('some_value', 'memcached'));
        $this->assertEquals('cache2', Cache::read('some_value', 'memcached2'));

        Cache::clear(false, 'memcached2');
    }

    /**
     * test that a 0 duration can successfully write.
     *
     * @return void
     */
    public function testZeroDuration()
    {
        $this->_configCache(['duration' => 0]);
        $result = Cache::write('test_key', 'written!', 'memcached');

        $this->assertTrue($result);
        $result = Cache::read('test_key', 'memcached');
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
        Cache::config('memcached_groups', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);
        Cache::config('memcached_helper', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'prefix' => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'memcached_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'memcached_groups'));

        Cache::increment('group_a', 1, 'memcached_helper');
        $this->assertFalse(Cache::read('test_groups', 'memcached_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'memcached_groups'));
        $this->assertEquals('value2', Cache::read('test_groups', 'memcached_groups'));

        Cache::increment('group_b', 1, 'memcached_helper');
        $this->assertFalse(Cache::read('test_groups', 'memcached_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'memcached_groups'));
        $this->assertEquals('value3', Cache::read('test_groups', 'memcached_groups'));
    }

    /**
     * Tests that deleteing from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::config('memcached_groups', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b']
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'memcached_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'memcached_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'memcached_groups'));

        $this->assertFalse(Cache::read('test_groups', 'memcached_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::config('memcached_groups', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b']
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'memcached_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'memcached_groups'));
        $this->assertFalse(Cache::read('test_groups', 'memcached_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'memcached_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'memcached_groups'));
        $this->assertFalse(Cache::read('test_groups', 'memcached_groups'));
    }
}
