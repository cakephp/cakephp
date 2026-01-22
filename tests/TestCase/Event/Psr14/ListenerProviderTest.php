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

use Cake\Event\Psr14\ListenerProvider;
use Cake\TestSuite\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use stdClass;

/**
 * ListenerProviderTest class
 */
class ListenerProviderTest extends TestCase
{
    /**
     * Test that provider implements PSR-14 interface.
     */
    public function testImplementsInterface(): void
    {
        $provider = new ListenerProvider();
        $this->assertInstanceOf(ListenerProviderInterface::class, $provider);
    }

    /**
     * Test getListenersForEvent with no listeners.
     */
    public function testGetListenersForEventEmpty(): void
    {
        $provider = new ListenerProvider();
        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertEmpty($listeners);
    }

    /**
     * Test addListener and getListenersForEvent.
     */
    public function testAddListenerAndGetListeners(): void
    {
        $listener = function (stdClass $event) {
        };

        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, $listener);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertCount(1, $listeners);
        $this->assertSame($listener, $listeners[0]);
    }

    /**
     * Test listeners are called for parent classes.
     */
    public function testListenersMatchParentClasses(): void
    {
        $listener = function (object $event) {
        };

        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, $listener);

        // ExtendedClass extends stdClass
        $event = new class extends stdClass
        {
        };

        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(1, $listeners);
    }

    /**
     * Test priority ordering.
     */
    public function testPriorityOrdering(): void
    {
        $listener1 = function () {
        };
        $listener2 = function () {
        };
        $listener3 = function () {
        };

        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, $listener1, 5);
        $provider->addListener(stdClass::class, $listener2, 10);
        $provider->addListener(stdClass::class, $listener3, 1);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertSame($listener2, $listeners[0]); // priority 10
        $this->assertSame($listener1, $listeners[1]); // priority 5
        $this->assertSame($listener3, $listeners[2]); // priority 1
    }

    /**
     * Test removeListener.
     */
    public function testRemoveListener(): void
    {
        $listener1 = function () {
        };
        $listener2 = function () {
        };

        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, $listener1);
        $provider->addListener(stdClass::class, $listener2);
        $provider->removeListener(stdClass::class, $listener1);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertCount(1, $listeners);
        $this->assertSame($listener2, $listeners[0]);
    }

    /**
     * Test removeListener with non-existent listener.
     */
    public function testRemoveListenerNotFound(): void
    {
        $listener1 = function () {
        };
        $listener2 = function () {
        };

        $provider = new ListenerProvider();
        $provider->addListener(stdClass::class, $listener1);
        $provider->removeListener(stdClass::class, $listener2);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertCount(1, $listeners);
    }

    /**
     * Test clearListeners for specific event.
     */
    public function testClearListenersForEvent(): void
    {
        $testEvent = new class
        {
        };
        $testEventClass = $testEvent::class;

        $provider = new ListenerProvider();
        $provider->addListener(
            stdClass::class,
            function () {
            },
        );
        $provider->addListener(
            $testEventClass,
            function () {
            },
        );

        $provider->clearListeners(stdClass::class);

        $this->assertFalse($provider->hasListeners(stdClass::class));
        $this->assertTrue($provider->hasListeners($testEventClass));
    }

    /**
     * Test clearListeners for all events.
     */
    public function testClearListenersAll(): void
    {
        $testEvent = new class
        {
        };
        $testEventClass = $testEvent::class;

        $provider = new ListenerProvider();
        $provider->addListener(
            stdClass::class,
            function () {
            },
        );
        $provider->addListener(
            $testEventClass,
            function () {
            },
        );

        $provider->clearListeners();

        $this->assertFalse($provider->hasListeners(stdClass::class));
        $this->assertFalse($provider->hasListeners($testEventClass));
    }

    /**
     * Test hasListeners.
     */
    public function testHasListeners(): void
    {
        $provider = new ListenerProvider();

        $this->assertFalse($provider->hasListeners(stdClass::class));

        $provider->addListener(
            stdClass::class,
            function () {
            },
        );

        $this->assertTrue($provider->hasListeners(stdClass::class));
    }

    /**
     * Test fluent interface.
     */
    public function testFluentInterface(): void
    {
        $provider = new ListenerProvider();
        $listener = function () {
        };

        $result = $provider
            ->addListener(stdClass::class, $listener)
            ->removeListener(stdClass::class, $listener)
            ->clearListeners();

        $this->assertSame($provider, $result);
    }
}
