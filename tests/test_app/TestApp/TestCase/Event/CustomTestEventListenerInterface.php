<?php
declare(strict_types=1);

namespace TestApp\TestCase\Event;

use Cake\Event\EventListenerInterface;
use Closure;

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
            'closure.event' => Closure::fromCallable([$this, 'thirdlistenerFunction']),
            'multiple.handlers' => [
                ['callable' => 'listenerFunction'],
                ['callable' => Closure::fromCallable([$this, 'secondListenerFunction'])],
            ],
        ];
    }
}
