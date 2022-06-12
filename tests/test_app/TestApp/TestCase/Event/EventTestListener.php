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
     *
     * @return bool|void
     */
    public function listenerFunction(EventInterface $event)
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Test function to be used in event dispatching
     *
     * @return bool|void
     */
    public function secondListenerFunction(EventInterface $event)
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Test function to be used in event dispatching
     *
     * @return bool|void
     */
    public function thirdListenerFunction(EventInterface $event)
    {
        $this->callList[] = __FUNCTION__;
    }

    /**
     * Auxiliary function to help in stopPropagation testing
     *
     * @return bool|void
     */
    public function stopListener(EventInterface $event)
    {
        $event->stopPropagation();
    }
}
