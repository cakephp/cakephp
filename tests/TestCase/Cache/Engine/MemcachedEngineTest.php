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
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\MemcachedEngine;
use Cake\TestSuite\TestCase;
use DateInterval;
use InvalidArgumentException;
use Memcached;

/**
 * MemcachedEngineTest class
 */
class MemcachedEngineTest extends TestCase
{
    /**
     * @var string
     */
    protected $port = '11211';

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIf(!class_exists('Memcached'), 'Memcached is not installed or configured properly.');

        $this->port = env('MEMCACHED_PORT', $this->port);

        // phpcs:disable
        $socket = @fsockopen('127.0.0.1', (int)$this->port, $errno, $errstr, 1);
        // phpcs:enable
        $this->skipIf(!$socket, 'Memcached is not running.');
        fclose($socket);

        $this->_configCache();
    }

    /**
     * Helper method for testing.
     *
     * @param array $config
     */
    protected function _configCache($config = []): void
    {
        $defaults = [
            'className' => 'Memcached',
            'prefix' => 'cake_',
            'duration' => 3600,
            'servers' => ['127.0.0.1:' . $this->port],
        ];
        Cache::drop('memcached');
        Cache::setConfig('memcached', array_merge($defaults, $config));
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
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
     */
    public function testConfig(): void
    {
        $config = Cache::pool('memcached')->getConfig();
        unset($config['path']);
        $expecting = [
            'prefix' => 'cake_',
            'duration' => 3600,
            'servers' => ['127.0.0.1:' . $this->port],
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
     */
    public function testCompressionSetting(): void
    {
        $Memcached = new MemcachedEngine();
        $Memcached->init([
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'compress' => false,
        ]);

        $this->assertFalse($Memcached->getOption(Memcached::OPT_COMPRESSION));

        $MemcachedCompressed = new MemcachedEngine();
        $MemcachedCompressed->init([
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'compress' => true,
        ]);

        $this->assertTrue($MemcachedCompressed->getOption(Memcached::OPT_COMPRESSION));
    }

    /**
     * test setting options
     */
    public function testOptionsSetting(): void
    {
        $memcached = new MemcachedEngine();
        $memcached->init([
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'options' => [
                Memcached::OPT_BINARY_PROTOCOL => true,
            ],
        ]);
        $this->assertSame(1, $memcached->getOption(Memcached::OPT_BINARY_PROTOCOL));
    }

    /**
     * test accepts only valid serializer engine
     */
    public function testInvalidSerializerSetting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_serializer is not a valid serializer engine for Memcached');
        $Memcached = new MemcachedEngine();
        $config = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'invalid_serializer',
        ];
        $Memcached->init($config);
    }

    /**
     * testPhpSerializerSetting method
     */
    public function testPhpSerializerSetting(): void
    {
        $Memcached = new MemcachedEngine();
        $config = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'php',
        ];

        $Memcached->init($config);
        $this->assertSame(Memcached::SERIALIZER_PHP, $Memcached->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testJsonSerializerSetting method
     */
    public function testJsonSerializerSetting(): void
    {
        $this->skipIf(
            !Memcached::HAVE_JSON,
            'Memcached extension is not compiled with json support'
        );

        $Memcached = new MemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'json',
        ];

        $Memcached->init($config);
        $this->assertSame(Memcached::SERIALIZER_JSON, $Memcached->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testIgbinarySerializerSetting method
     */
    public function testIgbinarySerializerSetting(): void
    {
        $this->skipIf(
            !Memcached::HAVE_IGBINARY,
            'Memcached extension is not compiled with igbinary support'
        );

        $Memcached = new MemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'igbinary',
        ];

        $Memcached->init($config);
        $this->assertSame(Memcached::SERIALIZER_IGBINARY, $Memcached->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testMsgpackSerializerSetting method
     */
    public function testMsgpackSerializerSetting(): void
    {
        $this->skipIf(
            !defined('Memcached::HAVE_MSGPACK') || !Memcached::HAVE_MSGPACK,
            'Memcached extension is not compiled with msgpack support'
        );

        $Memcached = new MemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'msgpack',
        ];

        $Memcached->init($config);
        $this->assertSame(Memcached::SERIALIZER_MSGPACK, $Memcached->getOption(Memcached::OPT_SERIALIZER));
    }

    /**
     * testJsonSerializerThrowException method
     */
    public function testJsonSerializerThrowException(): void
    {
        $this->skipIf(
            (bool)Memcached::HAVE_JSON,
            'Memcached extension is compiled with json support'
        );

        $Memcached = new MemcachedEngine();
        $config = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'json',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memcached extension is not compiled with json support');
        $Memcached->init($config);
    }

    /**
     * testMsgpackSerializerThrowException method
     */
    public function testMsgpackSerializerThrowException(): void
    {
        $this->skipIf(
            !defined('Memcached::HAVE_MSGPACK'),
            'Memcached::HAVE_MSGPACK constant is not available in Memcached below 3.0.0'
        );
        $this->skipIf(
            (bool)Memcached::HAVE_MSGPACK,
            'Memcached extension is compiled with msgpack support'
        );

        $Memcached = new MemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'msgpack',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memcached extension is not compiled with msgpack support');
        $Memcached->init($config);
    }

    /**
     * testIgbinarySerializerThrowException method
     */
    public function testIgbinarySerializerThrowException(): void
    {
        $this->skipIf(
            (bool)Memcached::HAVE_IGBINARY,
            'Memcached extension is compiled with igbinary support'
        );

        $Memcached = new MemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'serialize' => 'igbinary',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memcached extension is not compiled with igbinary support');
        $Memcached->init($config);
    }

    /**
     * test using authentication without memcached installed with SASL support
     * throw an exception
     */
    public function testSaslAuthException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memcached extension is not build with SASL support');
        $this->skipIf(
            method_exists(Memcached::class, 'setSaslAuthData'),
            'Cannot test exception when sasl has been compiled in.'
        );
        $MemcachedEngine = new MemcachedEngine();
        $config = [
            'engine' => 'Memcached',
            'servers' => ['127.0.0.1:' . $this->port],
            'persistent' => false,
            'username' => 'test',
            'password' => 'password',
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memcached extension is not built with SASL support');
        $MemcachedEngine->init($config);
    }

    /**
     * testConfigDifferentPorts method
     */
    public function testConfigDifferentPorts(): void
    {
        $Memcached1 = new MemcachedEngine();
        $config1 = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:11211'],
            'persistent' => '123',
        ];
        $Memcached1->init($config1);

        $Memcached2 = new MemcachedEngine();
        $config2 = [
            'className' => 'Memcached',
            'servers' => ['127.0.0.1:11212'],
            'persistent' => '123',
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache configuration. Multiple persistent cache');
        $Memcached2->init($config2);
    }

    /**
     * testConfig method
     */
    public function testMultipleServers(): void
    {
        $servers = ['127.0.0.1:' . $this->port, '127.0.0.1:11222'];
        $available = true;
        $Memcached = new Memcached();

        foreach ($servers as $server) {
            [$host, $port] = explode(':', $server);
            // phpcs:disable
            if (!$Memcached->addServer($host, (int)$port)) {
                $available = false;
            }
            // phpcs:enable
        }

        $this->skipIf(!$available, 'Need memcached servers at ' . implode(', ', $servers) . ' to run this test.');

        $Memcached = new MemcachedEngine();
        $Memcached->init(['engine' => 'Memcached', 'servers' => $servers]);

        $config = $Memcached->getConfig();
        $this->assertEquals($config['servers'], $servers);
        Cache::drop('dual_server');
    }

    /**
     * test connecting to an ipv6 server.
     */
    public function testConnectIpv6(): void
    {
        $Memcached = new MemcachedEngine();
        $result = $Memcached->init([
            'prefix' => 'cake_',
            'duration' => 200,
            'engine' => 'Memcached',
            'servers' => [
                '[::1]:' . $this->port,
            ],
        ]);
        $this->assertTrue($result);
    }

    /**
     * test domain starts with u
     */
    public function testParseServerStringWithU(): void
    {
        $Memcached = new MemcachedEngine();
        $result = $Memcached->parseServerString('udomain.net:13211');
        $this->assertEquals(['udomain.net', '13211'], $result);
    }

    /**
     * test non latin domains.
     */
    public function testParseServerStringNonLatin(): void
    {
        $Memcached = new MemcachedEngine();
        $result = $Memcached->parseServerString('schülervz.net:13211');
        $this->assertEquals(['schülervz.net', '13211'], $result);

        $result = $Memcached->parseServerString('sülül:1111');
        $this->assertEquals(['sülül', '1111'], $result);
    }

    /**
     * test unix sockets.
     */
    public function testParseServerStringUnix(): void
    {
        $Memcached = new MemcachedEngine();
        $result = $Memcached->parseServerString('unix:///path/to/memcachedd.sock');
        $this->assertEquals(['/path/to/memcachedd.sock', 0], $result);
    }

    /**
     * testReadAndWriteCache method
     */
    public function testReadAndWriteCache(): void
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'memcached');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'memcached');
        $this->assertTrue($result);

        $result = Cache::read('test', 'memcached');
        $expecting = $data;
        $this->assertSame($expecting, $result);

        Cache::delete('test', 'memcached');
    }

    /**
     * Test get with default value
     */
    public function testGetDefaultValue(): void
    {
        $memcache = Cache::pool('memcached');
        $this->assertFalse($memcache->get('nope', false));
        $this->assertNull($memcache->get('nope', null));
        $this->assertTrue($memcache->get('nope', true));
        $this->assertSame(0, $memcache->get('nope', 0));

        $memcache->set('yep', 0);
        $this->assertSame(0, $memcache->get('yep', false));
    }

    /**
     * testReadMany method
     */
    public function testReadMany(): void
    {
        $this->_configCache(['duration' => 2]);
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

        $read = Cache::readMany(array_merge(array_keys($data), ['App.doesNotExist']), 'memcached');

        $this->assertFalse($read['App.falseTest']);
        $this->assertTrue($read['App.trueTest']);
        $this->assertNull($read['App.nullTest']);
        $this->assertSame($read['App.zeroTest'], 0);
        $this->assertSame($read['App.zeroTest2'], '0');
        $this->assertNull($read['App.doesNotExist']);
    }

    /**
     * testWriteMany method
     */
    public function testWriteMany(): void
    {
        $this->skipIf(PHP_VERSION_ID >= 80100, 'php-memcached writeMany() argument parsing is broken with PHP 8.1+');
        $this->_configCache(['duration' => 2]);
        $data = [
            'App.falseTest' => false,
            'App.trueTest' => true,
            'App.nullTest' => null,
            'App.zeroTest' => 0,
            'App.zeroTest2' => '0',
        ];
        Cache::writeMany($data, 'memcached');

        $this->assertFalse(Cache::read('App.falseTest', 'memcached'));
        $this->assertTrue(Cache::read('App.trueTest', 'memcached'));
        $this->assertNull(Cache::read('App.nullTest', 'memcached'));
        $this->assertSame(Cache::read('App.zeroTest', 'memcached'), 0);
        $this->assertSame(Cache::read('App.zeroTest2', 'memcached'), '0');
    }

    /**
     * testExpiry method
     */
    public function testExpiry(): void
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'memcached');
        $this->assertNull($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'memcached');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'memcached');
        $this->assertNull($result);

        $this->_configCache(['duration' => '+1 second']);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'memcached');
        $this->assertTrue($result);

        sleep(3);
        $result = Cache::read('other_test', 'memcached');
        $this->assertNull($result);

        $result = Cache::read('other_test', 'memcached');
        $this->assertNull($result);

        $this->_configCache(['duration' => '+29 days']);
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('long_expiry_test', $data, 'memcached');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('long_expiry_test', 'memcached');
        $expecting = $data;
        $this->assertSame($expecting, $result);
    }

    /**
     * test set ttl parameter
     */
    public function testSetWithTtl(): void
    {
        $this->_configCache(['duration' => 99]);
        $engine = Cache::pool('memcached');
        $this->assertNull($engine->get('test'));

        $data = 'this is a test of the emergency broadcasting system';
        $this->assertTrue($engine->set('default_ttl', $data));
        $this->assertTrue($engine->set('int_ttl', $data, 1));
        $this->assertTrue($engine->set('interval_ttl', $data, new DateInterval('PT1S')));

        sleep(2);
        $this->assertNull($engine->get('int_ttl'));
        $this->assertNull($engine->get('interval_ttl'));
        $this->assertSame($data, $engine->get('default_ttl'));
    }

    /**
     * testDeleteCache method
     */
    public function testDeleteCache(): void
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'memcached');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'memcached');
        $this->assertTrue($result);
    }

    /**
     * testDeleteMany method
     */
    public function testDeleteMany(): void
    {
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

        $this->assertNull(Cache::read('App.falseTest', 'memcached'));
        $this->assertNull(Cache::read('App.trueTest', 'memcached'));
        $this->assertNull(Cache::read('App.nullTest', 'memcached'));
        $this->assertNull(Cache::read('App.zeroTest', 'memcached'));
        $this->assertNull(Cache::read('App.zeroTest2', 'memcached'));
        $this->assertSame('keepMe', Cache::read('App.keepTest', 'memcached'));
    }

    /**
     * testDecrement method
     */
    public function testDecrement(): void
    {
        $result = Cache::write('test_decrement', 5, 'memcached');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'memcached');
        $this->assertSame(4, $result);

        $result = Cache::read('test_decrement', 'memcached');
        $this->assertSame(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'memcached');
        $this->assertSame(2, $result);

        $result = Cache::read('test_decrement', 'memcached');
        $this->assertSame(2, $result);

        Cache::delete('test_decrement', 'memcached');
    }

    /**
     * test decrementing compressed keys
     */
    public function testDecrementCompressedKeys(): void
    {
        Cache::setConfig('compressed_memcached', [
            'engine' => 'Memcached',
            'duration' => '+2 seconds',
            'servers' => ['127.0.0.1:' . $this->port],
            'compress' => true,
        ]);

        $result = Cache::write('test_decrement', 5, 'compressed_memcached');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'compressed_memcached');
        $this->assertSame(4, $result);

        $result = Cache::read('test_decrement', 'compressed_memcached');
        $this->assertSame(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'compressed_memcached');
        $this->assertSame(2, $result);

        $result = Cache::read('test_decrement', 'compressed_memcached');
        $this->assertSame(2, $result);

        Cache::delete('test_decrement', 'compressed_memcached');
    }

    /**
     * testIncrement method
     */
    public function testIncrement(): void
    {
        $result = Cache::write('test_increment', 5, 'memcached');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'memcached');
        $this->assertSame(6, $result);

        $result = Cache::read('test_increment', 'memcached');
        $this->assertSame(6, $result);

        $result = Cache::increment('test_increment', 2, 'memcached');
        $this->assertSame(8, $result);

        $result = Cache::read('test_increment', 'memcached');
        $this->assertSame(8, $result);

        Cache::delete('test_increment', 'memcached');
    }

    /**
     * Test that increment and decrement set ttls.
     */
    public function testIncrementDecrementExpiring(): void
    {
        $this->_configCache(['duration' => 1]);
        Cache::write('test_increment', 1, 'memcached');
        Cache::write('test_decrement', 1, 'memcached');

        $this->assertSame(2, Cache::increment('test_increment', 1, 'memcached'));
        $this->assertSame(0, Cache::decrement('test_decrement', 1, 'memcached'));

        sleep(2);

        $this->assertNull(Cache::read('test_increment', 'memcached'));
        $this->assertNull(Cache::read('test_decrement', 'memcached'));
    }

    /**
     * test incrementing compressed keys
     */
    public function testIncrementCompressedKeys(): void
    {
        Cache::setConfig('compressed_memcached', [
            'engine' => 'Memcached',
            'duration' => '+2 seconds',
            'servers' => ['127.0.0.1:' . $this->port],
            'compress' => true,
        ]);

        $result = Cache::write('test_increment', 5, 'compressed_memcached');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'compressed_memcached');
        $this->assertSame(6, $result);

        $result = Cache::read('test_increment', 'compressed_memcached');
        $this->assertSame(6, $result);

        $result = Cache::increment('test_increment', 2, 'compressed_memcached');
        $this->assertSame(8, $result);

        $result = Cache::read('test_increment', 'compressed_memcached');
        $this->assertSame(8, $result);

        Cache::delete('test_increment', 'compressed_memcached');
    }

    /**
     * test that configurations don't conflict, when a file engine is declared after a memcached one.
     */
    public function testConfigurationConflict(): void
    {
        Cache::setConfig('long_memcached', [
            'engine' => 'Memcached',
            'duration' => '+3 seconds',
            'servers' => ['127.0.0.1:' . $this->port],
        ]);
        Cache::setConfig('short_memcached', [
            'engine' => 'Memcached',
            'duration' => '+2 seconds',
            'servers' => ['127.0.0.1:' . $this->port],
        ]);

        $this->assertTrue(Cache::write('duration_test', 'yay', 'long_memcached'));
        $this->assertTrue(Cache::write('short_duration_test', 'boo', 'short_memcached'));

        $this->assertSame('yay', Cache::read('duration_test', 'long_memcached'), 'Value was not read %s');
        $this->assertSame('boo', Cache::read('short_duration_test', 'short_memcached'), 'Value was not read %s');

        usleep(500000);
        $this->assertSame('yay', Cache::read('duration_test', 'long_memcached'), 'Value was not read %s');

        usleep(3000000);
        $this->assertNull(Cache::read('short_duration_test', 'short_memcached'), 'Cache was not invalidated %s');
        $this->assertNull(Cache::read('duration_test', 'long_memcached'), 'Value did not expire %s');

        Cache::delete('duration_test', 'long_memcached');
        Cache::delete('short_duration_test', 'short_memcached');
    }

    /**
     * test clearing memcached.
     */
    public function testClear(): void
    {
        Cache::setConfig('memcached2', [
            'engine' => 'Memcached',
            'prefix' => 'cake2_',
            'duration' => 3600,
            'servers' => ['127.0.0.1:' . $this->port],
        ]);

        Cache::write('some_value', 'cache1', 'memcached');
        Cache::write('some_value', 'cache2', 'memcached2');
        sleep(1);
        $this->assertTrue(Cache::clear('memcached'));

        $this->assertNull(Cache::read('some_value', 'memcached'));
        $this->assertSame('cache2', Cache::read('some_value', 'memcached2'));

        Cache::clear('memcached2');
    }

    /**
     * test that a 0 duration can successfully write.
     */
    public function testZeroDuration(): void
    {
        $this->_configCache(['duration' => 0]);
        $result = Cache::write('test_key', 'written!', 'memcached');

        $this->assertTrue($result);
        $result = Cache::read('test_key', 'memcached');
        $this->assertSame('written!', $result);
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     */
    public function testGroupReadWrite(): void
    {
        Cache::setConfig('memcached_groups', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_',
            'servers' => ['127.0.0.1:' . $this->port],
        ]);
        Cache::setConfig('memcached_helper', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'prefix' => 'test_',
            'servers' => ['127.0.0.1:' . $this->port],
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'memcached_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'memcached_groups'));

        Cache::increment('group_a', 1, 'memcached_helper');
        $this->assertNull(Cache::read('test_groups', 'memcached_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'memcached_groups'));
        $this->assertSame('value2', Cache::read('test_groups', 'memcached_groups'));

        Cache::increment('group_b', 1, 'memcached_helper');
        $this->assertNull(Cache::read('test_groups', 'memcached_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'memcached_groups'));
        $this->assertSame('value3', Cache::read('test_groups', 'memcached_groups'));
    }

    /**
     * Tests that deleting from a groups-enabled config is possible
     */
    public function testGroupDelete(): void
    {
        Cache::setConfig('memcached_groups', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b'],
            'servers' => ['127.0.0.1:' . $this->port],
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'memcached_groups'));
        $this->assertSame('value', Cache::read('test_groups', 'memcached_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'memcached_groups'));

        $this->assertNull(Cache::read('test_groups', 'memcached_groups'));
    }

    /**
     * Test clearing a cache group
     */
    public function testGroupClear(): void
    {
        Cache::setConfig('memcached_groups', [
            'engine' => 'Memcached',
            'duration' => 3600,
            'groups' => ['group_a', 'group_b'],
            'servers' => ['127.0.0.1:' . $this->port],
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'memcached_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'memcached_groups'));
        $this->assertNull(Cache::read('test_groups', 'memcached_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'memcached_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'memcached_groups'));
        $this->assertNull(Cache::read('test_groups', 'memcached_groups'));
    }

    /**
     * Test add
     */
    public function testAdd(): void
    {
        Cache::delete('test_add_key', 'memcached');

        $result = Cache::add('test_add_key', 'test data', 'memcached');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'memcached');
        $this->assertSame($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'memcached');
        $this->assertFalse($result);
    }
}
