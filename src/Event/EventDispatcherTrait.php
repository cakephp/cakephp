<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.10
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Implements Cake\Event\EventDispatcherInterface.
 *
 */
trait EventDispatcherTrait
{

    /**
     * Instance of the Cake\Event\EventManager this object is using
     * to dispatch inner events.
     *
     * @var \Cake\Event\EventManager
     */
    protected $_eventManager = null;

    /**
     * Default class name for new event objects.
     *
     * @var string
     */
    protected $_eventClass = '\Cake\Event\Event';

    /**
     * Returns the Cake\Event\EventManager manager instance for this object.
     *
     * You can use this instance to register any new listeners or callbacks to the
     * object events, or create your own events and trigger them at will.
     *
     * @param \Cake\Event\EventManager|null $eventManager the eventManager to set
     * @return \Cake\Event\EventManager
     */
    public function eventManager(EventManager $eventManager = null)
    {
        if ($eventManager !== null) {
            $this->_eventManager = $eventManager;
        } elseif (empty($this->_eventManager)) {
            $this->_eventManager = new EventManager();
        }

        return $this->_eventManager;
    }

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
     *
     * @return \Cake\Event\Event
     */
    public function dispatchEvent($name, $data = null, $subject = null)
    {
        if ($subject === null) {
            $subject = $this;
        }

        $event = new $this->_eventClass($name, $subject, $data);
        $this->eventManager()->dispatch($event);

        return $event;
    }
}
