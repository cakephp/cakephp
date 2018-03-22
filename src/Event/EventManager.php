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
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

use InvalidArgumentException;

/**
 * The event manager is responsible for keeping track of event listeners, passing the correct
 * data to them, and firing them in the correct order, when associated events are triggered. You
 * can create multiple instances of this object to manage local events or keep a single instance
 * and pass it around to manage all events in your app.
 */
class EventManager implements EventManagerInterface
{

    /**
     * The default priority queue value for new, attached listeners
     *
     * @var int
     */
    public static $defaultPriority = 10;

    /**
     * The globally available instance, used for dispatching events attached from any scope
     *
     * @var \Cake\Event\EventManager
     */
    protected static $_generalManager;

    /**
     * List of listener callbacks associated to
     *
     * @var array
     */
    protected $_listeners = [];

    /**
     * Internal flag to distinguish a common manager from the singleton
     *
     * @var bool
     */
    protected $_isGlobal = false;

    /**
     * The event list object.
     *
     * @var \Cake\Event\EventList|null
     */
    protected $_eventList;

    /**
     * Enables automatic adding of events to the event list object if it is present.
     *
     * @var bool
     */
    protected $_trackEvents = false;

    /**
     * Returns the globally available instance of a Cake\Event\EventManager
     * this is used for dispatching events attached from outside the scope
     * other managers were created. Usually for creating hook systems or inter-class
     * communication
     *
     * If called with the first parameter, it will be set as the globally available instance
     *
     * @param \Cake\Event\EventManager|null $manager Event manager instance.
     * @return static The global event manager
     */
    public static function instance($manager = null)
    {
        if ($manager instanceof EventManager) {
            static::$_generalManager = $manager;
        }
        if (empty(static::$_generalManager)) {
            static::$_generalManager = new static();
        }

        static::$_generalManager->_isGlobal = true;

        return static::$_generalManager;
    }

    /**
     * Adds a new listener to an event.
     *
     * @param callable|\Cake\Event\EventListenerInterface $callable PHP valid callback type or instance of Cake\Event\EventListenerInterface to be called
     * when the event named with $eventKey is triggered. If a Cake\Event\EventListenerInterface instance is passed, then the `implementedEvents`
     * method will be called on the object to register the declared events individually as methods to be managed by this class.
     * It is possible to define multiple event handlers per event name.
     *
     * @param string|null $eventKey The event unique identifier name with which the callback will be associated. If $callable
     * is an instance of Cake\Event\EventListenerInterface this argument will be ignored
     *
     * @param array $options used to set the `priority` flag to the listener. In the future more options may be added.
     * Priorities are treated as queues. Lower values are called before higher ones, and multiple attachments
     * added to the same priority queue will be treated in the order of insertion.
     *
     * @return void
     * @throws \InvalidArgumentException When event key is missing or callable is not an
     *   instance of Cake\Event\EventListenerInterface.
     * @deprecated 3.0.0 Use on() instead.
     */
    public function attach($callable, $eventKey = null, array $options = [])
    {
        deprecationWarning('EventManager::attach() is deprecated. Use EventManager::on() instead.');
        if ($eventKey === null) {
            $this->on($callable);

            return;
        }
        if ($options) {
            $this->on($eventKey, $options, $callable);

            return;
        }
        $this->on($eventKey, $callable);
    }

    /**
     * {@inheritDoc}
     */
    public function on($eventKey = null, $options = [], $callable = null)
    {
        if ($eventKey instanceof EventListenerInterface) {
            $this->_attachSubscriber($eventKey);

            return $this;
        }
        $argCount = func_num_args();
        if ($argCount === 2) {
            $this->_listeners[$eventKey][static::$defaultPriority][] = [
                'callable' => $options
            ];

            return $this;
        }
        if ($argCount === 3) {
            $priority = isset($options['priority']) ? $options['priority'] : static::$defaultPriority;
            $this->_listeners[$eventKey][$priority][] = [
                'callable' => $callable
            ];

            return $this;
        }
        throw new InvalidArgumentException(
            'Invalid arguments for EventManager::on(). ' .
            "Expected 1, 2 or 3 arguments. Got {$argCount} arguments."
        );
    }

