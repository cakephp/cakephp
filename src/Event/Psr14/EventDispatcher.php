<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Psr14;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * PSR-14 EventDispatcher implementation.
 *
 * Dispatches events to listeners provided by a ListenerProvider.
 *
 * ### Usage
 *
 * ```php
 * $provider = new ListenerProvider();
 * $provider->addListener(UserCreatedEvent::class, function (UserCreatedEvent $event) {
 *     // Handle event
 * });
 *
 * $dispatcher = new EventDispatcher($provider);
 * $dispatcher->dispatch(new UserCreatedEvent($user));
 * ```
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Constructor.
     *
     * @param \Psr\EventDispatcher\ListenerProviderInterface $listenerProvider The listener provider.
     */
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event): object
    {
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }

    /**
     * Get the listener provider.
     *
     * @return \Psr\EventDispatcher\ListenerProviderInterface
     */
    public function getListenerProvider(): ListenerProviderInterface
    {
        return $this->listenerProvider;
    }
}
