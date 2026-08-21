<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: listener declarations targeting the default and a named event manager.
 */
#[EventListener('Order.afterPlace', method: 'handleOrders', manager: 'orders')]
class MultipleManagerListener
{
    /**
     * Invoked listener methods.
     *
     * @var list<string>
     */
    public array $called = [];

    /**
     * Handles an event from the connector's default event manager.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Order.afterPlace')]
    #[EventListener('Order.afterPlace', manager: 'orders')]
    public function handleDefault(EventInterface $event): void
    {
        $this->called[] = 'default';
    }

    /**
     * Handles an event from the named orders event manager.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    public function handleOrders(EventInterface $event): void
    {
        $this->called[] = 'orders';
    }
}
