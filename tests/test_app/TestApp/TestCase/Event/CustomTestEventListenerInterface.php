<?php
declare(strict_types=1);

namespace TestApp\TestCase\Event;

use Cake\Event\EventListenerInterface;

/**
 * Mock used for testing the subscriber objects
 */
class CustomTestEventListenerInterface extends EventTestListener implements EventListenerInterface
{
    /**
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'fake.event' => 'listenerFunction',
            'another.event' => ['callable' => 'secondListenerFunction'],
            'multiple.handlers' => [
                ['callable' => 'listenerFunction'],
                ['callable' => 'thirdListenerFunction'],
            ],
        ];
    }

    /**
     * Test function to be used in event dispatching
     *
     * @return void
     */
    public function thirdListenerFunction()
    {
        $this->callList[] = __FUNCTION__;
    }
}
