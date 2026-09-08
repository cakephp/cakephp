<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: method-level EventListener attribute on a protected method.
 *
 * Used to verify that EventAttributeException is thrown during connection.
 */
class NonPublicMethodListener
{
    /**
     * Protected handler that must not be registered by the connector.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Order.afterPlace')]
    protected function handle(EventInterface $event): void
    {
    }
}
