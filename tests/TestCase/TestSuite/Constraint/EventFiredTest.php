<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\TestSuite\Constraint;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\Constraint\EventFired;
use Cake\TestSuite\TestCase;

/**
 * EventFired Test
 */
class EventFiredTest extends TestCase
{
    /**
     * tests EventFired constraint
     */
    public function testMatches(): void
    {
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $myEvent = new Event('my.event', $this, []);
        $myOtherEvent = new Event('my.other.event', $this, []);

        $manager->getEventList()->add($myEvent);
        $manager->getEventList()->add($myOtherEvent);

        $constraint = new EventFired($manager);

        $this->assertTrue($constraint->matches('my.event'));
        $this->assertTrue($constraint->matches('my.other.event'));
        $this->assertFalse($constraint->matches('event.not.fired'));
    }
}
