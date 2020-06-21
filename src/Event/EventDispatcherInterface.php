<?php
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
 * The Cake\Event\EventDispatcherTrait lets you easily implement
 * this interface.
 *
 * @method \Cake\Event\EventManager getEventManager()
 * @method $this setEventManager(\Cake\Event\EventManager $eventManager)
 */
interface EventDispatcherInterface
{
    /**
     * Wrapper for creating and dispatching events.
     *
     * Returns a dispatched event.
     *
     * @param string $name Name of the event.
     * @param array|null $data Any value you wish to be transported with this event to
     * it can be read by listeners.
     * @param object|null $subject The object that this event applies to
     * ($this by default).
     * @return \Cake\Event\Event
     */
    public function dispatchEvent($name, $data = null, $subject = null);

    /**
     * Returns the Cake\Event\EventManager manager instance for this object.
     *
     * You can use this instance to register any new listeners or callbacks to the
     * object events, or create your own events and trigger them at will.
     *
     * @deprecated 3.5.0 Use getEventManager()/setEventManager() instead.
     * @param \Cake\Event\EventManager|null $eventManager the eventManager to set
     * @return \Cake\Event\EventManager
     */
    public function eventManager(EventManager $eventManager = null);
}
