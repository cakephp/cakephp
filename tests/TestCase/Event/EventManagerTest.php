<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * Mock class used to test event dispatching
 */
class EventTestListener
{

    public $callStack = [];

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function listenerFunction()
    {
        $this->callStack[] = __FUNCTION__;
    }

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function secondListenerFunction()
    {
        $this->callStack[] = __FUNCTION__;
    }

    /**
     * Auxiliary function to help in stopPropagation testing
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function stopListener($event)
    {
        $event->stopPropagation();
    }
}

/**
 * Mock used for testing the subscriber objects
 */
class CustomTestEventListenerInterface extends EventTestListener implements EventListenerInterface
{

    public function implementedEvents()
    {
        return [
            'fake.event' => 'listenerFunction',
            'another.event' => ['callable' => 'secondListenerFunction'],
            'multiple.handlers' => [
                ['callable' => 'listenerFunction'],
                ['callable' => 'thirdListenerFunction']
            ]
        ];
    }

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function thirdListenerFunction()
    {
        $this->callStack[] = __FUNCTION__;
    }
}

/**
 * Tests the Cake\Event\EventManager class functionality
 *
 */
class EventManagerTest extends TestCase
{

    /**
     * Tests the attach() method for a single event key in multiple queues
     *
     * @return void
     */
    public function testAttachListeners()
    {
        $manager = new EventManager();
        $manager->attach('fakeFunction', 'fake.event');
        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('fake.event'));

        $manager->attach('fakeFunction2', 'fake.event');
        $expected[] = ['callable' => 'fakeFunction2'];
        $this->assertEquals($expected, $manager->listeners('fake.event'));

        $manager->attach('inQ5', 'fake.event', ['priority' => 5]);
        $manager->attach('inQ1', 'fake.event', ['priority' => 1]);
        $manager->attach('otherInQ5', 'fake.event', ['priority' => 5]);

        $expected = array_merge(
            [
                ['callable' => 'inQ1'],
                ['callable' => 'inQ5'],
                ['callable' => 'otherInQ5']
            ],
            $expected
        );
        $this->assertEquals($expected, $manager->listeners('fake.event'));
    }

    /**
     * Tests the attach() method for multiple event key in multiple queues
     *
     * @return void
     */
    public function testAttachMultipleEventKeys()
    {
        $manager = new EventManager();
        $manager->attach('fakeFunction', 'fake.event');
        $manager->attach('fakeFunction2', 'another.event');
        $manager->attach('fakeFunction3', 'another.event', ['priority' => 1]);
        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('fake.event'));

        $expected = [
            ['callable' => 'fakeFunction3'],
            ['callable' => 'fakeFunction2']
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
    }

    /**
     * Tests attached event listeners for matching key pattern
     *
     * @return void
     */
    public function testMatchingListeners()
    {
        $manager = new EventManager();
        $manager->attach('fakeFunction1', 'fake.event');
        $manager->attach('fakeFunction2', 'real.event');
        $manager->attach('fakeFunction3', 'test.event');
        $manager->attach('fakeFunction4', 'event.test');

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
     *
     * @return void
     */
    public function testOn()
    {
        $count = 1;
        $manager = new EventManager();
        $manager->on('my.event', 'myfunc');
        $expected = [
            ['callable' => 'myfunc']
        ];
        $this->assertSame($expected, $manager->listeners('my.event'));

        $manager->on('my.event', ['priority' => 1], 'func2');
        $expected = [
            ['callable' => 'func2'],
            ['callable' => 'myfunc'],
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
     *
     * @return void
     */
    public function testOff()
    {
        $manager = new EventManager();
        $manager->on('fake.event', ['AClass', 'aMethod']);
        $manager->on('another.event', ['AClass', 'anotherMethod']);
        $manager->on('another.event', ['priority' => 1], 'fakeFunction');

        $manager->off('fake.event', ['AClass', 'aMethod']);
        $this->assertEquals([], $manager->listeners('fake.event'));

        $manager->off('another.event', ['AClass', 'anotherMethod']);
        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));

        $manager->off('another.event', 'fakeFunction');
        $this->assertEquals([], $manager->listeners('another.event'));
    }

    /**
     * Tests off'ing an event from all event queues
     *
     * @return void
     */
    public function testOffFromAll()
    {
        $manager = new EventManager();
        $manager->on('fake.event', ['AClass', 'aMethod']);
        $manager->on('another.event', ['AClass', 'aMethod']);
        $manager->on('another.event', ['priority' => 1], 'fakeFunction');

        $manager->off(['AClass', 'aMethod']);
        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $this->assertEquals([], $manager->listeners('fake.event'));
    }

    /**
     * Tests off'ing all listeners for an event
     */
    public function testRemoveAllListeners()
    {
        $manager = new EventManager();
        $manager->on('fake.event', ['AClass', 'aMethod']);
        $manager->on('another.event', ['priority' => 1], 'fakeFunction');

        $manager->off('fake.event');

        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $this->assertEquals([], $manager->listeners('fake.event'));
    }

    /**
     * Tests detaching an event from a event key queue
     *
     * @return void
     */
    public function testDetach()
    {
        $manager = new EventManager();
        $manager->attach(['AClass', 'aMethod'], 'fake.event');
        $manager->attach(['AClass', 'anotherMethod'], 'another.event');
        $manager->attach('fakeFunction', 'another.event', ['priority' => 1]);

        $manager->detach(['AClass', 'aMethod'], 'fake.event');
        $this->assertEquals([], $manager->listeners('fake.event'));

        $manager->detach(['AClass', 'anotherMethod'], 'another.event');
        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));

