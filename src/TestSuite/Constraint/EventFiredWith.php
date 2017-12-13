<?php
namespace Cake\TestSuite\Constraint;

if (class_exists('PHPUnit_Runner_Version', false)
    && !class_exists('PHPUnit\Framework\Constraint\Constraint', false)
) {
    class_alias('PHPUnit_Framework_Constraint', 'PHPUnit\Framework\Constraint\Constraint');
}
if (class_exists('PHPUnit_Runner_Version', false)
    && !class_exists('PHPUnit\Framework\AssertionFailedError', false)
) {
    class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\Framework\AssertionFailedError');
}

use Cake\Event\Event;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * EventFiredWith constraint
 *
 * Another glorified in_array check
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
     * @var string
     */
    protected $_dataValue;

    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager $eventManager Event manager to check
     * @param string $dataKey Data key
     * @param string $dataValue Data value
     */
    public function __construct($eventManager, $dataKey, $dataValue)
    {
        parent::__construct();
        $this->_eventManager = $eventManager;
        $this->_dataKey = $dataKey;
        $this->_dataValue = $dataValue;

        if ($this->_eventManager->getEventList() === null) {
            throw new AssertionFailedError('The event manager you are asserting against is not configured to track events.');
        }
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     * @return bool
     */
    public function matches($other)
    {
        $firedEvents = [];
        $list = $this->_eventManager->getEventList();
        $totalEvents = count($list);
        for ($e = 0; $e < $totalEvents; $e++) {
            $firedEvents[] = $list[$e];
        }

        $eventGroup = collection($firedEvents)
            ->groupBy(function (Event $event) {
                return $event->getName();
            })
            ->toArray();

        if (!array_key_exists($other, $eventGroup)) {
            return false;
        }

        $events = $eventGroup[$other];

        if (count($events) > 1) {
            throw new AssertionFailedError(sprintf('Event "%s" was fired %d times, cannot make data assertion', $other, count($events)));
        }

        /* @var \Cake\Event\Event $event */
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
    public function toString()
    {
        return 'was fired with ' . $this->_dataKey . ' matching ' . (string)$this->_dataValue;
    }
}
