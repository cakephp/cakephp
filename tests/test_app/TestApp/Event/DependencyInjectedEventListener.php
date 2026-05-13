<?php
declare(strict_types=1);

namespace TestApp\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;

class DependencyInjectedEventListener implements EventListenerInterface
{
    public ?string $lastGreeting = null;

    public function __construct(protected GreeterService $greeter)
    {
    }

    public function implementedEvents(): array
    {
        return [
            'Greeting.before' => 'onGreeting',
        ];
    }

    public function onGreeting(EventInterface $event): void
    {
        $this->lastGreeting = $this->greeter->greet((string)$event->getData('name'));
    }
}
