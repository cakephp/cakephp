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

trait EngineEventsTrait
{
    protected string $engine = '';

    public function testGetEventsAreFired(): void
    {
        $beforeEventIsCalled = false;
        $afterEventIsCalled = false;
        $manager = Cache::pool($this->engine)->getEventManager();
        $manager->on('Cache.beforeGet', function ($event, $key, $default) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertSame(null, $default);
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterGet', function ($event, $key, $value, $success) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            if ($this->engine === 'apcu') {
                $this->assertFalse($value);
            } else {
                $this->assertNull($value);
            }
            $this->assertFalse($success);
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
        $manager->on('Cache.beforeSet', function ($event, $key, $value, $ttl) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(1234, $value);
            $this->assertNull($ttl);
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterSet', function ($event, $key, $value, $success) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(1234, $value);
            $this->assertTrue($success);
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
        $manager->on('Cache.beforeAdd', function ($event, $key, $value) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(1234, $value);
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterAdd', function ($event, $key, $value, $success) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(1234, $value);
            $this->assertTrue($success);
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
        $manager->on('Cache.beforeIncrement', function ($event, $key, $offset) use (&$beforeIncEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(1234, $offset);
            $beforeIncEventIsCalled = true;
        });
        $manager->on('Cache.beforeDecrement', function ($event, $key, $offset) use (&$beforeDecEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(234, $offset);
            $beforeDecEventIsCalled = true;
        });
        $manager->on('Cache.afterIncrement', function ($event, $key, $offset, $success, $value) use (&$afterIncEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(1234, $offset);
            if ($this->engine !== 'memcached') {
                // No idea why memcached doesn't work in CI
                $this->assertTrue($success);
                $this->assertEquals(1234, $value);
            }
            $afterIncEventIsCalled = true;
        });
        $manager->on('Cache.afterDecrement', function ($event, $key, $offset, $success, $value) use (&$afterDecEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertEquals(234, $offset);
            if ($this->engine !== 'memcached') {
                // No idea why memcached doesn't work in CI
                $this->assertTrue($success);
                $this->assertEquals(1000, $value);
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
        $manager->on('Cache.beforeDelete', function ($event, $key) use (&$beforeEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $beforeEventIsCalled = true;
        });
        $manager->on('Cache.afterDelete', function ($event, $key, $success) use (&$afterEventIsCalled): void {
            $this->assertSame('cake_test', $key);
            $this->assertTrue($success);
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
        $manager->on('Cache.clearedGroup', function ($event, $group) use (&$eventIsCalled): void {
            $this->assertSame('someGroup', $group);
            $eventIsCalled = true;
        });

        Cache::clearGroup('someGroup', $this->engine);

        $this->assertTrue($eventIsCalled);
    }
}
