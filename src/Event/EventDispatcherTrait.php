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

    /**
     * Shortcut for binding events on event manager. Accepts simplified representation of callables:
     * ```
     * // assuming that 'handler' and 'condition' are methods on current object
     * // you can write
     * $this->on('event', 'handler', ['if' => 'condition'])
     * // instead of
     * $this->on('event', [$this, 'handler'], ['if' => [$this, 'condition']])
     * ```
     *
     * @param string $event Event name
     * @param callable|string $callable Callable to be used as an event handler
     * @param array $options Array of options
     * @return void
     */
    public function on($event, $callable, $options = [])
    {
        if (isset($options['if'])) {
            $options['if'] = $this->toCallables($options['if']);
        }

        if (isset($options['unless'])) {
            $options['unless'] = $this->toCallables($options['unless']);
        }

        $this->eventManager()->on($event, $options, $this->toCallables($callable)[0]);
    }

    /**
     * Converts simplified representation of callables to an array of valid PHP callables.
     *
     * Mainly does two things:
     * 1. converts strings to callables (in case they are not callables already) assuming
     * they are referencing a method in current object:
     * ```
     * ['isValid', 'isActive'] => [[$this, 'isValid'], [$this, 'isActive']]
     * ['time'] => ['time'] // time is already valid callable
     * ```
     *
     * 2. converts single callable to an array:
     * ```
     * 'time' => ['time']
     * function () { ... } => [function () { ... }]
     * [$object, 'method'] => [[$object, 'method']]
     * ```
     *
     * @param mixed $callables Simplified representation of callables
     * @return callable[] Array of valid PHP callables
     */
    protected function toCallables($callables)
    {
        // simple cast to an array would not work in case of [$object, 'method']
        if (is_callable($callables)) {
            $result = [$callables];
        } else {
            $result = (array)$callables;
        }

        foreach ($result as &$callable) {
            if (is_string($callable) && !is_callable($callable)) {
                $callable = [$this, $callable];
            }
        }

        return $result;
    }
}
