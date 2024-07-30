<?php
declare(strict_types=1);

namespace Cake\TestSuite\Constraint;

use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * EventFiredWith constraint
 *
 * @internal
 */
class EventFiredWith extends Constraint
{
    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager $_eventManager Event manager to check
     * @param string $_dataKey Data key
     * @param mixed $_dataValue Data value
     */
    public function __construct(/**
     * Array of fired events
     */
    protected EventManager $_eventManager, /**
     * Event data key
     */
    protected string $_dataKey, /**
     * Event data value
     */
    protected mixed $_dataValue)
    {
        if (!$this->_eventManager->getEventList() instanceof \Cake\Event\EventList) {
            throw new AssertionFailedError(
                'The event manager you are asserting against is not configured to track events.'
            );
        }
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    protected function matches(mixed $other): bool
    {
        $firedEvents = [];
        $list = $this->_eventManager->getEventList();
        if ($list instanceof \Cake\Event\EventList) {
            $totalEvents = count($list);
            for ($e = 0; $e < $totalEvents; $e++) {
                $firedEvents[] = $list[$e];
            }
        }

        $eventGroup = (new Collection($firedEvents))
            ->groupBy(fn(EventInterface $event): string => $event->getName())
            ->toArray();

        if (!array_key_exists($other, $eventGroup)) {
            return false;
        }

        /** @var array<\Cake\Event\EventInterface<object>> $events */
        $events = $eventGroup[$other];

        if (count($events) > 1) {
            throw new AssertionFailedError(sprintf(
                'Event `%s` was fired %d times, cannot make data assertion',
                $other,
                count($events)
            ));
        }

        $event = $events[0];

        if (array_key_exists($this->_dataKey, (array)$event->getData()) === false) {
            return false;
        }

        return $event->getData($this->_dataKey) === $this->_dataValue;
    }

    /**
     * Assertion message string
     */
    public function toString(): string
    {
        return sprintf('was fired with `%s` matching `', $this->_dataKey) . json_encode($this->_dataValue) . '`';
    }
}
