<?php
declare(strict_types=1);

/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventList;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Closure;
use TestApp\TestCase\Event\CustomTestEventListenerInterface;
use TestApp\TestCase\Event\EventTestListener;

/**
 * Tests the Cake\Event\EventManager class functionality
 */
class EventManagerTest extends TestCase
{
    /**
     * Test attach() with a listener interface.
     */
    public function testAttachListener(): void
    {
        $manager = new EventManager();
        $listener = new CustomTestEventListenerInterface();
        $manager->on($listener);
        $expected = [
            ['callable' => [$listener, 'listenerFunction']],
        ];
        $this->assertEquals($expected, $manager->listeners('fake.event'));

        $expected = [
            ['callable' => Closure::fromCallable([$listener, 'thirdListenerFunction'])],
        ];
        $this->assertEquals($expected, $manager->listeners('closure.event'));
    }

    /**
     * Tests attached event listeners for matching key pattern
     */
    public function testMatchingListeners(): void
    {
        $manager = new EventManager();
        $manager->on('fake.event', 'fakeFunction1');
        $manager->on('real.event', 'fakeFunction2');
        $manager->on('test.event', 'fakeFunction3');
        $manager->on('event.test', 'fakeFunction4');

        $this->assertArrayHasKey('fake.event', $manager->matchingListeners('fake.event'));
        $this->assertArrayHasKey('real.event', $manager->matchingListeners('real.event'));
        $this->assertArrayHasKey('test.event', $manager->matchingListeners('test.event'));
        $this->assertArrayHasKey('event.test', $manager->matchingListeners('event.test'));

        $this->assertArrayHasKey('fake.event', $manager->matchingListeners('fake'));
        $this->assertArrayHasKey('real.event', $manager->matchingListeners('real'));
        $this->assertArrayHasKey('test.event', $manager->matchingListeners('test'));
        $this->assertArrayHasKey('event.test', $manager->matchingListeners('test'));
        $this->assertArrayHasKey('fake.event', $manager->matchingListeners('event'));
        $this->assertArrayHasKey('real.event', $manager->matchingListeners('event'));
        $this->assertArrayHasKey('test.event', $manager->matchingListeners('event'));
        $this->assertArrayHasKey('event.test', $manager->matchingListeners('event'));
        $this->assertArrayHasKey('fake.event', $manager->matchingListeners('.event'));
        $this->assertArrayHasKey('real.event', $manager->matchingListeners('.event'));
        $this->assertArrayHasKey('test.event', $manager->matchingListeners('.event'));
        $this->assertArrayHasKey('test.event', $manager->matchingListeners('test.'));
        $this->assertArrayHasKey('event.test', $manager->matchingListeners('.test'));

        $this->assertEmpty($manager->matchingListeners('/test'));
        $this->assertEmpty($manager->matchingListeners('test/'));
        $this->assertEmpty($manager->matchingListeners('/test/'));
        $this->assertEmpty($manager->matchingListeners('test$'));
        $this->assertEmpty($manager->matchingListeners('ev.nt'));
        $this->assertEmpty($manager->matchingListeners('^test'));
        $this->assertEmpty($manager->matchingListeners('^event'));
        $this->assertEmpty($manager->matchingListeners('*event'));
        $this->assertEmpty($manager->matchingListeners('event*'));
        $this->assertEmpty($manager->matchingListeners('foo'));

        $expected = ['fake.event', 'real.event', 'test.event', 'event.test'];
        $result = $manager->matchingListeners('event');
        $this->assertNotEmpty($result);
        $this->assertSame($expected, array_keys($result));

        $expected = ['fake.event', 'real.event', 'test.event'];
        $result = $manager->matchingListeners('.event');
        $this->assertNotEmpty($result);
        $this->assertSame($expected, array_keys($result));

        $expected = ['test.event', 'event.test'];
        $result = $manager->matchingListeners('test');
        $this->assertNotEmpty($result);
        $this->assertSame($expected, array_keys($result));

        $expected = ['test.event'];
        $result = $manager->matchingListeners('test.');
        $this->assertNotEmpty($result);
        $this->assertSame($expected, array_keys($result));

        $expected = ['event.test'];
        $result = $manager->matchingListeners('.test');
        $this->assertNotEmpty($result);
        $this->assertSame($expected, array_keys($result));
    }

