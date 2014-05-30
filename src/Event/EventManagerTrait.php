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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 *
 * Provides the _eventManager property for usage in classes that require it.
 *
 */
trait EventManagerTrait {

/**
 * Instance of the Cake\Event\EventManager this object is using
 * to dispatch inner events.
 *
 * @var \Cake\Event\EventManager
 */
	protected $_eventManager = null;

/**
 * Returns the Cake\Event\EventManager manager instance for this object.
 *
 * You can use this instance to register any new listeners or callbacks to the
 * object events, or create your own events and trigger them at will.
 *
 * @param \Cake\Event\EventManager $eventManager the eventManager to set
 * @return \Cake\Event\EventManager
 */
	public function eventManager(EventManager $eventManager = null) {
		if ($eventManager != null) {
			$this->_eventManager = $eventManager;
		} elseif (empty($this->_eventManager)) {
			$this->_eventManager = new EventManager();
		}
		return $this->_eventManager;
	}

}