        $manager->detach('fakeFunction', 'another.event');
        $this->assertEquals([], $manager->listeners('another.event'));
    }

    /**
     * Tests detaching an event from all event queues
     *
     * @return void
     */
    public function testDetachFromAll()
    {
        $manager = new EventManager();
        $manager->attach(['AClass', 'aMethod'], 'fake.event');
        $manager->attach(['AClass', 'aMethod'], 'another.event');
        $manager->attach('fakeFunction', 'another.event', ['priority' => 1]);

        $manager->detach(['AClass', 'aMethod']);
        $expected = [
            ['callable' => 'fakeFunction']
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $this->assertEquals([], $manager->listeners('fake.event'));
    }

    /**
     * Tests event dispatching
     *
     * @return void
     * @triggers fake.event
     */
    public function testDispatch()
    {
        $manager = new EventManager();
        $listener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
        $anotherListener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $manager->attach([$anotherListener, 'listenerFunction'], 'fake.event');
        $event = new Event('fake.event');

        $listener->expects($this->once())->method('listenerFunction')->with($event);
        $anotherListener->expects($this->once())->method('listenerFunction')->with($event);
        $manager->dispatch($event);
    }

    /**
     * Tests event dispatching using event key name
     *
     * @return void
     */
    public function testDispatchWithKeyName()
    {
        $manager = new EventManager();
        $listener = new EventTestListener;
        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $event = 'fake.event';
        $manager->dispatch($event);

        $expected = ['listenerFunction'];
        $this->assertEquals($expected, $listener->callStack);
    }

    /**
     * Tests event dispatching with a return value
     *
     * @return void
     * @triggers fake.event
     */
    public function testDispatchReturnValue()
    {
        $this->skipIf(
            version_compare(\PHPUnit_Runner_Version::id(), '3.7', '<'),
            'These tests fail in PHPUnit 3.6'
        );
        $manager = new EventManager;
        $listener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
        $anotherListener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $manager->attach([$anotherListener, 'listenerFunction'], 'fake.event');
        $event = new Event('fake.event');

        $listener->expects($this->at(0))->method('listenerFunction')
            ->with($event)
            ->will($this->returnValue('something special'));
        $anotherListener->expects($this->at(0))
            ->method('listenerFunction')
            ->with($event);
        $manager->dispatch($event);
        $this->assertEquals('something special', $event->result);
    }

    /**
     * Tests that returning false in a callback stops the event
     *
     * @return void
     * @triggers fake.event
     */
    public function testDispatchFalseStopsEvent()
    {
        $this->skipIf(
            version_compare(\PHPUnit_Runner_Version::id(), '3.7', '<'),
            'These tests fail in PHPUnit 3.6'
        );

        $manager = new EventManager();
        $listener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
        $anotherListener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $manager->attach([$anotherListener, 'listenerFunction'], 'fake.event');
        $event = new Event('fake.event');

        $listener->expects($this->at(0))->method('listenerFunction')
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
     * @return void
     * @triggers fake.event
     */
    public function testDispatchPrioritized()
    {
        $manager = new EventManager();
        $listener = new EventTestListener;
        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $manager->attach([$listener, 'secondListenerFunction'], 'fake.event', ['priority' => 5]);
        $event = new Event('fake.event');
        $manager->dispatch($event);

        $expected = ['secondListenerFunction', 'listenerFunction'];
        $this->assertEquals($expected, $listener->callStack);
    }

    /**
     * Tests subscribing a listener object and firing the events it subscribed to
     *
     * @return void
     * @triggers fake.event
     * @triggers another.event $this, array(some => data)
     */
    public function testAttachSubscriber()
    {
        $manager = new EventManager();
        $listener = $this->getMock(__NAMESPACE__ . '\CustomTestEventListenerInterface', ['secondListenerFunction']);
        $manager->attach($listener);

        $event = new Event('fake.event');
        $manager->dispatch($event);

        $expected = ['listenerFunction'];
        $this->assertEquals($expected, $listener->callStack);

        $event = new Event('another.event', $this, ['some' => 'data']);
        $listener->expects($this->at(0))
            ->method('secondListenerFunction')
            ->with($event, 'data');
        $manager->dispatch($event);
    }

    /**
     * Test implementedEvents binding multiple callbacks to the same event name.
     *
     * @return void
     * @triggers multiple.handlers
     */
    public function testAttachSubscriberMultiple()
    {
        $manager = new EventManager();
        $listener = $this->getMock(__NAMESPACE__ . '\CustomTestEventListenerInterface', ['listenerFunction', 'thirdListenerFunction']);
        $manager->attach($listener);
        $event = new Event('multiple.handlers');
        $listener->expects($this->once())
            ->method('listenerFunction')
            ->with($event);
        $listener->expects($this->once())
            ->method('thirdListenerFunction')
            ->with($event);
        $manager->dispatch($event);
    }

    /**
     * Tests subscribing a listener object and firing the events it subscribed to
     *
     * @return void
     */
    public function testDetachSubscriber()
    {
        $manager = new EventManager();
        $listener = $this->getMock(__NAMESPACE__ . '\CustomTestEventListenerInterface', ['secondListenerFunction']);
        $manager->attach($listener);
        $expected = [
            ['callable' => [$listener, 'secondListenerFunction']]
        ];
        $this->assertEquals($expected, $manager->listeners('another.event'));
        $expected = [
            ['callable' => [$listener, 'listenerFunction']]
        ];
        $this->assertEquals($expected, $manager->listeners('fake.event'));
        $manager->detach($listener);
        $this->assertEquals([], $manager->listeners('fake.event'));
        $this->assertEquals([], $manager->listeners('another.event'));
    }

    /**
     * Tests that it is possible to get/set the manager singleton
     *
     * @return void
     */
    public function testGlobalDispatcherGetter()
    {
        $this->assertInstanceOf('Cake\Event\EventManager', EventManager::instance());
        $manager = new EventManager();

        EventManager::instance($manager);
        $this->assertSame($manager, EventManager::instance());
    }

    /**
     * Tests that the global event manager gets the event too from any other manager
     *
     * @return void
     * @triggers fake.event
     */
    public function testDispatchWithGlobal()
    {
        $generalManager = $this->getMock('Cake\Event\EventManager', ['prioritisedListeners']);
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
     * @return void
     * @triggers fake.event
     */
    public function testStopPropagation()
    {
        $generalManager = $this->getMock('Cake\Event\EventManager');
        $manager = new EventManager();
        $listener = new EventTestListener();

        EventManager::instance($generalManager);
        $generalManager->expects($this->any())
                ->method('prioritisedListeners')
                ->with('fake.event')
                ->will($this->returnValue([]));

        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $manager->attach([$listener, 'stopListener'], 'fake.event', ['priority' => 8]);
        $manager->attach([$listener, 'secondListenerFunction'], 'fake.event', ['priority' => 5]);
        $event = new Event('fake.event');
        $manager->dispatch($event);

        $expected = ['secondListenerFunction'];
        $this->assertEquals($expected, $listener->callStack);
        EventManager::instance(new EventManager());
    }

    /**
     * Tests event dispatching using priorities
     *
     * @return void
     * @triggers fake.event
     */
    public function testDispatchPrioritizedWithGlobal()
    {
        $generalManager = $this->getMock('Cake\Event\EventManager');
        $manager = new EventManager();
        $listener = new CustomTestEventListenerInterface();
        $event = new Event('fake.event');

        EventManager::instance($generalManager);
        $generalManager->expects($this->any())
                ->method('prioritisedListeners')
                ->with('fake.event')
                ->will($this->returnValue(
                    [11 => [
                        ['callable' => [$listener, 'secondListenerFunction']]
                    ]]
                ));

        $manager->attach([$listener, 'listenerFunction'], 'fake.event');
        $manager->attach([$listener, 'thirdListenerFunction'], 'fake.event', ['priority' => 15]);

        $manager->dispatch($event);

        $expected = ['listenerFunction', 'secondListenerFunction', 'thirdListenerFunction'];
        $this->assertEquals($expected, $listener->callStack);
        EventManager::instance(new EventManager());
    }

    /**
     * Tests event dispatching using priorities
     *
     * @return void
     * @triggers fake.event
     */
    public function testDispatchGlobalBeforeLocal()
    {
        $generalManager = $this->getMock('Cake\Event\EventManager');
        $manager = new EventManager();
        $listener = new CustomTestEventListenerInterface();
        $event = new Event('fake.event');

        EventManager::instance($generalManager);
        $generalManager->expects($this->any())
                ->method('prioritisedListeners')
                ->with('fake.event')
                ->will($this->returnValue(
                    [10 => [
                        ['callable' => [$listener, 'listenerFunction']]
                    ]]
                ));

        $manager->attach([$listener, 'secondListenerFunction'], 'fake.event');

        $manager->dispatch($event);

        $expected = ['listenerFunction', 'secondListenerFunction'];
        $this->assertEquals($expected, $listener->callStack);
        EventManager::instance(new EventManager());
    }

    /**
     * test callback
     */
    public function onMyEvent($event)
    {
        $event->data['callback'] = 'ok';
    }

    /**
     * Tests events dispatched by a local manager can be handled by
     * handler registered in the global event manager
     * @triggers my_event $manager
     */
    public function testDispatchLocalHandledByGlobal()
    {
        $callback = [$this, 'onMyEvent'];
        EventManager::instance()->attach($callback, 'my_event');
        $manager = new EventManager();
        $event = new Event('my_event', $manager);
        $manager->dispatch($event);
        $this->assertEquals('ok', $event->data['callback']);
    }

    /**
     * Test that events are dispatched properly when there are global and local
     * listeners at the same priority.
     *
     * @return void
     * @triggers fake.event $this)
     */
    public function testDispatchWithGlobalAndLocalEvents()
    {
        $listener = new CustomTestEventListenerInterface();
        EventManager::instance()->attach($listener);
        $listener2 = new EventTestListener();
        $manager = new EventManager();
        $manager->attach([$listener2, 'listenerFunction'], 'fake.event');

        $manager->dispatch(new Event('fake.event', $this));
        $this->assertEquals(['listenerFunction'], $listener->callStack);
        $this->assertEquals(['listenerFunction'], $listener2->callStack);
    }

    /**
     * Test getting a list of dispatched events from the manager.
     *
     * @return void
     * @triggers my_event $this)
     * @triggers my_second_event $this)
     */
    public function testGetDispatchedEvents()
    {
        $event = new Event('my_event', $this);
        $event2 = new Event('my_second_event', $this);

        $manager = new EventManager();
        $manager->dispatch($event);
        $manager->dispatch($event2);

        $result = $manager->getDispatchedEvents();
        $this->assertCount(2, $result);
        $this->assertEquals($result[0], $event);
        $this->assertEquals($result[1], $event2);
    }
}
