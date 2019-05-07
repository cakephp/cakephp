<?php
declare(strict_types=1);

namespace TestApp\View;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;

/**
 * TestViewEventListenerInterface
 *
 * An event listener to test cakePHP events
 */
class TestViewEventListenerInterface implements EventListenerInterface
{
    /**
     * type of view before rendering has occurred
     *
     * @var string
     */
    public $beforeRenderViewType;

    /**
     * type of view after rendering has occurred
     *
     * @var string
     */
    public $afterRenderViewType;

    /**
     * implementedEvents method
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'beforeRender',
            'View.afterRender' => 'afterRender',
        ];
    }

    /**
     * beforeRender method
     *
     * @param \Cake\Event\EventInterface $event the event being sent
     * @return void
     */
    public function beforeRender(EventInterface $event)
    {
        $this->beforeRenderViewType = $event->getSubject()->getCurrentType();
    }

    /**
     * afterRender method
     *
     * @param \Cake\Event\EventInterface $event the event being sent
     * @return void
     */
    public function afterRender(EventInterface $event)
    {
        $this->afterRenderViewType = $event->getSubject()->getCurrentType();
    }
}
