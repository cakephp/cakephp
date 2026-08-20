<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: listener requiring a constructor dependency.
 */
class ContainerAwareListener
{
    /**
     * Whether the listener has handled an event.
     *
     * @var bool
     */
    public bool $wasCalled = false;

    /**
     * Initializes the listener with its injected service.
     *
     * @param object $service Service resolved by the application's container.
     */
    public function __construct(public readonly object $service)
    {
    }

    /**
     * Records that the order placement event was handled.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Order.afterPlace')]
    public function handle(EventInterface $event): void
    {
        $this->wasCalled = true;
    }
}
