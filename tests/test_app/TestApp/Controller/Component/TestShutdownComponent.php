<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;

/**
 * Tests deprecated shutdown callback
 */
class TestShutdownComponent extends Component
{
    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function shutdown(EventInterface $event): void
    {
    }
}
