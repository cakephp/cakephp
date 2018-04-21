<?php
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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\TestSuite\TestCase;

/**
 * Tests the Cake\Event\EvenList class functionality
 */
class EvenListTest extends TestCase
{

    /**
     * testAddEventAndFlush
     *
     * @return void
     */
    public function testAddEventAndFlush()
    {
        $eventList = new EventList();
        $event = new Event('my_event', $this);
        $event2 = new Event('my_second_event', $this);

        $eventList->add($event);
        $eventList->add($event2);
        $this->assertCount(2, $eventList);

        $this->assertEquals($eventList[0], $event);
        $this->assertEquals($eventList[1], $event2);

        $eventList->flush();

        $this->assertCount(0, $eventList);
    }

    /**
     * Testing implemented \ArrayAccess and \Count methods
     *
     * @return void
     */
    public function testArrayAccess()
    {
        $eventList = new EventList();
        $event = new Event('my_event', $this);
        $event2 = new Event('my_second_event', $this);

        $eventList->add($event);
        $eventList->add($event2);
        $this->assertCount(2, $eventList);

        $this->assertTrue($eventList->hasEvent('my_event'));
        $this->assertFalse($eventList->hasEvent('does-not-exist'));

        $this->assertEquals($eventList->offsetGet(0), $event);
        $this->assertEquals($eventList->offsetGet(1), $event2);
        $this->assertTrue($eventList->offsetExists(0));
        $this->assertTrue($eventList->offsetExists(1));
        $this->assertFalse($eventList->offsetExists(2));

        $eventList->offsetUnset(1);
        $this->assertCount(1, $eventList);

        $eventList->flush();

        $this->assertCount(0, $eventList);
    }
}
