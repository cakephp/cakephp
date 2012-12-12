<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP(tm) Project
 * @package		  Cake.Event
 * @since		  CakePHP(tm) v 2.1
 * @license		  MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Event;

use Cake\Error;

/**
 * The event manager is responsible for keeping track of event listeners, passing the correct
 * data to them, and firing them in the correct order, when associated events are triggered. You
 * can create multiple instances of this object to manage local events or keep a single instance
 * and pass it around to manage all events in your app.
 *
 * @package Cake.Event
 */
class EventManager {

/**
 * The default priority queue value for new, attached listeners
 *
 * @var int
 */
	public static $defaultPriority = 10;

/**
 * The globally available instance, used for dispatching events attached from any scope
 *
 * @var Cake\Event\EventManager
 */
	protected static $_generalManager = null;

/**
 * List of listener callbacks associated to
 *
 * @var object $Listeners
 */
	protected $_listeners = array();

/**
 * Internal flag to distinguish a common manager from the singleton
 *
 * @var boolean
 */
	protected $_isGlobal = false;

/**
 * Returns the globally available instance of a Cake\Event\EventManager
 * this is used for dispatching events attached from outside the scope
 * other managers were created. Usually for creating hook systems or inter-class
 * communication
 *
 * If called with the first parameter, it will be set as the globally available instance
 *
 * @param Cake\Event\EventManager $manager
 * @return Cake\Event\EventManager the global event manager
 */
	public static function instance($manager = null) {
		if ($manager instanceof EventManager) {
			static::$_generalManager = $manager;
		}
		if (empty(static::$_generalManager)) {
			static::$_generalManager = new EventManager;
		}

		static::$_generalManager->_isGlobal = true;
		return static::$_generalManager;
	}

/**
 * Adds a new listener to an event. Listeners
 *
 * @param callback|Cake\Event\EventListener $callable PHP valid callback type or instance of Cake\Event\EventListener to be called
 * when the event named with $eventKey is triggered. If a Cake\Event\EventListener instance is passed, then the `implementedEvents`
 * method will be called on the object to register the declared events individually as methods to be managed by this class.
 * It is possible to define multiple event handlers per event name.
 *
 * @param string $eventKey The event unique identifier name with which the callback will be associated. If $callable
 * is an instance of Cake\Event\EventListener this argument will be ignored
 *
 * @param array $options used to set the `priority` and `passParams` flags to the listener.
 * Priorities are handled like queues, and multiple attachments added to the same priority queue will be treated in
 * the order of insertion. `passParams` means that the event data property will be converted to function arguments
 * when the listener is called. If $called is an instance of Cake\Event\EventListener, this parameter will be ignored
 *
 * @return void
 * @throws InvalidArgumentException When event key is missing or callable is not an
 *   instance of Cake\Event\EventListener.
 */
	public function attach($callable, $eventKey = null, $options = array()) {
		if (!$eventKey && !($callable instanceof EventListener)) {
			throw new \InvalidArgumentException(__d('cake_dev', 'The eventKey variable is required'));
		}
		if ($callable instanceof EventListener) {
			$this->_attachSubscriber($callable);
			return;
		}
		$options = $options + array('priority' => static::$defaultPriority, 'passParams' => false);
		$this->_listeners[$eventKey][$options['priority']][] = array(
			'callable' => $callable,
			'passParams' => $options['passParams'],
		);
	}

/**
 * Auxiliary function to attach all implemented callbacks of a Cake\Event\EventListener class instance
 * as individual methods on this manager
 *
 * @param Cake\Event\EventListener $subscriber
 * @return void
 */
	protected function _attachSubscriber(EventListener $subscriber) {
		foreach ($subscriber->implementedEvents() as $eventKey => $function) {
			$options = array();
			$method = $function;
			if (is_array($function) && isset($function['callable'])) {
				list($method, $options) = $this->_extractCallable($function, $subscriber);
			} elseif (is_array($function) && is_numeric(key($function))) {
				foreach ($function as $f) {
					list($method, $options) = $this->_extractCallable($f, $subscriber);
					$this->attach($method, $eventKey, $options);
				}
				continue;
			}
			if (is_string($method)) {
				$method = array($subscriber, $function);
			}
			$this->attach($method, $eventKey, $options);
		}
	}

/**
 * Auxiliary function to extract and return a PHP callback type out of the callable definition
 * from the return value of the `implementedEvents` method on a Cake\Event\EventListener
 *
 * @param array $function the array taken from a handler definition for an event
 * @param Cake\Event\EventListener $object The handler object
 * @return callback
 */
	protected function _extractCallable($function, $object) {
		$method = $function['callable'];
		$options = $function;
		unset($options['callable']);
		if (is_string($method)) {
			$method = array($object, $method);
		}
		return array($method, $options);
	}

/**
 * Removes a listener from the active listeners.
 *
 * @param callback|Cake\Event\EventListener $callable any valid PHP callback type or an instance of EventListener
 * @return void
 */
	public function detach($callable, $eventKey = null) {
		if ($callable instanceof EventListener) {
			return $this->_detachSubscriber($callable, $eventKey);
		}
		if (empty($eventKey)) {
			foreach (array_keys($this->_listeners) as $eventKey) {
				$this->detach($callable, $eventKey);
			}
			return;
		}
		if (empty($this->_listeners[$eventKey])) {
			return;
		}
		foreach ($this->_listeners[$eventKey] as $priority => $callables) {
			foreach ($callables as $k => $callback) {
				if ($callback['callable'] === $callable) {
					unset($this->_listeners[$eventKey][$priority][$k]);
					break;
				}
			}
		}
	}

/**
 * Auxiliary function to help detach all listeners provided by an object implementing EventListener
 *
 * @param Cake\Event\EventListener $subscriber the subscriber to be detached
 * @param string $eventKey optional event key name to unsubscribe the listener from
 * @return void
 */
	protected function _detachSubscriber(EventListener $subscriber, $eventKey = null) {
		$events = $subscriber->implementedEvents();
		if (!empty($eventKey) && empty($events[$eventKey])) {
			return;
		} elseif (!empty($eventKey)) {
			$events = array($eventKey => $events[$eventKey]);
		}
		foreach ($events as $key => $function) {
			if (is_array($function)) {
				if (is_numeric(key($function))) {
					foreach ($function as $handler) {
						$handler = isset($handler['callable']) ? $handler['callable'] : $handler;
						$this->detach(array($subscriber, $handler), $key);
					}
					continue;
				}
				$function = $function['callable'];
			}
			$this->detach(array($subscriber, $function), $key);
		}
	}

/**
 * Dispatches a new event to all configured listeners
 *
 * @param string|Cake\Event\Event $event the event key name or instance of Event
 * @return void
 */
	public function dispatch($event) {
		if (is_string($event)) {
			$event = new Event($event);
		}

		if (!$this->_isGlobal) {
			static::instance()->dispatch($event);
		}

		if (empty($this->_listeners[$event->name()])) {
			return;
		}

		foreach ($this->listeners($event->name()) as $listener) {
			if ($event->isStopped()) {
				break;
			}
			if ($listener['passParams'] === true) {
				$result = call_user_func_array($listener['callable'], $event->data);
			} else {
				$result = call_user_func($listener['callable'], $event);
			}
			if ($result === false) {
				$event->stopPropagation();
			}
			if ($result !== null) {
				$event->result = $result;
			}
			continue;
		}
	}

/**
 * Returns a list of all listeners for an eventKey in the order they should be called
 *
 * @param string $eventKey
 * @return array
 */
	public function listeners($eventKey) {
		if (empty($this->_listeners[$eventKey])) {
			return array();
		}
		ksort($this->_listeners[$eventKey]);
		$result = array();
		foreach ($this->_listeners[$eventKey] as $priorityQ) {
			$result = array_merge($result, $priorityQ);
		}
		return $result;
	}

}
