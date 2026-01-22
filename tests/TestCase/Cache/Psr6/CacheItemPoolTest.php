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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Psr6;

use Cake\Cache\Cache;
use Cake\Cache\Psr6\CacheItemPool;
use Cake\Cache\Psr6\InvalidArgumentException;
use Cake\TestSuite\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * CacheItemPoolTest class
 */
class CacheItemPoolTest extends TestCase
{
    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::setConfig('psr6_test', [
            'engine' => 'Array',
        ]);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('psr6_test');
    }

    /**
     * Test that pool implements PSR-6 interface.
     */
    public function testImplementsInterface(): void
    {
        $pool = new CacheItemPool('psr6_test');
        $this->assertInstanceOf(CacheItemPoolInterface::class, $pool);
    }

    /**
     * Test getItem returns CacheItem.
     */
    public function testGetItem(): void
    {
        $pool = new CacheItemPool('psr6_test');
        $item = $pool->getItem('test_key');

        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertSame('test_key', $item->getKey());
        $this->assertFalse($item->isHit());
    }

    /**
     * Test getItem returns hit after save.
     */
    public function testGetItemAfterSave(): void
    {
        $pool = new CacheItemPool('psr6_test');
        $item = $pool->getItem('test_key');
        $item->set('test_value');
        $pool->save($item);

        $retrieved = $pool->getItem('test_key');
        $this->assertTrue($retrieved->isHit());
        $this->assertSame('test_value', $retrieved->get());
    }

    /**
     * Test getItems returns multiple items.
     */
    public function testGetItems(): void
    {
        $pool = new CacheItemPool('psr6_test');
        $items = $pool->getItems(['key1', 'key2', 'key3']);

        $this->assertCount(3, $items);
        foreach ($items as $item) {
            $this->assertInstanceOf(CacheItemInterface::class, $item);
            $this->assertFalse($item->isHit());
        }
    }

    /**
     * Test hasItem.
     */
    public function testHasItem(): void
    {
        $pool = new CacheItemPool('psr6_test');
        $this->assertFalse($pool->hasItem('test_key'));

        $item = $pool->getItem('test_key');
        $item->set('value');
        $pool->save($item);

        $this->assertTrue($pool->hasItem('test_key'));
    }

    /**
     * Test deleteItem.
     */
    public function testDeleteItem(): void
    {
        $pool = new CacheItemPool('psr6_test');

        $item = $pool->getItem('test_key');
        $item->set('value');
        $pool->save($item);
        $this->assertTrue($pool->hasItem('test_key'));

        $pool->deleteItem('test_key');
        $this->assertFalse($pool->hasItem('test_key'));
    }

    /**
     * Test deleteItems.
     */
    public function testDeleteItems(): void
    {
        $pool = new CacheItemPool('psr6_test');

        foreach (['key1', 'key2', 'key3'] as $key) {
            $item = $pool->getItem($key);
            $item->set('value');
            $pool->save($item);
        }

        $pool->deleteItems(['key1', 'key2']);

        $this->assertFalse($pool->hasItem('key1'));
        $this->assertFalse($pool->hasItem('key2'));
        $this->assertTrue($pool->hasItem('key3'));
    }

    /**
     * Test clear.
     */
    public function testClear(): void
    {
        $pool = new CacheItemPool('psr6_test');

        $item = $pool->getItem('test_key');
        $item->set('value');
        $pool->save($item);

        $pool->clear();
        $this->assertFalse($pool->hasItem('test_key'));
    }

    /**
     * Test saveDeferred and commit.
     */
    public function testSaveDeferredAndCommit(): void
    {
        $pool = new CacheItemPool('psr6_test');

        $item1 = $pool->getItem('key1');
        $item1->set('value1');
        $pool->saveDeferred($item1);

        $item2 = $pool->getItem('key2');
        $item2->set('value2');
        $pool->saveDeferred($item2);

        // Before commit, deferred items should be accessible
        $this->assertTrue($pool->hasItem('key1'));
        $this->assertTrue($pool->hasItem('key2'));

        $pool->commit();

        // After commit, items should be persisted
        $retrieved1 = $pool->getItem('key1');
        $retrieved2 = $pool->getItem('key2');

        $this->assertTrue($retrieved1->isHit());
        $this->assertTrue($retrieved2->isHit());
        $this->assertSame('value1', $retrieved1->get());
        $this->assertSame('value2', $retrieved2->get());
    }

    /**
     * Test invalid key throws exception.
     */
    public function testInvalidKeyThrowsException(): void
    {
        $pool = new CacheItemPool('psr6_test');

        $this->expectException(InvalidArgumentException::class);
        $pool->getItem('invalid{key');
    }

    /**
     * Test empty key throws exception.
     */
    public function testEmptyKeyThrowsException(): void
    {
        $pool = new CacheItemPool('psr6_test');

        $this->expectException(InvalidArgumentException::class);
        $pool->getItem('');
    }
}
