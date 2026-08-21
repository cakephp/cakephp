<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: method-level repeatable EventListener attributes.
 */
class OrdersListener
{
    /**
     * Records dispatched event names for test assertions.
     *
     * @var list<string>
     */
    public array $called = [];

    /**
     * Handles receipt sending after an order is placed.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Order.afterPlace')]
    public function sendReceipt(EventInterface $event): void
    {
        $this->called[] = 'sendReceipt';
    }

    /**
     * Updates metrics after an order is placed or cancelled.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Order.afterPlace', priority: 5)]
    #[EventListener('Order.afterCancel', priority: 20)]
    public function updateMetrics(EventInterface $event): void
    {
        $this->called[] = 'updateMetrics';
    }
}
