<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: method-level EventListener attributes with different priorities.
 */
class PriorityListener
{
    /**
     * Records the order in which listener methods are invoked for test assertions.
     *
     * @var list<string>
     */
    public array $called = [];

    /**
     * High-priority (low number) listener for Model.afterSave.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Model.afterSave', priority: 5)]
    public function highPriority(EventInterface $event): void
    {
        $this->called[] = 'highPriority';
    }

    /**
     * Low-priority (high number) listener for Model.afterSave.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Model.afterSave', priority: 100)]
    public function lowPriority(EventInterface $event): void
    {
        $this->called[] = 'lowPriority';
    }
}
