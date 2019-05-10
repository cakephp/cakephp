<?php
declare(strict_types=1);

namespace TestApp\TestCase\Event;

/**
 * Mock class used to test event dispatching
 */
class EventTestListener
{
    public $callList = [];

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function listenerFunction()
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function secondListenerFunction()
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Auxiliary function to help in stopPropagation testing
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function stopListener($event)
    {
        $event->stopPropagation();
    }
}
