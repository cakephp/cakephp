<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Routing;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;
use Cake\Event\EventListener;

/**
 * This abstract class represents a filter to be applied to a dispatcher cycle. It acts as as
 * event listener with the ability to alter the request or response as needed before it is handled
 * by a controller or after the response body has already been built.
 *
 */
abstract class DispatcherFilter implements EventListener {

	use InstanceConfigTrait;

/**
 * Default priority for all methods in this filter
 *
 * @var int
 */
	public $priority = 10;

/**
 * Default config
 *
 * These are merged with user-provided config when the class is used.
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * Constructor.
 *
 * @param array $config Settings for the filter.
 */
	public function __construct($config = []) {
		$this->config($config);
	}

/**
 * Returns the list of events this filter listens to.
 * Dispatcher notifies 2 different events `Dispatcher.before` and `Dispatcher.after`.
 * By default this class will attach `preDispatch` and `postDispatch` method respectively.
 *
 * Override this method at will to only listen to the events you are interested in.
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Dispatcher.beforeDispatch' => array('callable' => 'beforeDispatch', 'priority' => $this->priority),
			'Dispatcher.afterDispatch' => array('callable' => 'afterDispatch', 'priority' => $this->priority),
		);
	}

/**
 * Method called before the controller is instantiated and called to serve a request.
 * If used with default priority, it will be called after the Router has parsed the
 * URL and set the routing params into the request object.
 *
 * If a Cake\Network\Response object instance is returned, it will be served at the end of the
 * event cycle, not calling any controller as a result. This will also have the effect of
 * not calling the after event in the dispatcher.
 *
 * If false is returned, the event will be stopped and no more listeners will be notified.
 * Alternatively you can call `$event->stopPropagation()` to achieve the same result.
 *
 * @param \Cake\Event\Event $event container object having the `request`, `response` and `additionalParams`
 *    keys in the data property.
 */
	public function beforeDispatch(Event $event) {
	}

/**
 * Method called after the controller served a request and generated a response.
 * It is possible to alter the response object at this point as it is not sent to the
 * client yet.
 *
 * If false is returned, the event will be stopped and no more listeners will be notified.
 * Alternatively you can call `$event->stopPropagation()` to achieve the same result.
 *
 * @param \Cake\Event\Event $event container object having the `request` and  `response`
 *    keys in the data property.
 */
	public function afterDispatch(Event $event) {
	}
}
