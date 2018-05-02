<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Interface EventManagerInterface
 */
interface EventManagerInterface
{

    /**
     * Adds a new listener to an event.
     *
     * A variadic interface to add listeners that emulates jQuery.on().
     *
     * Binding an EventListenerInterface:
     *
     * ```
     * $eventManager->on($listener);
     * ```
     *
     * Binding with no options:
     *
     * ```
     * $eventManager->on('Model.beforeSave', $callable);
     * ```
     *
     * Binding with options:
     *
     * ```
     * $eventManager->on('Model.beforeSave', ['priority' => 90], $callable);
     * ```
     *
     * @param string|\Cake\Event\EventListenerInterface|null $eventKey The event unique identifier name
     * with which the callback will be associated. If $eventKey is an instance of
     * Cake\Event\EventListenerInterface its events will be bound using the `implementedEvents` methods.
     *
     * @param array|callable $options Either an array of options or the callable you wish to
     * bind to $eventKey. If an array of options, the `priority` key can be used to define the order.
     * Priorities are treated as queues. Lower values are called before higher ones, and multiple attachments
     * added to the same priority queue will be treated in the order of insertion.
     *
     * @param callable|null $callable The callable function you want invoked.
     *
     * @return $this
     * @throws \InvalidArgumentException When event key is missing or callable is not an
     *   instance of Cake\Event\EventListenerInterface.
     */
    public function on($eventKey = null, $options = [], $callable = null);

    /**
     * Remove a listener from the active listeners.
     *
     * Remove a EventListenerInterface entirely:
     *
     * ```
     * $manager->off($listener);
     * ```
     *
     * Remove all listeners for a given event:
     *
     * ```
     * $manager->off('My.event');
     * ```
     *
     * Remove a specific listener:
     *
     * ```
     * $manager->off('My.event', $callback);
     * ```
     *
     * Remove a callback from all events:
     *
     * ```
     * $manager->off($callback);
     * ```
     *
     * @param string|\Cake\Event\EventListenerInterface $eventKey The event unique identifier name
     *   with which the callback has been associated, or the $listener you want to remove.
     * @param callable|null $callable The callback you want to detach.
     * @return $this
     */
    public function off($eventKey, $callable = null);

    /**
     * Dispatches a new event to all configured listeners
     *
     * @param string|\Cake\Event\EventInterface $event The event key name or instance of EventInterface.
     * @return \Cake\Event\EventInterface
     * @triggers $event
     */
    public function dispatch($event);

    /**
     * Returns a list of all listeners for an eventKey in the order they should be called
     *
     * @param string $eventKey Event key.
     * @return array
     */
    public function listeners($eventKey);
}
