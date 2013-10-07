<?php
/**
 * ControllerTestCaseTest file
 *
 * Test Case for ControllerTestCase class
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP Project
 * @package		  Cake.Test.Case.Event
 * @since		  CakePHP v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeEvent', 'Event');
App::uses('CakeEventManager', 'Event');
App::uses('CakeEventListener', 'Event');

/**
 * Mock class used to test event dispatching
 *
 * @package Cake.Test.Case.Event
 */
class CakeEventTestListener {

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
 * @param CakeEvent $event
 * @return void
 */
	public function stopListener($event) {
		$event->stopPropagation();
	}

}

/**
 * Mock used for testing the subscriber objects
 *
 * @package Cake.Test.Case.Event
 */
class CustomTestEventListener extends CakeEventTestListener implements CakeEventListener {

	public function implementedEvents() {
		return array(
			'fake.event' => 'listenerFunction',
			'another.event' => array('callable' => 'secondListenerFunction', 'passParams' => true),
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
 * Tests the CakeEventManager class functionality
 *
 */
class CakeEventManagerTest extends CakeTestCase {

/**
 * Tests the attach() method for a single event key in multiple queues
 *
 * @return void
 */
	public function testAttachListeners() {
		$manager = new CakeEventManager;
		$manager->attach('fakeFunction', 'fake.event');
		$expected = array(
			array('callable' => 'fakeFunction', 'passParams' => false)
		);
		$this->assertEquals($expected, $manager->listeners('fake.event'));

		$manager->attach('fakeFunction2', 'fake.event');
		$expected[] = array('callable' => 'fakeFunction2', 'passParams' => false);
		$this->assertEquals($expected, $manager->listeners('fake.event'));

		$manager->attach('inQ5', 'fake.event', array('priority' => 5));
		$manager->attach('inQ1', 'fake.event', array('priority' => 1));
		$manager->attach('otherInQ5', 'fake.event', array('priority' => 5));

		$expected = array_merge(
			array(
				array('callable' => 'inQ1', 'passParams' => false),
				array('callable' => 'inQ5', 'passParams' => false),
				array('callable' => 'otherInQ5', 'passParams' => false)
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
		$manager = new CakeEventManager;
		$manager->attach('fakeFunction', 'fake.event');
		$manager->attach('fakeFunction2', 'another.event');
		$manager->attach('fakeFunction3', 'another.event', array('priority' => 1, 'passParams' => true));
		$expected = array(
			array('callable' => 'fakeFunction', 'passParams' => false)
		);
		$this->assertEquals($expected, $manager->listeners('fake.event'));

		$expected = array(
			array('callable' => 'fakeFunction3', 'passParams' => true),
			array('callable' => 'fakeFunction2', 'passParams' => false)
		);
		$this->assertEquals($expected, $manager->listeners('another.event'));
	}

/**
 * Tests detaching an event from a event key queue
 *
 * @return void
 */
	public function testDetach() {
		$manager = new CakeEventManager;
		$manager->attach(array('AClass', 'aMethod'), 'fake.event');
		$manager->attach(array('AClass', 'anotherMethod'), 'another.event');
		$manager->attach('fakeFunction', 'another.event', array('priority' => 1));

		$manager->detach(array('AClass', 'aMethod'), 'fake.event');
		$this->assertEquals(array(), $manager->listeners('fake.event'));

		$manager->detach(array('AClass', 'anotherMethod'), 'another.event');
		$expected = array(
			array('callable' => 'fakeFunction', 'passParams' => false)
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
		$manager = new CakeEventManager;
		$manager->attach(array('AClass', 'aMethod'), 'fake.event');
		$manager->attach(array('AClass', 'aMethod'), 'another.event');
		$manager->attach('fakeFunction', 'another.event', array('priority' => 1));

		$manager->detach(array('AClass', 'aMethod'));
		$expected = array(
			array('callable' => 'fakeFunction', 'passParams' => false)
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
		$manager = new CakeEventManager;
		$listener = $this->getMock('CakeEventTestListener');
		$anotherListener = $this->getMock('CakeEventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'listenerFunction'), 'fake.event');
		$event = new CakeEvent('fake.event');

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
		$manager = new CakeEventManager;
		$listener = new CakeEventTestListener;
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
			version_compare(PHPUnit_Runner_Version::id(), '3.7', '<'),
			'These tests fail in PHPUnit 3.6'
		);
		$manager = new CakeEventManager;
		$listener = $this->getMock('CakeEventTestListener');
		$anotherListener = $this->getMock('CakeEventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'listenerFunction'), 'fake.event');
		$event = new CakeEvent('fake.event');

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
			version_compare(PHPUnit_Runner_Version::id(), '3.7', '<'),
			'These tests fail in PHPUnit 3.6'
		);

		$manager = new CakeEventManager;
		$listener = $this->getMock('CakeEventTestListener');
		$anotherListener = $this->getMock('CakeEventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'listenerFunction'), 'fake.event');
		$event = new CakeEvent('fake.event');

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
		$manager = new CakeEventManager;
		$listener = new CakeEventTestListener;
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($listener, 'secondListenerFunction'), 'fake.event', array('priority' => 5));
		$event = new CakeEvent('fake.event');
		$manager->dispatch($event);

		$expected = array('secondListenerFunction', 'listenerFunction');
		$this->assertEquals($expected, $listener->callStack);
	}

/**
 * Tests event dispatching with passed params
 *
 * @return void
 */
	public function testDispatchPassingParams() {
		$manager = new CakeEventManager;
		$listener = $this->getMock('CakeEventTestListener');
		$anotherListener = $this->getMock('CakeEventTestListener');
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($anotherListener, 'secondListenerFunction'), 'fake.event', array('passParams' => true));
		$event = new CakeEvent('fake.event', $this, array('some' => 'data'));

		$listener->expects($this->once())->method('listenerFunction')->with($event);
		$anotherListener->expects($this->once())->method('secondListenerFunction')->with('data');
		$manager->dispatch($event);
	}

/**
 * Tests subscribing a listener object and firing the events it subscribed to
 *
 * @return void
 */
	public function testAttachSubscriber() {
		$manager = new CakeEventManager;
		$listener = $this->getMock('CustomTestEventListener', array('secondListenerFunction'));
		$manager->attach($listener);
		$event = new CakeEvent('fake.event');

		$manager->dispatch($event);

		$expected = array('listenerFunction');
		$this->assertEquals($expected, $listener->callStack);

		$listener->expects($this->at(0))->method('secondListenerFunction')->with('data');
		$event = new CakeEvent('another.event', $this, array('some' => 'data'));
		$manager->dispatch($event);

		$manager = new CakeEventManager;
		$listener = $this->getMock('CustomTestEventListener', array('listenerFunction', 'thirdListenerFunction'));
		$manager->attach($listener);
		$event = new CakeEvent('multiple.handlers');
		$listener->expects($this->once())->method('listenerFunction')->with($event);
		$listener->expects($this->once())->method('thirdListenerFunction')->with($event);
		$manager->dispatch($event);
	}

/**
 * Tests subscribing a listener object and firing the events it subscribed to
 *
 * @return void
 */
	public function testDetachSubscriber() {
		$manager = new CakeEventManager;
		$listener = $this->getMock('CustomTestEventListener', array('secondListenerFunction'));
		$manager->attach($listener);
		$expected = array(
			array('callable' => array($listener, 'secondListenerFunction'), 'passParams' => true)
		);
		$this->assertEquals($expected, $manager->listeners('another.event'));
		$expected = array(
			array('callable' => array($listener, 'listenerFunction'), 'passParams' => false)
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
		$this->assertInstanceOf('CakeEventManager', CakeEventManager::instance());
		$manager = new CakeEventManager;

		CakeEventManager::instance($manager);
		$this->assertSame($manager, CakeEventManager::instance());
	}

/**
 * Tests that the global event manager gets the event too from any other manager
 *
 * @return void
 */
	public function testDispatchWithGlobal() {
		$generalManager = $this->getMock('CakeEventManager', array('dispatch'));
		$manager = new CakeEventManager;
		$event = new CakeEvent('fake.event');
		CakeEventManager::instance($generalManager);

		$generalManager->expects($this->once())->method('dispatch')->with($event);
		$manager->dispatch($event);
	}

/**
 * Tests that stopping an event will not notify the rest of the listeners
 *
 * @return void
 */
	public function testStopPropagation() {
		$manager = new CakeEventManager;
		$listener = new CakeEventTestListener;
		$manager->attach(array($listener, 'listenerFunction'), 'fake.event');
		$manager->attach(array($listener, 'stopListener'), 'fake.event', array('priority' => 8));
		$manager->attach(array($listener, 'secondListenerFunction'), 'fake.event', array('priority' => 5));
		$event = new CakeEvent('fake.event');
		$manager->dispatch($event);

		$expected = array('secondListenerFunction');
		$this->assertEquals($expected, $listener->callStack);
	}
}
