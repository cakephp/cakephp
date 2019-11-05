<?php
declare(strict_types=1);

namespace Cake\TestSuite\Constraint;

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
    protected $_eventManager;

    /**
     * Event data key
     *
     * @var string
     */
    protected $_dataKey;

    /**
     * Event data value
     *
     * @var string|null
     */
    protected $_dataValue;

    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager $eventManager Event manager to check
     * @param string $dataKey Data key
     * @param string|null $dataValue Data value
     */
    public function __construct(EventManager $eventManager, string $dataKey, ?string $dataValue)
    {
        $this->_eventManager = $eventManager;
        $this->_dataKey = $dataKey;
        $this->_dataValue = $dataValue;

        if ($this->_eventManager->getEventList() === null) {
            throw new AssertionFailedError(
                'The event manager you are asserting against is not configured to track events.'
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
    public function matches($other): bool
    {
        $firedEvents = [];
        $list = $this->_eventManager->getEventList();
        if ($list === null) {
            $totalEvents = 0;
        } else {
            $totalEvents = count($list);
            for ($e = 0; $e < $totalEvents; $e++) {
                $firedEvents[] = $list[$e];
            }
        }

        $eventGroup = collection($firedEvents)
            ->groupBy(function (EventInterface $event): string {
                return $event->getName();
            })
            ->toArray();

        if (!array_key_exists($other, $eventGroup)) {
            return false;
        }

        /** @var \Cake\Event\EventInterface[] $event */
        $events = $eventGroup[$other];

        if (count($events) > 1) {
            throw new AssertionFailedError(sprintf(
                'Event "%s" was fired %d times, cannot make data assertion',
                $other,
                count($events)
            ));
        }

        $event = $events[0];

        if (array_key_exists($this->_dataKey, $event->getData()) === false) {
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
        return 'was fired with ' . $this->_dataKey . ' matching ' . (string)$this->_dataValue;
    }
}
