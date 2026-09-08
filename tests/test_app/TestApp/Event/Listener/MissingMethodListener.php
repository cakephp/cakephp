<?php
declare(strict_types=1);

namespace TestApp\Event\Listener;

use Cake\Event\Attribute\EventListener;

/**
 * Fixture: class-level EventListener attribute referencing a non-existent method.
 *
 * Used to verify that EventAttributeException is thrown during connection.
 */
#[EventListener('Order.afterPlace', method: 'nonExistentMethod')]
class MissingMethodListener
{
}
