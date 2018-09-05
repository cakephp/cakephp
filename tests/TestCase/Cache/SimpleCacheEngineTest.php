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

use Cake\Cache\Engine\FileEngine;
use Cake\Cache\SimpleCacheEngine;
use Cake\TestSuite\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * SimpleCacheEngine class
 */
class SimpleCacheEngineTest extends TestCase
{
    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->inner = new FileEngine();
        $this->inner->init([
            'prefix' => '',
            'path' => TMP . 'tests',
            'duration' => 5,
        ]);
        $this->cache = new SimpleCacheEngine($this->inner);
    }

    /**
     * tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->inner->clear(false);
    }

    /**
     * test getting keys
     *
     * @return void
     */
    public function testGetSuccess()
    {
        $this->inner->write('key_one', 'Some Value');
        $this->assertSame('Some Value', $this->cache->get('key_one'));
        $this->assertSame('Some Value', $this->cache->get('key_one', 'default'));
    }

    /**
     * test get on missing keys
     *
     * @return void
     */
    public function testGetNoKey()
    {
        $this->assertSame('default', $this->cache->get('no', 'default'));
        $this->assertNull($this->cache->get('no'));
    }

    /**
     * test get on invalid keys. The PSR spec outlines that an exception
     * must be raised.
     *
     * @return void
     */
    public function testGetInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get('');
    }

    /**
     * test set() inheriting the default TTL
     *
     * @return void
     */
    public function testSetNoTtl()
    {
        $this->assertTrue($this->cache->set('key', 'a value'));
        $this->assertSame('a value', $this->cache->get('key'));
    }

    /**
     * test the TTL parameter of set()
     *
     * @return void
     */
    public function testSetWithTtl()
    {
        $this->assertTrue($this->cache->set('key', 'a value'));
        $this->assertTrue($this->cache->set('expired', 'a value', 0));

        sleep(1);
        $this->assertSame('a value', $this->cache->get('key'));
        $this->assertNull($this->cache->get('expired'));
        $this->assertSame(5, $this->inner->getConfig('duration'));
    }

    /**
     * test set() with an invalid key.
     *
     * @return void
     */
    public function testSetInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->set('', 'some data');
    }

    /**
     * test delete on known and unknown keys
     *
     * @return void
     */
    public function testDelete()
    {
        $this->cache->set('key', 'a value');
        $this->assertTrue($this->cache->delete('key'));
        $this->assertFalse($this->cache->delete('undefined'));
    }

    /**
     * test delete on an invalid key
     *
     * @return void
     */
    public function testDeleteInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->delete('');
    }

    /**
     * test clearing cache data
     *
     * @return void
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
     * test getMultiple
     *
     * @return void
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
     * test getting multiple keys with an invalid key
     *
     * @return void
     */
    public function testGetMultipleInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $withInvalidKey = [''];
        $this->cache->getMultiple($withInvalidKey);
    }

    /**
     * test getting multiple keys with an invalid keys parameter
     *
     * @return void
     */
    public function testGetMultipleInvalidKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key set must be either an array or a Traversable.');
        $notAnArray = 'neither an array nor a Traversable';
        $this->cache->getMultiple($notAnArray);
    }

    /**
     * test getMultiple adding defaults in.
     *
     * @return void
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
     * test setMultiple
     *
     * @return void
     */
    public function testSetMultiple()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value',
        ];
        $this->cache->setMultiple($data);

        $results = $this->cache->getMultiple(array_keys($data));
        $this->assertSame($data, $results);
    }

    /**
     * test setMultiple with an invalid key
     *
     * @return void
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
     * test setMultiple with ttl parameter
     *
     * @return void
     */
    public function testSetMultipleWithTtl()
    {
        $data = [
            'key' => 'a value',
            'key2' => 'other value',
        ];
        $this->cache->setMultiple($data, 0);

        sleep(1);
        $results = $this->cache->getMultiple(array_keys($data));
        $this->assertNull($results['key']);
        $this->assertNull($results['key2']);
        $this->assertSame(5, $this->inner->getConfig('duration'));
    }

    /**
     * test deleting multiple keys
     *
     * @return void
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
     * test deleting multiple keys with an invalid key
     *
     * @return void
     */
    public function testDeleteMultipleInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key must be a non-empty string.');
        $withInvalidKey = [''];
        $this->cache->deleteMultiple($withInvalidKey);
    }

    /**
     * test deleting multiple keys with an invalid keys parameter
     *
     * @return void
     */
    public function testDeleteMultipleInvalidKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A cache key set must be either an array or a Traversable.');
        $notAnArray = 'neither an array nor a Traversable';
        $this->cache->deleteMultiple($notAnArray);
    }

    /**
     * test partial success with deleteMultiple
     *
     * @return void
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
     * test has
     *
     * @return void
     */
    public function testHas()
    {
        $this->assertFalse($this->cache->has('key'));

        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->has('key'));
    }

    /**
     * test has with invalid key
     *
     * @return void
     */
    public function testHasInvalidKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->has('');
    }
}
