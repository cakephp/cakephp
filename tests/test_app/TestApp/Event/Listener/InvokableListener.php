<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: class-level EventListener attribute resolved via __invoke().
 */
#[EventListener('Order.afterPlace')]
class InvokableListener
{
    /**
     * Records dispatched event names for test assertions.
     *
     * @var list<string>
     */
    public array $called = [];

    /**
     * Invoked for every dispatched event this class is registered for.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    public function __invoke(EventInterface $event): void
    {
        $this->called[] = '__invoke';
    }
}
