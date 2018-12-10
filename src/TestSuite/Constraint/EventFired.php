<?php
namespace Cake\TestSuite\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * EventFired constraint
 *
 * @internal
 */
class EventFired extends Constraint
{
    /**
     * Array of fired events
     *
     * @var \Cake\Event\EventManager
     */
    protected $_eventManager;

    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager $eventManager Event manager to check
     */
    public function __construct($eventManager)
    {
        parent::__construct();
        $this->_eventManager = $eventManager;

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
        return $this->_eventManager->getEventList()->hasEvent($other);
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString()
    {
        return 'was fired';
    }
}
