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

use Cake\Cache\Psr6\CacheItem;
use Cake\TestSuite\TestCase;
use DateInterval;
use DateTimeImmutable;
use Psr\Cache\CacheItemInterface;

/**
 * CacheItemTest class
 */
class CacheItemTest extends TestCase
{
    /**
     * Test that item implements PSR-6 interface.
     */
    public function testImplementsInterface(): void
    {
        $item = new CacheItem('test_key');
        $this->assertInstanceOf(CacheItemInterface::class, $item);
    }

    /**
     * Test getKey.
     */
    public function testGetKey(): void
    {
        $item = new CacheItem('my_cache_key');
        $this->assertSame('my_cache_key', $item->getKey());
    }

    /**
     * Test isHit for miss.
     */
    public function testIsHitMiss(): void
    {
        $item = new CacheItem('key', false);
        $this->assertFalse($item->isHit());
    }

    /**
     * Test isHit for hit.
     */
    public function testIsHitSuccess(): void
    {
        $item = new CacheItem('key', true);
        $this->assertTrue($item->isHit());
    }

    /**
     * Test get and set.
     */
    public function testGetSet(): void
    {
        $item = new CacheItem('key');
        $this->assertNull($item->get());

        $result = $item->set('my value');
        $this->assertSame($item, $result);
        $this->assertSame('my value', $item->get());
    }

    /**
     * Test set with various types.
     */
    public function testSetVariousTypes(): void
    {
        $item = new CacheItem('key');

        $item->set(['array', 'value']);
        $this->assertSame(['array', 'value'], $item->get());

        $item->set(123);
        $this->assertSame(123, $item->get());

        $item->set(null);
        $this->assertNull($item->get());

        $obj = new \stdClass();
        $item->set($obj);
        $this->assertSame($obj, $item->get());
    }

    /**
     * Test expiresAt with DateTime.
     */
    public function testExpiresAt(): void
    {
        $item = new CacheItem('key');
        $expiration = new DateTimeImmutable('+1 hour');

        $result = $item->expiresAt($expiration);
        $this->assertSame($item, $result);
        $this->assertSame($expiration, $item->getExpiration());
    }

    /**
     * Test expiresAt with null.
     */
    public function testExpiresAtNull(): void
    {
        $item = new CacheItem('key');
        $item->expiresAt(null);
        $this->assertNull($item->getExpiration());
    }

    /**
     * Test expiresAfter with int.
     */
    public function testExpiresAfterInt(): void
    {
        $item = new CacheItem('key');
        $before = time();

        $result = $item->expiresAfter(3600);
        $this->assertSame($item, $result);

        $ttl = $item->getTtl();
        $this->assertGreaterThanOrEqual(3599, $ttl);
        $this->assertLessThanOrEqual(3600, $ttl);
    }

    /**
     * Test expiresAfter with DateInterval.
     */
    public function testExpiresAfterDateInterval(): void
    {
        $item = new CacheItem('key');
        $interval = new DateInterval('PT1H');

        $item->expiresAfter($interval);

        $ttl = $item->getTtl();
        $this->assertGreaterThanOrEqual(3599, $ttl);
        $this->assertLessThanOrEqual(3600, $ttl);
    }

    /**
     * Test expiresAfter with null.
     */
    public function testExpiresAfterNull(): void
    {
        $item = new CacheItem('key');
        $item->expiresAfter(null);
        $this->assertNull($item->getTtl());
    }

    /**
     * Test getTtl returns null when no expiration set.
     */
    public function testGetTtlNoExpiration(): void
    {
        $item = new CacheItem('key');
        $this->assertNull($item->getTtl());
    }
}