    /**
     * Test the on() method for basic callable types.
     */
    public function testOn(): void
    {
        $count = 1;
        $manager = new EventManager();
        $manager->on('my.event', 'substr');
        $expected = [
            ['callable' => 'substr'],
        ];
        $this->assertSame($expected, $manager->listeners('my.event'));

        $manager->on('my.event', ['priority' => 1], 'strpos');
        $expected = [
            ['callable' => 'strpos'],
            ['callable' => 'substr'],
        ];
        $this->assertSame($expected, $manager->listeners('my.event'));

        $listener = new CustomTestEventListenerInterface();
        $manager->on($listener);
        $expected = [
            ['callable' => [$listener, 'listenerFunction']],
        ];
        $this->assertEquals($expected, $manager->listeners('fake.event'));
    }

    /**
     * Tests off'ing an event from a event key queue
     */
    public function testOff(): void
    {
        $manager = new EventManager();
        $manager->on('fake.event', ['AClass', 'aMethod']);
        $manager->on('another.event', ['AClass', 'anotherMethod']);
        $manager->on('another.event', ['priority' => 1], 'substr');

        $manager->off('fake.event', ['AClass', 'aMethod']);
        $this->assertEquals([], $manager->listeners('fake.event'));

        $manager->off('another.event', ['AClass', 'anotherMethod']);
        $expected = [
            ['callable' => 'substr'],
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));