    /**
     * Auxiliary function to attach all implemented callbacks of a Cake\Event\EventListenerInterface class instance
     * as individual methods on this manager
     *
     * @param \Cake\Event\EventListenerInterface $subscriber Event listener.
     * @return void
     */
    protected function _attachSubscriber(EventListenerInterface $subscriber)
    {
        foreach ((array)$subscriber->implementedEvents() as $eventKey => $function) {
            $options = [];
            $method = $function;
            if (is_array($function) && isset($function['callable'])) {
                list($method, $options) = $this->_extractCallable($function, $subscriber);
            } elseif (is_array($function) && is_numeric(key($function))) {
                foreach ($function as $f) {
                    list($method, $options) = $this->_extractCallable($f, $subscriber);
                    $this->on($eventKey, $options, $method);
                }
                continue;
            }
            if (is_string($method)) {
                $method = [$subscriber, $function];
            }
            $this->on($eventKey, $options, $method);
        }
    }

    /**
     * Auxiliary function to extract and return a PHP callback type out of the callable definition
     * from the return value of the `implementedEvents` method on a Cake\Event\EventListenerInterface
     *
     * @param array $function the array taken from a handler definition for an event
     * @param \Cake\Event\EventListenerInterface $object The handler object
     * @return callable
     */
    protected function _extractCallable($function, $object)
    {
        $method = $function['callable'];
        $options = $function;
        unset($options['callable']);
        if (is_string($method)) {
            $method = [$object, $method];
        }

        return [$method, $options];
    }

    /**
     * Removes a listener from the active listeners.
     *
     * @param callable|\Cake\Event\EventListenerInterface $callable any valid PHP callback type or an instance of EventListenerInterface
     * @param string|null $eventKey The event unique identifier name with which the callback has been associated
     * @return void
     * @deprecated 3.0.0 Use off() instead.
     */
    public function detach($callable, $eventKey = null)
    {
        deprecationWarning('EventManager::detach() is deprecated. Use EventManager::off() instead.');
        if ($eventKey === null) {
            $this->off($callable);

            return;
        }
        $this->off($eventKey, $callable);
    }

