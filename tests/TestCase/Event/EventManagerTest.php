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
use Cake\Event\EventListener;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * Mock class used to test event dispatching
 */
class EventTestListener {

	public $callStack = array();

/**
 * Test function to be used in event dispatching
 *
 * @return void
 */
	public function listenerFunction() {
		$this->callStack[] = __FUNCTION__;
	}

/**
 * Test function to be used in event dispatching
 *
 * @return void
 */
	public function secondListenerFunction() {
		$this->callStack[] = __FUNCTION__;
	}

/**
 * Auxiliary function to help in stopPropagation testing
 *
 * @param \Cake\Event\Event $event
 * @return void
 */
	public function stopListener($event) {
		$event->stopPropagation();
	}

}

/**
 * Mock used for testing the subscriber objects
 */
class CustomTestEventListener extends EventTestListener implements EventListener {

	public function implementedEvents() {
		return array(
			'fake.event' => 'listenerFunction',
			'another.event' => array('callable' => 'secondListenerFunction'),
			'multiple.handlers' => array(
				array('callable' => 'listenerFunction'),
				array('callable' => 'thirdListenerFunction')
			)
		);
	}

/**
 * Test function to be used in event dispatching
 *
 * @return void
 */
	public function thirdListenerFunction() {
		$this->callStack[] = __FUNCTION__;
	}

}

/**
 * Tests the Cake\Event\EventManager class functionality
 *
 */
class EventManagerTest extends TestCase {

/**
 * Tests the attach() method for a single event key in multiple queues
 *
 * @return void
 */
	public function testAttachListeners() {
		$manager = new EventManager();
		$manager->attach('fakeFunction', 'fake.event');
		$expected = array(
			array('callable' => 'fakeFunction')
		);
		$this->assertEquals($expected, $manager->listeners('fake.event'));

		$manager->attach('fakeFunction2', 'fake.event');
		$expected[] = array('callable' => 'fakeFunction2');
		$this->assertEquals($expected, $manager->listeners('fake.event'));

		$manager->attach('inQ5', 'fake.event', array('priority' => 5));
		$manager->attach('inQ1', 'fake.event', array('priority' => 1));
		$manager->attach('otherInQ5', 'fake.event', array('priority' => 5));

		$expected = array_merge(
			array(
				array('callable' => 'inQ1'),
				array('callable' => 'inQ5'),
				array('callable' => 'otherInQ5')
			),
			$expected
		);
		$this->assertEquals($expected, $manager->listeners('fake.event'));
	}

/**
 * Tests the attach() method for multiple event key in multiple queues
 *
 * @return void
 */
	public function testAttachMultipleEventKeys() {
		$manager = new EventManager();
		$manager->attach('fakeFunction', 'fake.event');
		$manager->attach('fakeFunction2', 'another.event');
		$manager->attach('fakeFunction3', 'another.event', array('priority' => 1));
		$expected = array(
			array('callable' => 'fakeFunction')
		);
		$this->assertEquals($expected, $manager->listeners('fake.event'));

		$expected = array(
			array('callable' => 'fakeFunction3'),
			array('callable' => 'fakeFunction2')
		);
		$this->assertEquals($expected, $manager->listeners('another.event'));
	}

/**
 * Tests detaching an event from a event key queue
 *
 * @return void
 */
	public function testDetach() {
		$manager = new EventManager();
		$manager->attach(array('AClass', 'aMethod'), 'fake.event');
		$manager->attach(array('AClass', 'anotherMethod'), 'another.event');
		$manager->attach('fakeFunction', 'another.event', array('priority' => 1));

		$manager->detach(array('AClass', 'aMethod'), 'fake.event');
		$this->assertEquals(array(), $manager->listeners('fake.event'));

		$manager->detach(array('AClass', 'anotherMethod'), 'another.event');
		$expected = array(
			array('callable' => 'fakeFunction')
		);
		$this->assertEquals($expected, $manager->listeners('another.event'));

		$manager->detach('fakeFunction', 'another.event');
		$this->assertEquals(array(), $manager->listeners('another.event'));
	}

/**
 * Tests detaching an event from all event queues
 *
 * @return void
 */
	public function testDetachFromAll() {
		$manager = new EventManager();
		$manager->attach(array('AClass', 'aMethod'), 'fake.event');
		$manager->attach(array('AClass', 'aMethod'), 'another.event');
		$manager->attach('fakeFunction', 'another.event', array('priority' => 1));

		$manager->detach(array('AClass', 'aMethod'));
		$expected = array(
			array('callable' => 'fakeFunction')
		);
		$this->assertEquals($expected, $manager->listeners('another.event'));
		$this->assertEquals(array(), $manager->listeners('fake.event'));
	}

/**
 * Tests event dispatching
 *
 * @return void
 */
	public function testDispatch() {
		$manager = new EventManager();
		$listener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
		$anotherListener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'listenerFunction'), 'fake.event');
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
	public function testDispatchWithKeyName() {
		$manager = new EventManager();
		$listener = new EventTestListener;
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$event = 'fake.event';
		$manager->dispatch($event);

		$expected = array('listenerFunction');
		$this->assertEquals($expected, $listener->callStack);
	}

