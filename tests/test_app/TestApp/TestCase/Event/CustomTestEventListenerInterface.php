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
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'fake.event' => 'listenerFunction',
            'another.event' => ['callable' => 'secondListenerFunction'],
            'closure.event' => $this->thirdlistenerFunction(...),
            'multiple.handlers' => [
                ['callable' => 'listenerFunction'],
                ['callable' => $this->secondListenerFunction(...)],
            ],
        ];
    }
}
