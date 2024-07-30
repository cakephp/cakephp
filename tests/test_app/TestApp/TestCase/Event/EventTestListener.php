<?php
declare(strict_types=1);

namespace TestApp\TestCase\Event;

use Cake\Event\EventInterface;

/**
 * Mock class used to test event dispatching
 */
class EventTestListener
{
    public $callList = [];

    /**
     * Test function to be used in event dispatching
     */
    public function listenerFunction(EventInterface $event): void
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Test function to be used in event dispatching
     */
    public function secondListenerFunction(EventInterface $event): void
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Test function to be used in event dispatching
     */
    public function thirdListenerFunction(EventInterface $event): void
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Auxiliary function to help in stopPropagation testing
     */
    public function stopListener(EventInterface $event): void
    {
        $event->stopPropagation();
    }
}
