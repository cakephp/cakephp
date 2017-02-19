<?php
namespace Cake\TestSuite\Constraint;

if (class_exists('PHPUnit_Runner_Version') && !class_exists('PHPUnit\Framework\Constraint\Constraint')) {
    class_alias('PHPUnit_Framework_Constraint', 'PHPUnit\Framework\Constraint\Constraint');
}
if (class_exists('PHPUnit_Runner_Version') && !class_exists('PHPUnit\Framework\AssertionFailedError')) {
    class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\Framework\AssertionFailedError');
}

use Cake\Event\EventManager;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * EventFired constraint
 */
class EventFired extends Constraint
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
