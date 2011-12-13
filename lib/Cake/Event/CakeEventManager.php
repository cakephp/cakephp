<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	  Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP(tm) Project
 * @package		  Cake.Event
 * @since		  CakePHP(tm) v 2.1
 * @license		  MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeEventListener', 'Event');

/**
 * The event manager is responsible for keeping track of event listeners and pass the correct
 * data to them, and fire them in the correct order, when associated events are triggered. You
 * can create multiple instances of this objects to manage local events or keep a single instance
 * and pass it around to manage all events in your app.
 *
 * @package Cake.Event
 */
class CakeEventManager {

/**
 * The default priority queue value for new attached listeners
 *
 * @var int
 */
	public static $defaultPriority = 10;

/**
 * List of listener callbacks associated to
 *
 * @var object $Listeners
 */
	protected $_listeners = array();

/**
 * Adds a new listener to an event. Listeners 
 *
 * @param callback|CakeEventListener $callable PHP valid callback type or instance of CakeListener to be called
 * when the event named with $eventKey is triggered.
 * @param mixed $eventKey The event unique identifier name to with the callback will be associated. If $callable
 * is an instance of CakeEventListener this argument will be ignored
 * @param array $options used to set the `priority` and `passParams` flags to the listener.
 * Priorities are handled like queues, and multiple attachments into the same priority queue will be treated in
 * the order of insertion. `passParams` means that the event data property will be converted to function arguments
 * when the listener is called. If $called is an instance of CakeEventListener, this parameter will be ignored
 * @return void
 */
	public function attach($callable, $eventKey = null, $options = array()) {
		if (!$eventKey && !($callable instanceof CakeEventListener)) {
			throw new InvalidArgumentException(__d('cake_dev', 'The eventKey variable is required'));
		}
		if ($callable instanceof CakeEventListener) {
			foreach ($callable->implementedEvents() as $eventKey => $function) {
				$options = array();
				$method = null;
				if (is_array($function)) {
					$method = array($callable, $function['callable']);
					unset($function['callable']);
					$options = $function;
				} else {
					$method = array($callable, $function);
				}
				$this->attach($method, $eventKey, $options);
			}
			return;
		}
		$options = $options + array('priority' => self::$defaultPriority, 'passParams' => false);
		$this->_listeners[$eventKey][$options['priority']][] = array(
			'callable' => $callable,
			'passParams' => $options['passParams'],
		);
	}

/**
 * Removes a listener from the active listeners.
 *
 * @param callback|CakeListener $callable any valid PHP callback type or an instance of CakeListener
 * @return void
 */
	public function detach($callable, $eventKey = null) {
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
 * Dispatches a new event to all configured listeners
 *
 * @param mixed $event the event key name or instance of CakeEvent
 * @return void
 */
	public function dispatch($event) {
		if (is_string($event)) {
			$Event = new CakeEvent($event);
		}
		if (empty($this->_listeners[$event->name()])) {
			return;
		}

		foreach ($this->listeners($event->name()) as $listener) {
			if ($event->isStopped()) {
				break;
			}
			if ($listener['passParams'] === true) {
				call_user_func_array($listener['callable'], $event->data);
			} else {
				call_user_func($listener['callable'], $event);
			}
			continue;
		}
	}

/**
 * Returns a list of all listeners for a eventKey in the order they should be called
 *
 * @param string $eventKey
 * @return array
 */
	public function listeners($eventKey) {
		if (empty($this->_listeners[$eventKey])) {
			return array();
		}
		ksort($this->_listeners[$eventKey]);
		return array_reduce($this->_listeners[$eventKey], 'array_merge', array());
	}

}