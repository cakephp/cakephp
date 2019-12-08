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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Cache;

use Cake\Cache\CacheEngine;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\SimpleCacheEngine;
use Cake\TestSuite\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * SimpleCacheEngine class
 *
 * @coversDefaultClass \Cake\Cache\SimpleCacheEngine
 */
class SimpleCacheEngineTest extends TestCase
{
    /**
     * The inner cache engine
     *
     * @var CacheEngine
     */
    protected $innerEngine;

    /**
     * The simple cache engine under test
     *
     * @var SimpleCacheEngine
     */
    protected $cache;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->innerEngine = new FileEngine();
        $this->innerEngine->init([
            'prefix' => '',
            'path' => TMP . 'tests',
            'duration' => 5,
            'groups' => ['blog', 'category'],
        ]);
        $this->cache = new SimpleCacheEngine($this->innerEngine);
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->innerEngine->clear(false);
    }

    /**
     * Test getting keys
     *
     * @return void
     * @covers ::get
     * @covers ::__construct
     * @covers ::ensureValidKey
     */
    public function testGetSuccess()
    {
        $this->innerEngine->write('key_one', 'Some Value');
        $this->assertSame('Some Value', $this->cache->get('key_one'));
        $this->assertSame('Some Value', $this->cache->get('key_one', 'default'));
    }

    /**
     * Test get on missing keys
     *
     * @return void
     * @covers ::get
     */
    public function testGetNoKey()
    {
        $this->assertSame('default', $this->cache->get('no', 'default'));
        $this->assertNull($this->cache->get('no'));
    }

    /**
     * Test get on invalid keys. The PSR spec outlines that an exception
     * must be raised.
     *
     * @return void
     * @covers ::get
     * @covers ::ensureValidKey
     */
    public function testGetInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $this->cache->get('');
    }

    /**
     * Test set() inheriting the default TTL
     *
     * @return void
     * @covers ::set
     * @covers ::__construct
     */
    public function testSetNoTtl()
    {
        $this->assertTrue($this->cache->set('key', 'a value'));
        $this->assertSame('a value', $this->cache->get('key'));
    }

    /**
     * Test the TTL parameter of set()
     *
     * @return void
     * @covers ::set
     */
    public function testSetWithTtl()
    {
        $this->assertTrue($this->cache->set('key', 'a value'));
        $ttl = 0;
        $this->assertTrue($this->cache->set('expired', 'a value', $ttl));

        sleep(1);
        $this->assertSame('a value', $this->cache->get('key'));
        $this->assertNull($this->cache->get('expired'));
        $this->assertSame(5, $this->innerEngine->getConfig('duration'));
    }

    /**
     * Test set() with an invalid key.
     *
     * @return void
     * @covers ::set
     * @covers ::ensureValidKey
     */
    public function testSetInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $this->cache->set('', 'some data');
    }

    /**
     * Test delete on known and unknown keys
     *
     * @return void
     * @covers ::delete
     */
    public function testDelete()
    {
        $this->cache->set('key', 'a value');
        $this->assertTrue($this->cache->delete('key'));
        $this->assertFalse($this->cache->delete('undefined'));
    }

    /**
     * Test delete on an invalid key
     *
     * @return void
     * @covers ::delete
     * @covers ::ensureValidKey
     */
    public function testDeleteInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $this->cache->delete('');
    }

    /**
     * Test clearing cache data
     *
     * @return void
     * @covers ::clear
     */
    public function testClear()
    {
        $this->cache->set('key', 'a value');
        $this->cache->set('key2', 'other value');

        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get('key'));
        $this->assertNull($this->cache->get('key2'));
    }

    /**
     * Test getMultiple
     *
     * @return void
     * @covers ::getMultiple
     */
    public function testGetMultiple()
    {
        $this->cache->set('key', 'a value');
        $this->cache->set('key2', 'other value');

        $results = $this->cache->getMultiple(['key', 'key2', 'no']);
        $expected = [
            'key' => 'a value',
            'key2' => 'other value',
            'no' => null,
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Test getting multiple keys with an invalid key
     *
     * @return void
     * @covers ::getMultiple
     * @covers ::ensureValidKeys
     * @covers ::ensureValidKey
     */
    public function testGetMultipleInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $withInvalidKey = [''];
        $this->cache->getMultiple($withInvalidKey);
    }

    /**
     * Test getting multiple keys with an invalid keys parameter
     *
     * @return void
     * @covers ::getMultiple
     * @covers ::ensureValidKeys
     */
    public function testGetMultipleInvalidKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key set must be either an array or a Traversable.');
        $notAnArray = 'neither an array nor a Traversable';
        $this->cache->getMultiple($notAnArray);
    }

    /**
     * Test getMultiple adding defaults in.
     *
     * @return void
     * @covers ::getMultiple
     */
    public function testGetMultipleDefault()
    {
        $this->cache->set('key', 'a value');
        $this->cache->set('key2', 'other value');

        $results = $this->cache->getMultiple(['key', 'key2', 'no'], 'default value');
        $expected = [
            'key' => 'a value',
            'key2' => 'other value',
            'no' => 'default value',
        ];
        $this->assertSame($expected, $results);
    }

    /**
     * Test setMultiple
     *
     * We should not assert for array equality, as the PSR-16 specs
     * do not make any guarantees on key order.
     *
     * @return void
     * @covers ::setMultiple
     */
    public function testSetMultiple()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value',
        ];
        $expected = [
            'key2' => 'other value',
            'key' => 'a value',
        ];

        $result = $this->cache->setMultiple($data);
        $this->assertTrue($result);

        $results = $this->cache->getMultiple(array_keys($data));
        $this->assertEquals($expected, $results);
    }

    /**
     * Test setMultiple with an invalid key
     *
     * @return void
     * @covers ::setMultiple
     * @covers ::ensureValidKeys
     * @covers ::ensureValidKey
     */
    public function testSetMultipleInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $data = [
            '' => 'a value wuth an invalid key',
        ];
        $this->cache->setMultiple($data);
    }

    /**
     * Test setMultiple with TTL parameter
     *
     * @return void
     * @covers ::setMultiple
     * @covers ::ensureValidKeys
     */
    public function testSetMultipleWithTtl()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value',
        ];
        $ttl = 0;
        $this->cache->setMultiple($data, $ttl);

        sleep(1);
        $results = $this->cache->getMultiple(array_keys($data));
        $this->assertNull($results['key']);
        $this->assertNull($results['key2']);
        $this->assertSame(5, $this->innerEngine->getConfig('duration'));
    }

    /**
     * Test deleting multiple keys
     *
     * @return void
     * @covers ::deleteMultiple
     */
    public function testDeleteMultiple()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value',
            'key3' => 'more data',
        ];
        $this->cache->setMultiple($data);
        $this->assertTrue($this->cache->deleteMultiple(['key', 'key3']));
        $this->assertNull($this->cache->get('key'));
        $this->assertNull($this->cache->get('key3'));
        $this->assertSame('other value', $this->cache->get('key2'));
    }

    /**
     * Test deleting multiple keys with an invalid key
     *
     * @return void
     * @covers ::deleteMultiple
     * @covers ::ensureValidKeys
     * @covers ::ensureValidKey
     */
    public function testDeleteMultipleInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $withInvalidKey = [''];
        $this->cache->deleteMultiple($withInvalidKey);
    }

    /**
     * Test deleting multiple keys with an invalid keys parameter
     *
     * @return void
     * @covers ::deleteMultiple
     * @covers ::ensureValidKeys
     */
    public function testDeleteMultipleInvalidKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key set must be either an array or a Traversable.');
        $notAnArray = 'neither an array nor a Traversable';
        $this->cache->deleteMultiple($notAnArray);
    }

    /**
     * Test partial success with deleteMultiple
     *
     * @return void
     * @covers ::deleteMultiple
     */
    public function testDeleteMultipleSomeMisses()
    {
        $data = [
            'key' => 'a value',
        ];
        $this->cache->setMultiple($data);
        $this->assertFalse($this->cache->deleteMultiple(['key', 'key3']));
    }

    /**
     * Test has
     *
     * @return void
     * @covers ::has
     */
    public function testHas()
    {
        $this->assertFalse($this->cache->has('key'));

        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->has('key'));
    }

    /**
     * Test has with invalid key
     *
     * @return void
     * @covers ::has
     * @covers ::ensureValidKey
     */
    public function testHasInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $this->cache->has('');
    }

    /**
     * Test pass through on clearGroup()
     *
     * @return void
     */
    public function testClearGroup()
    {
        $this->cache->set('one', 'val');
        $this->cache->set('two', 'val 2');

        $this->cache->clearGroup('blog');
        $this->assertFalse($this->cache->has('one'));
        $this->assertFalse($this->cache->has('two'));
    }

    /**
     * Test pass through on increment()
     *
     * @return void
     */
    public function testIncrement()
    {
        $mock = $this->createMock(CacheEngine::class);
        $mock->expects($this->once())
            ->method('increment')
            ->with('key', 2)
            ->will($this->returnValue(true));

        $cache = new SimpleCacheEngine($mock);
        $this->assertTrue($cache->increment('key', 2));
    }

    /**
     * Test pass through on decrement()
     *
     * @return void
     */
    public function testDecrement()
    {
        $mock = $this->createMock(CacheEngine::class);
        $mock->expects($this->once())
            ->method('decrement')
            ->with('key', 2)
            ->will($this->returnValue(true));

        $cache = new SimpleCacheEngine($mock);
        $this->assertTrue($cache->decrement('key', 2));
    }

    /**
     * Test pass through on add()
     *
     * @return void
     */
    public function testAdd()
    {
        $mock = $this->createMock(CacheEngine::class);
        $mock->expects($this->once())
            ->method('add')
            ->with('key', 2)
            ->will($this->returnValue(true));

        $cache = new SimpleCacheEngine($mock);
        $this->assertTrue($cache->add('key', 2));
    }
}
