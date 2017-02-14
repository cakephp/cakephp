<?php
namespace Cake\Test\TestCase\TestSuite\Constraint;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\Constraint\EventFiredWith;
use Cake\TestSuite\TestCase;

/**
 * EventFiredWith Test
 */
class EventFiredWithTest extends TestCase
{

    /**
     * tests EventFiredWith constraint
     *
     * @return void
     */
    public function testMatches()
    {
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $myEvent = new Event('my.event', $this, [
            'key' => 'value'
        ]);
        $myOtherEvent = new Event('my.other.event', $this, [
            'key' => null
        ]);

        $manager->getEventList()->add($myEvent);
        $manager->getEventList()->add($myOtherEvent);

        $constraint = new EventFiredWith($manager, 'key', 'value');

        $this->assertTrue($constraint->matches('my.event'));
        $this->assertFalse($constraint->matches('my.other.event'));
        $this->assertFalse($constraint->matches('event.not.fired'));

        $constraint = new EventFiredWith($manager, 'key', null);

        $this->assertTrue($constraint->matches('my.other.event'));
        $this->assertFalse($constraint->matches('my.event'));
    }

    /**
     * tests trying to assert data key=>value when an event is fired multiple times
     *
     * @return void
     * @expectedException \PHPUnit\Framework\AssertionFailedError
     */
    public function testMatchesInvalid()
    {
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $myEvent = new Event('my.event', $this, [
            'key' => 'value'
        ]);

        $manager->getEventList()->add($myEvent);
        $manager->getEventList()->add($myEvent);

        $constraint = new EventFiredWith($manager, 'key', 'value');

        $constraint->matches('my.event');
    }
}
