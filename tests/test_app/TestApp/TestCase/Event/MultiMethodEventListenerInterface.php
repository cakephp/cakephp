<?php
declare(strict_types=1);

namespace TestApp\TestCase\Event;

use Cake\Event\EventListenerInterface;

/**
 * Mock used for testing the subscriber objects
 */
class MultiMethodEventListenerInterface extends EventTestListener implements EventListenerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'multiple.handlers' => [
                ['callable' => 'listenerFunction'],
                ['callable' => 'secondListenerFunction'],
            ],
        ];
    }
}
