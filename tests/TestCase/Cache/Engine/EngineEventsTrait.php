<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Event\AfterCacheEvent;
use Cake\Cache\Event\BeforeCacheEvent;
use Cake\Cache\Event\GroupClearCacheEvent;

trait EngineEventsTrait
{
    protected string $engine = '';

    public function testGetEventsAreFired(): void
    {
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.beforeGet', function (BeforeCacheEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertSame(null, $event->getDefault());
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterGet', function (AfterCacheEvent $event) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            if ($this->engine === 'apcu') {
                $this->assertFalse($event->getValue());
            } else {
                $this->assertNull($event->getValue());
            }
            $this->assertFalse($event->getResult());
            $afterEventIsCalled = true;
        });

        Cache::read('test', $this->engine);

        $this->assertTrue($beforeEventIsCalled);
        $this->assertTrue($afterEventIsCalled);
    }

    public function testSetEventsAreFired(): void
    {
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.beforeSet', function (BeforeCacheEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getValue());
            $this->assertNull($event->getTtl());
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterSet', function (AfterCacheEvent $event) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getValue());
            $this->assertTrue($event->getResult());
            $afterEventIsCalled = true;
        });

        Cache::write('test', 1234, $this->engine);

        $this->assertTrue($beforeEventIsCalled);
        $this->assertTrue($afterEventIsCalled);
    }

    public function testAddEventsAreFired(): void
    {
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.beforeAdd', function (BeforeCacheEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getValue());
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterAdd', function (AfterCacheEvent $event) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getValue());
            $this->assertTrue($event->getResult());
            $afterEventIsCalled = true;
        });

        Cache::delete('test', $this->engine);
        Cache::add('test', 1234, $this->engine);

        $this->assertTrue($beforeEventIsCalled);
        $this->assertTrue($afterEventIsCalled);
    }

    public function testIncDecEventsAreFired(): void
    {
        $this->skipIf($this->engine === 'file_test', 'File engine does not support increment/decrement.');

        $beforeIncEventIsCalled = false;
        $beforeDecEventIsCalled = false;
        $afterIncEventIsCalled = false;
        $afterDecEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.beforeIncrement', function (BeforeCacheEvent $event) use (&$beforeIncEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getOffset());
            $beforeIncEventIsCalled = true;
        });
        $manager->on('Cache.beforeDecrement', function (BeforeCacheEvent $event) use (&$beforeDecEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(234, $event->getOffset());
            $beforeDecEventIsCalled = true;
        });
        $manager->on('Cache.afterIncrement', function (AfterCacheEvent $event) use (&$afterIncEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getOffset());
            if ($this->engine !== 'memcached') {
                // No idea why memcached doesn't work in CI
                $this->assertTrue($event->getResult());
                $this->assertEquals(1234, $event->getValue());
            }
            $afterIncEventIsCalled = true;
        });
        $manager->on('Cache.afterDecrement', function (AfterCacheEvent $event) use (&$afterDecEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(234, $event->getOffset());
            if ($this->engine !== 'memcached') {
                // No idea why memcached doesn't work in CI
                $this->assertTrue($event->getResult());
                $this->assertEquals(1000, $event->getValue());
            }
            $afterDecEventIsCalled = true;
        });

        Cache::delete('test', $this->engine);
        Cache::increment('test', 1234, $this->engine);
        Cache::decrement('test', 234, $this->engine);

        $this->assertTrue($beforeIncEventIsCalled);
        $this->assertTrue($afterIncEventIsCalled);
        $this->assertTrue($beforeDecEventIsCalled);
        $this->assertTrue($afterDecEventIsCalled);
    }

    public function testDeleteEventsAreFired(): void
    {
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.beforeDelete', function (BeforeCacheEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterDelete', function (AfterCacheEvent $event) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertTrue($event->getResult());
            $afterEventIsCalled = true;
        });

        // We need to write something first so delete returns true.
        Cache::write('test', 1234, $this->engine);
        Cache::delete('test', $this->engine);

        $this->assertTrue($beforeEventIsCalled);
        $this->assertTrue($afterEventIsCalled);
    }

    public function testClearEventsAreFired(): void
    {
        $eventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.cleared', function () use (&$eventIsCalled): void {
            $eventIsCalled = true;
        });

        Cache::clear($this->engine);

        $this->assertTrue($eventIsCalled);
    }

    public function testClearGroupEventsAreFired(): void
    {
        $eventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.clearedGroup', function (GroupClearCacheEvent $event) use (&$eventIsCalled): void {
            $this->assertSame('someGroup', $event->getGroup());
            $eventIsCalled = true;
        });

        Cache::clearGroup('someGroup', $this->engine);

        $this->assertTrue($eventIsCalled);
    }
}
