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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

use Cake\Core\ContainerInterface;
use InvalidArgumentException;

/**
 * Shared registration loop used by `BaseApplication` and `BasePlugin` to attach
 * declarative event listeners to an event manager.
 *
 * Each listener must be a class string implementing `EventListenerInterface`;
 * the trait resolves it through the given DI container so constructor
 * dependencies can be injected.
 */
trait EventListenerRegistrationTrait
{
    /**
     * Validate, resolve, and register the given event listeners.
     *
     * @param list<class-string<\Cake\Event\EventListenerInterface>> $listeners FQCNs to register.
     * @param \Cake\Event\EventManagerInterface $eventManager Manager the listeners are attached to.
     * @param \Cake\Core\ContainerInterface $container Container used to resolve each listener.
     * @return void
     * @throws \InvalidArgumentException When an entry is not a class string implementing
     *    `EventListenerInterface`.
     */
    protected function registerEventListeners(
        array $listeners,
        EventManagerInterface $eventManager,
        ContainerInterface $container,
    ): void {
        foreach ($listeners as $listener) {
            if (!is_a($listener, EventListenerInterface::class, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Event listener `%s` must be a class name that implements %s',
                    $listener,
                    EventListenerInterface::class,
                ));
            }

            $eventManager->on($container->get($listener));
        }
    }
}
