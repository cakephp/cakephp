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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Event;

use Cake\Event\Event;
use Cake\Event\EventStack;
use Cake\TestSuite\TestCase;

/**
 * Tests the Cake\Event\EvenStack class functionality
 */
class EvenStackTest extends TestCase
{

    /**
     * testAddEventAndFlush
     *
     * @return void
     */
    public function testAddEventAndFlush()
    {
        $eventStack = new EventStack();
        $event = new Event('my_event', $this);
        $event2 = new Event('my_second_event', $this);

        $eventStack->add($event);
        $eventStack->add($event2);
        $this->assertCount(2, $eventStack);

        $this->assertEquals($eventStack[0], $event);
        $this->assertEquals($eventStack[1], $event2);

        $eventStack->flush();

        $this->assertCount(0, $eventStack);
    }

    /**
     * Testing implemented \ArrayAccess and \Count methods
     *
     * @return void
     */
    public function testArrayAccess()
    {
        $eventStack = new EventStack();
        $event = new Event('my_event', $this);
        $event2 = new Event('my_second_event', $this);

        $eventStack->add($event);
        $eventStack->add($event2);
        $this->assertCount(2, $eventStack);

        $this->assertTrue($eventStack->hasEvent('my_event'));
        $this->assertFalse($eventStack->hasEvent('does-not-exist'));

        $this->assertEquals($eventStack->offsetGet(0), $event);
        $this->assertEquals($eventStack->offsetGet(1), $event2);
        $this->assertTrue($eventStack->offsetExists(0));
        $this->assertTrue($eventStack->offsetExists(1));
        $this->assertFalse($eventStack->offsetExists(2));

        $eventStack->offsetUnset(1);
        $this->assertCount(1, $eventStack);

        $eventStack->flush();

        $this->assertCount(0, $eventStack);
    }
}
