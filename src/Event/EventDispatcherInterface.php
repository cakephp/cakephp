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
 * @since         3.0.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Objects implementing this interface can emit events.
 *
 * Objects with this interface can trigger events, and have
 * an event manager retrieved from them.
 *
 * The {@link \Cake\Event\EventDispatcherTrait} lets you easily implement
 * this interface.
 *
 * @template TSubject of object
 */
interface EventDispatcherInterface
{
    /**
     * Wrapper for creating and dispatching events.
     *
     * Returns a dispatched event.
     *
     * @param string $name Name of the event.
     * @param array $data Any value you wish to be transported with this event to
     * it can be read by listeners.
     * @param TSubject|null $subject The object that this event applies to
     * ($this by default).
     * @return \Cake\Event\EventInterface<TSubject>
     */
    public function dispatchEvent(string $name, array $data = [], ?object $subject = null): EventInterface;

    /**
     * Sets the Cake\Event\EventManager manager instance for this object.
     *
     * You can use this instance to register any new listeners or callbacks to the
     * object events, or create your own events and trigger them at will.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager the eventManager to set
     * @return $this
     */
    public function setEventManager(EventManagerInterface $eventManager);

    /**
     * Returns the Cake\Event\EventManager manager instance for this object.
     *
     * @return \Cake\Event\EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface;
}
