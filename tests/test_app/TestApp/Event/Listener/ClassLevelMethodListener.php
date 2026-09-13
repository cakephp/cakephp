<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: class-level EventListener attribute with an explicit method name.
 */
#[EventListener('Order.afterPlace', method: 'handleOrder')]
class ClassLevelMethodListener
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
    public function handleOrder(EventInterface $event): void
    {
        $this->called[] = 'handleOrder';
    }
}
