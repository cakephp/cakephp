<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: class-level EventListener attribute referencing a protected method.
 *
 * Used to verify that class-level listener methods are validated during connection.
 */
#[EventListener('Order.afterPlace', method: 'handle')]
class ClassLevelNonPublicMethodListener
{
    /**
     * Protected handler that must not be registered by the connector.
     *
     * @param \Cake\Event\EventInterface $event Event being dispatched.
     * @return void
     */
    protected function handle(EventInterface $event): void
    {
    }
}
