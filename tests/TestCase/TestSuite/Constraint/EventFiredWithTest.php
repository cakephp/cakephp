<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\TestSuite\Constraint;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\Constraint\EventFiredWith;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use stdClass;

/**
 * EventFiredWith Test
 */
class EventFiredWithTest extends TestCase
{
    /**
     * tests EventFiredWith constraint
     */
    public function testMatches(): void
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
     */
    public function testMatchesInvalid(): void
    {
        $this->expectException(AssertionFailedError::class);
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

    /**
     * tests assertions on events with non-scalar data
     */
    public function testMatchesArrayData(): void
    {
        $manager = EventManager::instance();
        $manager->setEventList(new EventList());
        $manager->trackEvents(true);

        $myEvent = new Event('my.event', $this, [
            'data' => ['one' => 1],
        ]);

        $manager->getEventList()->add($myEvent);

        $constraint = new EventFiredWith($manager, 'data', ['one' => 1]);
        $constraint->matches('my.event');
        $this->assertEquals('was fired with `data` matching `{"one":1}`', $constraint->toString());
    }
}
