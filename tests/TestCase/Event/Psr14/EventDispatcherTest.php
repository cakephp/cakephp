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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event\Psr14;

use Cake\Event\Psr14\EventDispatcher;
use Cake\Event\Psr14\ListenerProvider;
use Cake\Event\Psr14\StoppableEventTrait;
use Cake\TestSuite\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use stdClass;

/**
 * EventDispatcherTest class
 */
class EventDispatcherTest extends TestCase
{
    /**
     * Test that dispatcher implements PSR-14 interface.
     */
    public function testImplementsInterface(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    /**
     * Test dispatch calls listeners.
     */
    public function testDispatchCallsListeners(): void
    {
        $called = false;
        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, function (stdClass $event) use (&$called) {
            $called = true;
        });

        $dispatcher = new EventDispatcher($provider);
        $event = new stdClass();
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertSame($event, $result);
    }

    /**
     * Test dispatch returns the event.
     */
    public function testDispatchReturnsEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new stdClass();
        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatch with multiple listeners.
     */
    public function testDispatchMultipleListeners(): void
    {
        $order = [];
        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, function () use (&$order) {
            $order[] = 'first';
        }, 10);
        $provider->addListener(stdClass::class, function () use (&$order) {
            $order[] = 'second';
        }, 5);
        $provider->addListener(stdClass::class, function () use (&$order) {
            $order[] = 'third';
        }, 15);

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new stdClass());

        // Should be ordered by priority (highest first)
        $this->assertSame(['third', 'first', 'second'], $order);
    }

    /**
     * Test dispatch stops on stoppable event.
     */
    public function testDispatchStopsOnStoppableEvent(): void
    {
        $order = [];

        $provider = new ListenerProvider();
        $provider->addListener(TestStoppableEvent::class, function (TestStoppableEvent $event) use (&$order) {
            $order[] = 'first';
            $event->stopPropagation();
        }, 10);
        $provider->addListener(TestStoppableEvent::class, function () use (&$order) {
            $order[] = 'second';
        }, 5);

        $dispatcher = new EventDispatcher($provider);
        $event = new TestStoppableEvent();
        $dispatcher->dispatch($event);

        $this->assertSame(['first'], $order);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * Test getListenerProvider.
     */
    public function testGetListenerProvider(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $this->assertSame($provider, $dispatcher->getListenerProvider());
    }
}

/**
 * Test event class that implements StoppableEventInterface.
 */
class TestStoppableEvent implements StoppableEventInterface
{
    use StoppableEventTrait;
}
