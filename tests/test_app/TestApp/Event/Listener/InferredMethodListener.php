<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: class-level EventListener attribute with an inferred method name.
 *
 * The method name `onOrderAfterPlace` is inferred from the event name `Order.afterPlace`.
 */
#[EventListener('Order.afterPlace')]
class InferredMethodListener
{
    /**
     * Records dispatched event names for test assertions.
     *
     * @var list<string>
     */
    public array $called = [];

    /**
     * Handles an order placement event.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    public function onOrderAfterPlace(EventInterface $event): void
    {
        $this->called[] = 'onOrderAfterPlace';
    }
}
