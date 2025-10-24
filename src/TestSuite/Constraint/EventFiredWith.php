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
     * Array of fired events
     *
     * @var \Cake\Event\EventManager
     */
    protected EventManager $_eventManager;

    /**
     * Event data key
     *
     * @var string
     */
    protected string $_dataKey;

    /**
     * Event data value
     *
     * @var mixed
     */
    protected mixed $_dataValue;

    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager $eventManager Event manager to check
     * @param string $dataKey Data key
     * @param mixed $dataValue Data value
     */
    public function __construct(EventManager $eventManager, string $dataKey, mixed $dataValue)
    {
        $this->_eventManager = $eventManager;
        $this->_dataKey = $dataKey;
        $this->_dataValue = $dataValue;

        if ($this->_eventManager->getEventList() === null) {
            throw new AssertionFailedError(
                'The event manager you are asserting against is not configured to track events.',
            );
        }
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     * @return bool
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function matches(mixed $other): bool
    {
        $firedEvents = [];
        $list = $this->_eventManager->getEventList();
        if ($list !== null) {
            $totalEvents = count($list);
            for ($e = 0; $e < $totalEvents; $e++) {
                $firedEvents[] = $list[$e];
            }
        }

        $eventGroup = (new Collection($firedEvents))
            ->groupBy(function (EventInterface $event): string {
                return $event->getName();
            })
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
                count($events),
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
     *
     * @return string
     */
    public function toString(): string
    {
        return "was fired with `{$this->_dataKey}` matching `" . json_encode($this->_dataValue) . '`';
    }
}