/**
 * Tests event dispatching with a return value
 *
 * @return void
 */
	public function testDispatchReturnValue() {
		$this->skipIf(
			version_compare(\PHPUnit_Runner_Version::id(), '3.7', '<'),
			'These tests fail in PHPUnit 3.6'
		);
		$manager = new EventManager;
		$listener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
		$anotherListener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'listenerFunction'), 'fake.event');
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
 */
	public function testDispatchFalseStopsEvent() {
		$this->skipIf(
			version_compare(\PHPUnit_Runner_Version::id(), '3.7', '<'),
			'These tests fail in PHPUnit 3.6'
		);

		$manager = new EventManager();
		$listener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
		$anotherListener = $this->getMock(__NAMESPACE__ . '\EventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'listenerFunction'), 'fake.event');
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
 */
	public function testDispatchPrioritized() {
		$manager = new EventManager();
		$listener = new EventTestListener;
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($listener, 'secondListenerFunction'), 'fake.event', array('priority' => 5));
		$event = new Event('fake.event');
		$manager->dispatch($event);

		$expected = array('secondListenerFunction', 'listenerFunction');
		$this->assertEquals($expected, $listener->callStack);
	}

/**
 * Tests subscribing a listener object and firing the events it subscribed to
 *
 * @return void
 */
	public function testAttachSubscriber() {
		$manager = new EventManager();
		$listener = $this->getMock(__NAMESPACE__ . '\CustomTestEventListener', array('secondListenerFunction'));
		$manager->attach($listener);

		$event = new Event('fake.event');
		$manager->dispatch($event);

		$expected = array('listenerFunction');
		$this->assertEquals($expected, $listener->callStack);

		$event = new Event('another.event', $this, array('some' => 'data'));
		$listener->expects($this->at(0))
			->method('secondListenerFunction')
			->with($event, 'data');
		$manager->dispatch($event);
	}

/**
 * Test implementedEvents binding multiple callbacks to the same event name.
 *
 * @return void
 */
	public function testAttachSubscriberMultiple() {
		$manager = new EventManager();
		$listener = $this->getMock(__NAMESPACE__ . '\CustomTestEventListener', array('listenerFunction', 'thirdListenerFunction'));
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
	public function testDetachSubscriber() {
		$manager = new EventManager();
		$listener = $this->getMock(__NAMESPACE__ . '\CustomTestEventListener', array('secondListenerFunction'));
		$manager->attach($listener);
		$expected = array(
			array('callable' => array($listener, 'secondListenerFunction'))
		);
		$this->assertEquals($expected, $manager->listeners('another.event'));
		$expected = array(
			array('callable' => array($listener, 'listenerFunction'))
		);
		$this->assertEquals($expected, $manager->listeners('fake.event'));
		$manager->detach($listener);
		$this->assertEquals(array(), $manager->listeners('fake.event'));
		$this->assertEquals(array(), $manager->listeners('another.event'));
	}

/**
 * Tests that it is possible to get/set the manager singleton
 *
 * @return void
 */
	public function testGlobalDispatcherGetter() {
		$this->assertInstanceOf('Cake\Event\EventManager', EventManager::instance());
		$manager = new EventManager();

		EventManager::instance($manager);
		$this->assertSame($manager, EventManager::instance());
	}

/**
 * Tests that the global event manager gets the event too from any other manager
 *
 * @return void
 */
	public function testDispatchWithGlobal() {
		$generalManager = $this->getMock('Cake\Event\EventManager', array('prioritisedListeners'));
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
 */
	public function testStopPropagation() {
		$generalManager = $this->getMock('Cake\Event\EventManager');
		$manager = new EventManager();
		$listener = new EventTestListener();

		EventManager::instance($generalManager);
		$generalManager->expects($this->any())
				->method('prioritisedListeners')
				->with('fake.event')
				->will($this->returnValue(array()));

		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($listener, 'stopListener'), 'fake.event', array('priority' => 8));
		$manager->attach(array($listener, 'secondListenerFunction'), 'fake.event', array('priority' => 5));
		$event = new Event('fake.event');
		$manager->dispatch($event);

		$expected = array('secondListenerFunction');
		$this->assertEquals($expected, $listener->callStack);
		EventManager::instance(new EventManager());
	}

/**
 * Tests event dispatching using priorities
 *
 * @return void
 */
	public function testDispatchPrioritizedWithGlobal() {
		$generalManager = $this->getMock('Cake\Event\EventManager');
		$manager = new EventManager();
		$listener = new CustomTestEventListener();
		$event = new Event('fake.event');

		EventManager::instance($generalManager);
		$generalManager->expects($this->any())
				->method('prioritisedListeners')
				->with('fake.event')
				->will($this->returnValue(
					array(11 => array(
						array('callable' => array($listener, 'secondListenerFunction'))
					))
				));

		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($listener, 'thirdListenerFunction'), 'fake.event', array('priority' => 15));

		$manager->dispatch($event);

		$expected = array('listenerFunction', 'secondListenerFunction', 'thirdListenerFunction');
		$this->assertEquals($expected, $listener->callStack);
		EventManager::instance(new EventManager());
	}

/**
 * Tests event dispatching using priorities
 *
 * @return void
 */
	public function testDispatchGlobalBeforeLocal() {
		$generalManager = $this->getMock('Cake\Event\EventManager');
		$manager = new EventManager();
		$listener = new CustomTestEventListener();
		$event = new Event('fake.event');

		EventManager::instance($generalManager);
		$generalManager->expects($this->any())
				->method('prioritisedListeners')
				->with('fake.event')
				->will($this->returnValue(
					array(10 => array(
						array('callable' => array($listener, 'listenerFunction'))
					))
				));

		$manager->attach(array($listener, 'secondListenerFunction'), 'fake.event');

		$manager->dispatch($event);

		$expected = array('listenerFunction', 'secondListenerFunction');
		$this->assertEquals($expected, $listener->callStack);
		EventManager::instance(new EventManager());
	}

/**
 * test callback
 */
	public function onMyEvent($event) {
		$event->data['callback'] = 'ok';
	}

/**
 * Tests events dispatched by a local manager can be handled by
 * handler registered in the global event manager
 */
	public function testDispatchLocalHandledByGlobal() {
		$callback = array($this, 'onMyEvent');
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
 */
	public function testDispatchWithGlobalAndLocalEvents() {
		$listener = new CustomTestEventListener();
		EventManager::instance()->attach($listener);
		$listener2 = new EventTestListener();
		$manager = new EventManager();
		$manager->attach(array($listener2, 'listenerFunction'), 'fake.event');

		$manager->dispatch(new Event('fake.event', $this));
		$this->assertEquals(array('listenerFunction'), $listener->callStack);
		$this->assertEquals(array('listenerFunction'), $listener2->callStack);
	}

}
