<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;
use Cake\Event\EventInterface;

/**
 * Fixture: abstract class with EventListener attribute (must be silently skipped).
 */
abstract class AbstractListener
{
    /**
     * Abstract handler that must not be registered by the connector.
     *
     * @param \Cake\Event\EventInterface $event Dispatched event.
     * @return void
     */
    #[EventListener('Order.afterPlace')]
    abstract public function handle(EventInterface $event): void;
}