        $manager->off('another.event', 'substr');
        $this->assertEquals([], $manager->listeners('another.event'));
    }

    /**
     * Tests off'ing an event from all event queues
     */
    public function testOffFromAll(): void
    {
        $manager = new EventManager();
        $callable = function (): void {
        };
        $manager->on('fake.event', $callable);
        $manager->on('another.event', $callable);
        $manager->on('another.event', ['priority' => 1], 'substr');

        $manager->off($callable);
        $expected = [
            ['callable' => 'substr'],
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $this->assertEquals([], $manager->listeners('fake.event'));
    }

    /**
     * Tests off'ing all listeners for an event
     */
    public function testRemoveAllListeners(): void
    {
        $manager = new EventManager();
        $manager->on('fake.event', ['AClass', 'aMethod']);

        $manager->on('another.event', ['priority' => 1], 'substr');

        $manager->off('fake.event');

        $expected = [
            ['callable' => 'substr'],
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $this->assertEquals([], $manager->listeners('fake.event'));
    }

    /**
     * Tests event dispatching
     *
     * @triggers fake.event
     */
    public function testDispatch(): void
    {
        $manager = new EventManager();
        $listener = $this->getMockBuilder(EventTestListener::class)
            ->getMock();
        $anotherListener = $this->getMockBuilder(EventTestListener::class)
            ->getMock();
        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $manager->on('fake.event', [$anotherListener, 'listenerFunction']);
        $event = new Event('fake.event');

        $listener->expects($this->once())->method('listenerFunction')->with($event);
        $anotherListener->expects($this->once())->method('listenerFunction')->with($event);
        $manager->dispatch($event);
    }

    /**
     * Tests event dispatching using event key name
     */
    public function testDispatchWithKeyName(): void
    {
        $manager = new EventManager();
        $listener = new EventTestListener();
        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $event = 'fake.event';
        $manager->dispatch($event);

        $expected = ['listenerFunction'];
        $this->assertEquals($expected, $listener->callList);
    }

    /**
     * Tests event dispatching with a return value
     *
     * @triggers fake.event
     */
    public function testDispatchReturnValue(): void
    {
        $manager = new EventManager();
        $listener = $this->getMockBuilder(EventTestListener::class)
            ->getMock();
        $anotherListener = $this->getMockBuilder(EventTestListener::class)
            ->getMock();
        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $manager->on('fake.event', [$anotherListener, 'listenerFunction']);
        $event = new Event('fake.event');

        $listener->expects($this->once())
            ->method('listenerFunction')
            ->with($event)
            ->will($this->returnValue('something special'));
        $anotherListener->expects($this->once())
            ->method('listenerFunction')
            ->with($event);
        $manager->dispatch($event);
        $this->assertSame('something special', $event->getResult());
    }

    /**
     * Tests that returning false in a callback stops the event
     *
     * @triggers fake.event
     */
    public function testDispatchFalseStopsEvent(): void
    {
        $manager = new EventManager();
        $listener = $this->getMockBuilder(EventTestListener::class)
            ->getMock();
        $anotherListener = $this->getMockBuilder(EventTestListener::class)
            ->getMock();
        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $manager->on('fake.event', [$anotherListener, 'listenerFunction']);
        $event = new Event('fake.event');

        $listener->expects($this->once())->method('listenerFunction')
            ->with($event)
            ->will($this->returnValue(false));
        $anotherListener->expects($this->never())
            ->method('listenerFunction');
        $manager->dispatch($event);
        $this->assertTrue($event->isStopped());
    }

    /**
     * Tests event dispatching using priorities
     *
     * @triggers fake.event
     */
    public function testDispatchPrioritized(): void
    {
        $manager = new EventManager();
        $listener = new EventTestListener();
        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $manager->on('fake.event', ['priority' => 5], [$listener, 'secondListenerFunction']);
        $event = new Event('fake.event');
        $manager->dispatch($event);

        $expected = ['secondListenerFunction', 'listenerFunction'];
        $this->assertEquals($expected, $listener->callList);
    }

    /**
     * Tests subscribing a listener object and firing the events it subscribed to
     *
     * @triggers fake.event
     * @triggers another.event $this, array(some => data)
     */
    public function testOnSubscriber(): void
    {
        $manager = new EventManager();
        /** @var \TestApp\TestCase\Event\CustomTestEventListenerInterface|\PHPUnit\Framework\MockObject\MockObject $listener */
        $listener = $this->getMockBuilder(CustomTestEventListenerInterface::class)
            ->onlyMethods(['secondListenerFunction'])
            ->getMock();
        $manager->on($listener);

        $event = new Event('fake.event');
        $manager->dispatch($event);

        $expected = ['listenerFunction'];
        $this->assertEquals($expected, $listener->callList);

        $event = new Event('another.event', $this, ['some' => 'data']);
        $listener->expects($this->once())
            ->method('secondListenerFunction')
            ->with($event, 'data');
        $manager->dispatch($event);
    }

    /**
     * Test implementedEvents binding multiple callbacks to the same event name.
     *
     * @triggers multiple.handlers
     */
    public function testOnSubscriberMultiple(): void
    {
        $manager = new EventManager();
        $listener = $this->getMockBuilder(CustomTestEventListenerInterface::class)
            ->onlyMethods(['listenerFunction', 'secondListenerFunction'])
            ->getMock();
        $manager->on($listener);
        $event = new Event('multiple.handlers');
        $listener->expects($this->once())
            ->method('listenerFunction')
            ->with($event);
        $listener->expects($this->once())
            ->method('secondListenerFunction')
            ->with($event);
        $manager->dispatch($event);
    }

    /**
     * Tests subscribing a listener object and firing the events it subscribed to
     */
    public function testDetachSubscriber(): void
    {
        $manager = new EventManager();
        $listener = $this->getMockBuilder(CustomTestEventListenerInterface::class)
            ->onlyMethods(['secondListenerFunction'])
            ->getMock();
        $manager->on($listener);
        $expected = [
            ['callable' => [$listener, 'secondListenerFunction']],
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $expected = [
            ['callable' => [$listener, 'listenerFunction']],
        ];
        $this->assertEquals($expected, $manager->listeners('fake.event'));
        $manager->off($listener);
        $this->assertEquals([], $manager->listeners('fake.event'));
        $this->assertEquals([], $manager->listeners('another.event'));
    }

    /**
     * Tests that it is possible to get/set the manager singleton
     */
    public function testGlobalDispatcherGetter(): void
    {
        $this->assertInstanceOf('Cake\Event\EventManager', EventManager::instance());
        $manager = new EventManager();

        EventManager::instance($manager);
        $this->assertSame($manager, EventManager::instance());
    }

    /**
     * Tests that the global event manager gets the event too from any other manager
     *
     * @triggers fake.event
     */
    public function testDispatchWithGlobal(): void
    {
        $generalManager = $this->getMockBuilder('Cake\Event\EventManager')
            ->onlyMethods(['prioritisedListeners'])
            ->getMock();
        $manager = new EventManager();
        $event = new Event('fake.event');
        EventManager::instance($generalManager);

        $generalManager->expects($this->once())->method('prioritisedListeners')->with('fake.event');
        $manager->dispatch($event);
        EventManager::instance(new EventManager());
    }

    /**
     * Tests that stopping an event will not notify the rest of the listeners
     *
     * @triggers fake.event
     */
    public function testStopPropagation(): void
    {
        $generalManager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $manager = new EventManager();
        $listener = new EventTestListener();

        EventManager::instance($generalManager);
        $generalManager->expects($this->any())
                ->method('prioritisedListeners')
                ->with('fake.event')
                ->will($this->returnValue([]));

        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $manager->on('fake.event', ['priority' => 8], [$listener, 'stopListener']);
        $manager->on('fake.event', ['priority' => 5], [$listener, 'secondListenerFunction']);
        $event = new Event('fake.event');
        $manager->dispatch($event);

        $expected = ['secondListenerFunction'];
        $this->assertEquals($expected, $listener->callList);
        EventManager::instance(new EventManager());
    }

    /**
     * Tests event dispatching using priorities
     *
     * @triggers fake.event
     */
    public function testDispatchPrioritizedWithGlobal(): void
    {
        $generalManager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $manager = new EventManager();
        $listener = new CustomTestEventListenerInterface();
        $event = new Event('fake.event');

        EventManager::instance($generalManager);
        $generalManager->expects($this->any())
                ->method('prioritisedListeners')
                ->with('fake.event')
                ->will($this->returnValue(
                    [11 => [
                        ['callable' => [$listener, 'secondListenerFunction']],
                    ]]
                ));

        $manager->on('fake.event', [$listener, 'listenerFunction']);
        $manager->on('fake.event', ['priority' => 15], [$listener, 'thirdListenerFunction']);

        $manager->dispatch($event);

        $expected = ['listenerFunction', 'secondListenerFunction', 'thirdListenerFunction'];
        $this->assertEquals($expected, $listener->callList);
        EventManager::instance(new EventManager());
    }

    /**
     * Tests event dispatching using priorities
     *
     * @triggers fake.event
     */
    public function testDispatchGlobalBeforeLocal(): void
    {
        $generalManager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $manager = new EventManager();
        $listener = new CustomTestEventListenerInterface();
        $event = new Event('fake.event');

        EventManager::instance($generalManager);
        $generalManager->expects($this->any())
                ->method('prioritisedListeners')
                ->with('fake.event')
                ->will($this->returnValue(
                    [10 => [
                        ['callable' => [$listener, 'listenerFunction']],
                    ]]
                ));

        $manager->on('fake.event', [$listener, 'secondListenerFunction']);

        $manager->dispatch($event);

        $expected = ['listenerFunction', 'secondListenerFunction'];
        $this->assertEquals($expected, $listener->callList);
        EventManager::instance(new EventManager());
    }

    /**
     * test callback
     */
    public function onMyEvent(EventInterface $event): void
    {
        $event->setData('callback', 'ok');
    }

    /**
     * Tests events dispatched by a local manager can be handled by
     * handler registered in the global event manager
     *
     * @triggers my_event $manager
     */
    public function testDispatchLocalHandledByGlobal(): void
    {
        $callback = [$this, 'onMyEvent'];
        EventManager::instance()->on('my_event', $callback);
        $manager = new EventManager();
        $event = new Event('my_event', $manager);
        $manager->dispatch($event);
        $this->assertSame('ok', $event->getData('callback'));
    }

    /**
     * Test that events are dispatched properly when there are global and local
     * listeners at the same priority.
     *
     * @triggers fake.event $this
     */
    public function testDispatchWithGlobalAndLocalEvents(): void
    {
        $listener = new CustomTestEventListenerInterface();
        EventManager::instance()->on($listener);
        $listener2 = new EventTestListener();
        $manager = new EventManager();
        $manager->on('fake.event', [$listener2, 'listenerFunction']);

        $manager->dispatch(new Event('fake.event', $this));
        $this->assertEquals(['listenerFunction'], $listener->callList);
        $this->assertEquals(['listenerFunction'], $listener2->callList);
    }

    /**
     * Test getting a list of dispatched events from the manager.
     *
     * @triggers my_event $this
     * @triggers my_second_event $this
     */
    public function testGetDispatchedEvents(): void
    {
        $eventList = new EventList();
        $event = new Event('my_event', $this);
        $event2 = new Event('my_second_event', $this);

        $manager = new EventManager();
        $manager->setEventList($eventList);
        $manager->dispatch($event);
        $manager->dispatch($event2);

        $result = $manager->getEventList();
        $this->assertInstanceOf('Cake\Event\EventList', $result);
        $this->assertCount(2, $result);
        $this->assertEquals($result[0], $event);
        $this->assertEquals($result[1], $event2);

        $manager->getEventList()->flush();
        $result = $manager->getEventList();
        $this->assertCount(0, $result);

        $manager->unsetEventList();
        $manager->dispatch($event);
        $manager->dispatch($event2);

        $result = $manager->getEventList();
        $this->assertNull($result);
    }

    /**
     * Test that locally dispatched events are also added to the global manager's event list
     *
     * @triggers Event $this
     */
    public function testGetLocallyDispatchedEventsFromGlobal(): void
    {
        $localList = new EventList();
        $globalList = new EventList();

        $globalManager = EventManager::instance();
        $globalManager->setEventList($globalList);

        $localManager = new EventManager();
        $localManager->setEventList($localList);

        $globalEvent = new Event('GlobalEvent', $this);
        $globalManager->dispatch($globalEvent);

        $localEvent = new Event('LocalEvent', $this);
        $localManager->dispatch($localEvent);

        $this->assertTrue($globalList->hasEvent('GlobalEvent'));
        $this->assertFalse($localList->hasEvent('GlobalEvent'));
        $this->assertTrue($localList->hasEvent('LocalEvent'));
        $this->assertTrue($globalList->hasEvent('LocalEvent'));
    }

    /**
     * Test isTrackingEvents
     */
    public function testIsTrackingEvents(): void
    {
        $this->assertFalse(EventManager::instance()->isTrackingEvents());

        $manager = new EventManager();
        $manager->setEventList(new EventList());

        $this->assertTrue($manager->isTrackingEvents());

        $manager->trackEvents(false);

        $this->assertFalse($manager->isTrackingEvents());
    }

    public function testDebugInfo(): void
    {
        $eventManager = new EventManager();

        $this->assertSame(
            [
                '_listeners' => [],
                '_isGlobal' => false,
                '_trackEvents' => false,
                '_generalManager' => '(object) EventManager',
                '_dispatchedEvents' => null,
            ],
            $eventManager->__debugInfo()
        );

        $eventManager->setEventList(new EventList());
        $eventManager->addEventToList(new Event('Foo', $this));
        $this->assertSame(
            [
                '_listeners' => [],
                '_isGlobal' => false,
                '_trackEvents' => true,
                '_generalManager' => '(object) EventManager',
                '_dispatchedEvents' => [
                    'Foo with subject Cake\Test\TestCase\Event\EventManagerTest',
                ],
            ],
            $eventManager->__debugInfo()
        );

        $eventManager->unsetEventList();

        $func = function (): void {
        };
        $eventManager->on('foo', $func);

        $this->assertSame(
            [
                '_listeners' => [
                    'foo' => '1 listener(s)',
                ],
                '_isGlobal' => false,
                '_trackEvents' => false,
                '_generalManager' => '(object) EventManager',
                '_dispatchedEvents' => null,
            ],
            $eventManager->__debugInfo()
        );

        $eventManager->off('foo', $func);

        $this->assertSame(
            [
                '_listeners' => [
                    'foo' => '0 listener(s)',
                ],
                '_isGlobal' => false,
                '_trackEvents' => false,
                '_generalManager' => '(object) EventManager',
                '_dispatchedEvents' => null,
            ],
            $eventManager->__debugInfo()
        );

        $eventManager->on('bar', function (): void {
        });
        $eventManager->on('bar', function (): void {
        });
        $eventManager->on('bar', function (): void {
        });
        $eventManager->on('baz', function (): void {
        });

        $this->assertSame(
            [
                '_listeners' => [
                    'foo' => '0 listener(s)',
                    'bar' => '3 listener(s)',
                    'baz' => '1 listener(s)',
                ],
                '_isGlobal' => false,
                '_trackEvents' => false,
                '_generalManager' => '(object) EventManager',
                '_dispatchedEvents' => null,
            ],
            $eventManager->__debugInfo()
        );
    }

    /**
     * test debugInfo with an event list.
     */
    public function testDebugInfoEventList(): void
    {
        $eventList = new EventList();
        $eventManager = new EventManager();
        $eventManager->setEventList($eventList);
        $eventManager->on('example', function (): void {
        });
        $eventManager->dispatch('example');

        $this->assertSame(
            [
                '_listeners' => [
                    'example' => '1 listener(s)',
                ],
                '_isGlobal' => false,
                '_trackEvents' => true,
                '_generalManager' => '(object) EventManager',
                '_dispatchedEvents' => [
                    'example with no subject',
                ],
            ],
            $eventManager->__debugInfo()
        );
    }

    /**
     * Test that chainable methods return correct values.
     */
    public function testChainableMethods(): void
    {
        $eventManager = new EventManager();

        $listener = $this->createMock(EventListenerInterface::class);
        $callable = function (): void {
        };

        $returnValue = $eventManager->on($listener);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->on('foo', $callable);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->on('foo', [], $callable);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->off($listener);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->off('foo', $listener);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->off('foo', $callable);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->off('foo');
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->setEventList(new EventList());
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->addEventToList(new Event('foo'));
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->trackEvents(true);
        $this->assertSame($eventManager, $returnValue);

        $returnValue = $eventManager->unsetEventList();
        $this->assertSame($eventManager, $returnValue);
    }
}
