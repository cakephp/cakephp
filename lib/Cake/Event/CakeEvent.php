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
 * @package		  Cake.Observer
 * @since		  CakePHP(tm) v 2.1
 * @license		  MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Represent the transport class of events across the system, it receives a name, and subject and an optional
 * payload. The name can be any string that uniquely identifies the event across the application, while the subject
 * represents the object that the event is applying to.
 *
 * @package Cake.Event
 */
class CakeEvent {

/**
 * Name of the event
 * 
 * @var string $name
 */
	protected $_name = null;

/**
 * The object this event applies to (usually the same object that generates the event)
 *
 * @var object
 */
	protected $_subject;

/**
 * Custom data for the method that receives the event
 *
 * @var mixed $data
 */
	public $data = null;

/**
 * Property used to retain the result value of the event listeners
 *
 * @var mixed $result
 */
	public $result = null;

/**
 * Flags an event as stopped or not, default is false
 *
 * @var boolean
 */
	protected $_stopped = false;

/**
 * Constructor
 *
 * @param string $name Name of the event
 * @param object $subject the object that this event applies to (usually the object that is generating the event)
 * @param mixed $data any value you wish to be transported with this event to it can be read by listeners
 *
 * ## Examples of usage:
 *
 * {{{
 *	$event = new CakeEvent('Order.afterBuy', $this, array('buyer' => $userData));
 *	$event = new CakeEvent('User.afterRegister', $UserModel);
 * }}}
 *
 */
	public function __construct($name, $subject = null, $data = null) {
		$this->_name = $name;
		$this->data = $data;
		$this->_subject = $subject;
	}

/**
 * Dynamically returns the name and subject if accessed directly
 *
 * @param string $attribute
 * @return mixed
 */
	public function __get($attribute) {
		if ($attribute === 'name' || $attribute === 'subject') {
			return $this->{$attribute}();
		}
	}

/**
 * Returns the name of this event. This is usually used as the event identifier
 *
 * @return string
 */
	public function name() {
		return $this->_name;
	}

/**
 * Returns the subject of this event
 *
 * @return string
 */
	public function subject() {
		return $this->_subject;
	}

/**
 * Stops the event from being used anymore
 *
 * @return void
 */
	public function stopPropagation() {
		return $this->_stopped = true;
	}

/**
 * Check if the event is stopped
 *
 * @return boolean True if the event is stopped
 */
	public function isStopped() {
		return $this->_stopped;
	}

}
