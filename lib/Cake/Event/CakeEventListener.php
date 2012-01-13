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
 * @package		  Cake.Observer
 * @since		  CakePHP(tm) v 2.1
 * @license		  MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Objects implementing this interface should declare the `implementedEvents` function
 * to hint the event manager what methods should be called when an event is triggered.
 *
 * @package Cake.Event
 */
interface CakeEventListener  {

/**
 * Returns a list of events this object is implementing, when the class is registered
 * in an event manager, each individual method will be associated to the respective event.
 *
 * ## Example:
 *
 * {{{
 *	public function implementedEvents() {
 *		return array(
 *			'Order.complete' => 'sendEmail',
 *			'Article.afterBuy' => 'decrementInventory',
 *			'User.onRegister' => array('callable' => 'logRegistration', 'priority' => 20, 'passParams' => true)
 *		);
 *	}
 * }}}
 *
 * @return array associative array or event key names pointing to the function
 * that should be called in the object when the respective event is fired
 */
	public function implementedEvents();
}