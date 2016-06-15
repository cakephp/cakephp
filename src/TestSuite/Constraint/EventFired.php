<?php
namespace Cake\TestSuite\Constraint;

use Cake\Event\EventManager;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Constraint;

/**
 * EventFired constraint
 */
class EventFired extends PHPUnit_Framework_Constraint
{
    /**
     * Array of fired events
     *
     * @var EventManager
     */
    protected $_eventManager;

    /**
     * Constructor
     *
     * @param EventManager $eventManager Event manager to check
     */
    public function __construct($eventManager)
    {
        parent::__construct();
        $this->_eventManager = $eventManager;

        if ($this->_eventManager->getEventList() === null) {
            throw new PHPUnit_Framework_AssertionFailedError('The event manager you are asserting against is not configured to track events.');
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
