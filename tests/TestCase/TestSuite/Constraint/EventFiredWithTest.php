<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\TestSuite\Constraint;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\Constraint\EventFiredWith;
use Cake\TestSuite\TestCase;
use stdClass;

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
            'key' => 'value',
        ]);
        $myOtherEvent = new Event('my.other.event', $this, [
            'key' => null,
        ]);

        $obj = new stdClass();
        $myEventWithObject = new Event('my.obj.event', $this, [
            'key' => $obj,
        ]);

        $manager->getEventList()->add($myEvent);
        $manager->getEventList()->add($myOtherEvent);
        $manager->getEventList()->add($myEventWithObject);

        $constraint = new EventFiredWith($manager, 'key', 'value');

        $this->assertTrue($constraint->matches('my.event'));
        $this->assertFalse($constraint->matches('my.other.event'));
        $this->assertFalse($constraint->matches('event.not.fired'));

        $constraint = new EventFiredWith($manager, 'key', null);

        $this->assertTrue($constraint->matches('my.other.event'));
        $this->assertFalse($constraint->matches('my.event'));

        $constraint = new EventFiredWith($manager, 'key', $obj);

        $this->assertTrue($constraint->matches('my.obj.event'));
    }

    /**
     * tests trying to assert data key=>value when an event is fired multiple times
     *
     * @return void
     */
    public function testMatchesInvalid()
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $myEvent = new Event('my.event', $this, [
            'key' => 'value',
        ]);

        $manager->getEventList()->add($myEvent);
        $manager->getEventList()->add($myEvent);

        $constraint = new EventFiredWith($manager, 'key', 'value');

        $constraint->matches('my.event');
    }
}