    /**
     * {@inheritDoc}
     */
    public function off($eventKey, $callable = null)
    {
        if ($eventKey instanceof EventListenerInterface) {
            $this->_detachSubscriber($eventKey);

            return $this;
        }
        if ($callable instanceof EventListenerInterface) {
            $this->_detachSubscriber($callable, $eventKey);

            return $this;
        }
        if ($callable === null && is_string($eventKey)) {
            unset($this->_listeners[$eventKey]);

            return $this;
        }
        if ($callable === null) {
            foreach (array_keys($this->_listeners) as $name) {
                $this->off($name, $eventKey);
            }

            return $this;
        }
        if (empty($this->_listeners[$eventKey])) {
            return $this;
        }
        foreach ($this->_listeners[$eventKey] as $priority => $callables) {
            foreach ($callables as $k => $callback) {
                if ($callback['callable'] === $callable) {
                    unset($this->_listeners[$eventKey][$priority][$k]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Auxiliary function to help detach all listeners provided by an object implementing EventListenerInterface
     *
     * @param \Cake\Event\EventListenerInterface $subscriber the subscriber to be detached
     * @param string|null $eventKey optional event key name to unsubscribe the listener from
     * @return void
     */
    protected function _detachSubscriber(EventListenerInterface $subscriber, $eventKey = null)
    {
        $events = (array)$subscriber->implementedEvents();
        if (!empty($eventKey) && empty($events[$eventKey])) {
            return;
        }
        if (!empty($eventKey)) {
            $events = [$eventKey => $events[$eventKey]];
        }
        foreach ($events as $key => $function) {
            if (is_array($function)) {
                if (is_numeric(key($function))) {
                    foreach ($function as $handler) {
                        $handler = isset($handler['callable']) ? $handler['callable'] : $handler;
                        $this->off($key, [$subscriber, $handler]);
                    }
                    continue;
                }
                $function = $function['callable'];
            }
            $this->off($key, [$subscriber, $function]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($event)
    {
        if (is_string($event)) {
            $event = new Event($event);
        }

        $listeners = $this->listeners($event->getName());

        if ($this->_trackEvents) {
            $this->addEventToList($event);
        }

        if (!$this->_isGlobal && static::instance()->isTrackingEvents()) {
            static::instance()->addEventToList($event);
        }

        if (empty($listeners)) {
            return $event;
        }

        foreach ($listeners as $listener) {
            if ($event->isStopped()) {
                break;
            }
            $result = $this->_callListener($listener['callable'], $event);
            if ($result === false) {
                $event->stopPropagation();
            }
            if ($result !== null) {
                $event->setResult($result);
            }
        }

        return $event;
    }

    /**
     * Calls a listener.
     *
     * @param callable $listener The listener to trigger.
     * @param \Cake\Event\Event $event Event instance.
     * @return mixed The result of the $listener function.
     */
    protected function _callListener(callable $listener, Event $event)
    {
        $data = $event->getData();

        return $listener($event, ...array_values($data));
    }

    /**
     * {@inheritDoc}
     */
    public function listeners($eventKey)
    {
        $localListeners = [];
        if (!$this->_isGlobal) {
            $localListeners = $this->prioritisedListeners($eventKey);
            $localListeners = empty($localListeners) ? [] : $localListeners;
        }
        $globalListeners = static::instance()->prioritisedListeners($eventKey);
        $globalListeners = empty($globalListeners) ? [] : $globalListeners;

        $priorities = array_merge(array_keys($globalListeners), array_keys($localListeners));
        $priorities = array_unique($priorities);
        asort($priorities);

        $result = [];
        foreach ($priorities as $priority) {
            if (isset($globalListeners[$priority])) {
                $result = array_merge($result, $globalListeners[$priority]);
            }
            if (isset($localListeners[$priority])) {
                $result = array_merge($result, $localListeners[$priority]);
            }
        }

        return $result;
    }

    /**
     * Returns the listeners for the specified event key indexed by priority
     *
     * @param string $eventKey Event key.
     * @return array
     */
    public function prioritisedListeners($eventKey)
    {
        if (empty($this->_listeners[$eventKey])) {
            return [];
        }

        return $this->_listeners[$eventKey];
    }

    /**
     * Returns the listeners matching a specified pattern
     *
     * @param string $eventKeyPattern Pattern to match.
     * @return array
     */
    public function matchingListeners($eventKeyPattern)
    {
        $matchPattern = '/' . preg_quote($eventKeyPattern, '/') . '/';
        $matches = array_intersect_key(
            $this->_listeners,
            array_flip(
                preg_grep($matchPattern, array_keys($this->_listeners), 0)
            )
        );

        return $matches;
    }

    /**
     * Returns the event list.
     *
     * @return \Cake\Event\EventList
     */
    public function getEventList()
    {
        return $this->_eventList;
    }

    /**
     * Adds an event to the list if the event list object is present.
     *
     * @param \Cake\Event\Event $event An event to add to the list.
     * @return $this
     */
    public function addEventToList(Event $event)
    {
        if ($this->_eventList) {
            $this->_eventList->add($event);
        }

        return $this;
    }

    /**
     * Enables / disables event tracking at runtime.
     *
     * @param bool $enabled True or false to enable / disable it.
     * @return $this
     */
    public function trackEvents($enabled)
    {
        $this->_trackEvents = (bool)$enabled;

        return $this;
    }

    /**
     * Returns whether this manager is set up to track events
     *
     * @return bool
     */
    public function isTrackingEvents()
    {
        return $this->_trackEvents && $this->_eventList;
    }

    /**
     * Enables the listing of dispatched events.
     *
     * @param \Cake\Event\EventList $eventList The event list object to use.
     * @return $this
     */
    public function setEventList(EventList $eventList)
    {
        $this->_eventList = $eventList;
        $this->_trackEvents = true;

        return $this;
    }

    /**
     * Disables the listing of dispatched events.
     *
     * @return $this
     */
    public function unsetEventList()
    {
        $this->_eventList = null;
        $this->_trackEvents = false;

        return $this;
    }

    /**
     * Debug friendly object properties.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $properties = get_object_vars($this);
        $properties['_generalManager'] = '(object) EventManager';
        $properties['_listeners'] = [];
        foreach ($this->_listeners as $key => $priorities) {
            $listenerCount = 0;
            foreach ($priorities as $listeners) {
                $listenerCount += count($listeners);
            }
            $properties['_listeners'][$key] = $listenerCount . ' listener(s)';
        }
        if ($this->_eventList) {
            $count = count($this->_eventList);
            for ($i = 0; $i < $count; $i++) {
                $event = $this->_eventList[$i];
                $subject = $event->getSubject();
                $properties['_dispatchedEvents'][] = $event->getName() . ' with ' .
                    (is_object($subject) ? 'subject ' . get_class($subject) : 'no subject');
            }
        } else {
            $properties['_dispatchedEvents'] = null;
        }
        unset($properties['_eventList']);

        return $properties;
    }
}
