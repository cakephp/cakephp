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
 * @since         CakePHP(tm) v 1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Core\Object;
use Cake\Event\Event;
use Cake\Event\EventListener;
use Cake\Utility\ObjectCollection;

/**
 * Base class for an individual Component. Components provide reusable bits of
 * controller logic that can be composed into a controller. Components also
 * provide request life-cycle callbacks for injecting logic at specific points.
 *
 * ## Life cycle callbacks
 *
 * Components can provide several callbacks that are fired at various stages of the request
 * cycle. The available callbacks are:
 *
 * - `initialize()` - Fired before the controller's beforeFilter method.
 * - `startup()` - Fired after the controller's beforeFilter method.
 * - `beforeRender()` - Fired before the view + layout are rendered.
 * - `shutdown()` - Fired after the action is complete and the view has been rendered.
 *    but before Controller::afterFilter().
 * - `beforeRedirect()` - Fired before a redirect() is done.
 *
 * Each callback has a slightly different signature:
 *
 * - `intitalize(Event $event, Controller $controller)`
 * - `startup(Event $event, Controller $controller)`
 * - `beforeRender(Event $event, Controller $controller)`
 * - `beforeRedirect(Event $event, Controller $controller, $url, $status, $exit)`
 * - `shutdown(Event $event, Controller $controller)`
 *
 * @package       Cake.Controller
 * @link          http://book.cakephp.org/2.0/en/controllers/components.html
 * @see Controller::$components
 */
class Component extends Object implements EventListener {

/**
 * Component collection class used to lazy load components.
 *
 * @var ComponentCollection
 */
	protected $_Collection;

/**
 * Settings for this Component
 *
 * @var array
 */
	public $settings = array();

/**
 * Other Components this component uses.
 *
 * @var array
 */
	public $components = array();

/**
 * A component lookup table used to lazy load component objects.
 *
 * @var array
 */
	protected $_componentMap = array();

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->_Collection = $collection;
		$this->settings = $settings;
		$this->_set($settings);
		if (!empty($this->components)) {
			$this->_componentMap = ObjectCollection::normalizeObjectArray($this->components);
		}
	}

/**
 * Magic method for lazy loading $components.
 *
 * @param string $name Name of component to get.
 * @return mixed A Component object or null.
 */
	public function __get($name) {
		if (isset($this->_componentMap[$name]) && !isset($this->{$name})) {
			$settings = array_merge((array)$this->_componentMap[$name]['settings'], array('enabled' => false));
			$this->{$name} = $this->_Collection->load($this->_componentMap[$name]['class'], $settings);
		}
		if (isset($this->{$name})) {
			return $this->{$name};
		}
	}

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Event $event An Event instance
 * @param Controller $controller Controller with components to initialize
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::initialize
 */
	public function initialize(Event $event, Controller $controller) {
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Event $event An Event instance
 * @param Controller $controller Controller with components to startup
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::startup
 */
	public function startup(Event $event, Controller $controller) {
	}

/**
 * Called before the Controller::beforeRender(), and before
 * the view class is loaded, and before Controller::render()
 *
 * @param Event $event An Event instance
 * @param Controller $controller Controller with components to beforeRender
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::beforeRender
 */
	public function beforeRender(Event $event, Controller $controller) {
	}

/**
 * Called after Controller::render() and before the output is printed to the browser.
 *
 * @param Event $event An Event instance
 * @param Controller $controller Controller with components to shutdown
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::shutdown
 */
	public function shutdown(Event $event, Controller $controller) {
	}

/**
 * Called before Controller::redirect(). Allows you to replace the URL that will
 * be redirected to with a new URL. The return of this method can either be an array or a string.
 *
 * If the return is an array and contains a 'url' key. You may also supply the following:
 *
 * - `status` The status code for the redirect
 * - `exit` Whether or not the redirect should exit.
 *
 * If your response is a string or an array that does not contain a 'url' key it will
 * be used as the new URL to redirect to.
 *
 * @param Event $event An Event instance
 * @param Controller $controller Controller with components to beforeRedirect
 * @param string|array $url Either the string or URL array that is being redirected to.
 * @param integer $status The status code of the redirect
 * @param boolean $exit Will the script exit.
 * @return array|void Either an array or null.
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::beforeRedirect
 */
	public function beforeRedirect(Event $event, Controller $controller, $url, $status = null, $exit = true) {
	}

/**
 * Get the Controller callbacks this Component is interested in.
 *
 * Uses Conventions to map controller events to standard component
 * callback method names. By defining one of the callback methods a 
 * component is assumed to be interested in the related event.
 *
 * Override this method if you need to add non-conventional event listeners.
 * Or if you want components to listen to non-standard events.
 *
 * @return array
 */
	public function implementedEvents() {
		$eventMap = [
			'Controller.initialize' => 'initialize',
			'Controller.startup' => 'startup',
			'Controller.beforeRender' => 'beforeRender',
			'Controller.beforeRedirect' => 'beforeRedirect',
			'Controller.shutdown' => 'shutdown',
		];
		$events = [];
		foreach ($eventMap as $event => $method) {
			if (method_exists($this, $method)) {
				$events[$event] = $method;
			}
		}
		return $events;
	}

}
