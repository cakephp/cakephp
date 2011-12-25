<?php
/**
 * ControllerTestCaseTest file
 *
 * Test Case for ControllerTestCase class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Event
 * @since         CakePHP v 2.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeEvent', 'Event');

/**
 * Tests the CakeEvent class functionality
 *
 */
class CakeEventTest extends CakeTestCase {

/**
 * Tests the name() method
 *
 * @return void
 */
	public function testName() {
		$event = new CakeEvent('fake.event');
		$this->assertEquals('fake.event', $event->name());
	}

/**
 * Tests the subject() method
 *
 * @return void
 */
	public function testSubject() {
		$event = new CakeEvent('fake.event', $this);
		$this->assertSame($this, $event->subject());

		$event = new CakeEvent('fake.event');
		$this->assertNull($event->subject());
	}

/**
 * Tests the event propagation stopping property
 *
 * @return void
 */
	public function testPropagation() {
		$event = new CakeEvent('fake.event');
		$this->assertFalse($event->isStopped());
		$event->stopPropagation();
		$this->assertTrue($event->isStopped());
	}

/**
 * Tests that it is possible to get/set custom data in a event
 *
 * @return void
 */
	public function testEventData() {
		$event = new CakeEvent('fake.event', $this, array('some' => 'data'));
		$this->assertEquals(array('some' => 'data'), $event->data);
	}

/**
 * Tests that it is possible to get the name and subject directly
 *
 * @return void
 */
	public function testEventDirectPropertyAccess() {
		$event = new CakeEvent('fake.event', $this);
		$this->assertEquals($this, $event->subject);
		$this->assertEquals('fake.event', $event->name);
	}
}