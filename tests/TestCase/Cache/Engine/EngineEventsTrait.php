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
use Cake\Cache\Event\CacheAfterAddEvent;
use Cake\Cache\Event\CacheAfterDecrementEvent;
use Cake\Cache\Event\CacheAfterDeleteEvent;
use Cake\Cache\Event\CacheAfterGetEvent;
use Cake\Cache\Event\CacheAfterIncrementEvent;
use Cake\Cache\Event\CacheAfterSetEvent;
use Cake\Cache\Event\CacheBeforeAddEvent;
use Cake\Cache\Event\CacheBeforeDecrementEvent;
use Cake\Cache\Event\CacheBeforeDeleteEvent;
use Cake\Cache\Event\CacheBeforeGetEvent;
use Cake\Cache\Event\CacheBeforeIncrementEvent;
use Cake\Cache\Event\CacheBeforeSetEvent;
use Cake\Cache\Event\CacheClearedEvent;
use Cake\Cache\Event\CacheGroupClearEvent;

trait EngineEventsTrait
{
    protected string $engine = '';

    public function testGetEventsAreFired(): void
    {
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on(CacheBeforeGetEvent::NAME, function (CacheBeforeGetEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertSame(null, $event->getDefault());
            $beforeEventIsCalled = true;
        });
        $manager->on(CacheAfterGetEvent::NAME, function (CacheAfterGetEvent $event) use (&$afterEventIsCalled): void {
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
        $manager->on(CacheBeforeSetEvent::NAME, function (CacheBeforeSetEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getValue());
            $this->assertNull($event->getTtl());
            $beforeEventIsCalled = true;
        });
        $manager->on(CacheAfterSetEvent::NAME, function (CacheAfterSetEvent $event) use (&$afterEventIsCalled): void {
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
        $manager->on(CacheBeforeAddEvent::NAME, function (CacheBeforeAddEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getValue());
            $beforeEventIsCalled = true;
        });
        $manager->on(CacheAfterAddEvent::NAME, function (CacheAfterAddEvent $event) use (&$afterEventIsCalled): void {
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
        $manager->on(CacheBeforeIncrementEvent::NAME, function (CacheBeforeIncrementEvent $event) use (&$beforeIncEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getOffset());
            $beforeIncEventIsCalled = true;
        });
        $manager->on(CacheBeforeDecrementEvent::NAME, function (CacheBeforeDecrementEvent $event) use (&$beforeDecEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(234, $event->getOffset());
            $beforeDecEventIsCalled = true;
        });
        $manager->on(CacheAfterIncrementEvent::NAME, function (CacheAfterIncrementEvent $event) use (&$afterIncEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $this->assertEquals(1234, $event->getOffset());
            if ($this->engine !== 'memcached') {
                // No idea why memcached doesn't work in CI
                $this->assertTrue($event->getResult());
                $this->assertEquals(1234, $event->getValue());
            }
            $afterIncEventIsCalled = true;
        });
        $manager->on(CacheAfterDecrementEvent::NAME, function (CacheAfterDecrementEvent $event) use (&$afterDecEventIsCalled): void {
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
        $manager->on(CacheBeforeDeleteEvent::NAME, function (CacheBeforeDeleteEvent $event) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $event->getKey());
            $beforeEventIsCalled = true;
        });
        $manager->on(CacheAfterDeleteEvent::NAME, function (CacheAfterDeleteEvent $event) use (&$afterEventIsCalled): void {
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
        $manager->on(CacheClearedEvent::NAME, function (CacheClearedEvent $e) use (&$eventIsCalled): void {
            $eventIsCalled = true;
        });

        Cache::clear($this->engine);

        $this->assertTrue($eventIsCalled);
    }

    public function testClearGroupEventsAreFired(): void
    {
        $eventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on(CacheGroupClearEvent::NAME, function (CacheGroupClearEvent $event) use (&$eventIsCalled): void {
            $this->assertSame('someGroup', $event->getGroup());
            $eventIsCalled = true;
        });

        Cache::clearGroup('someGroup', $this->engine);

        $this->assertTrue($eventIsCalled);
    }
}
