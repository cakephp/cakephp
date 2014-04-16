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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;
use Cake\Event\EventListener;

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
 * - `initialize()` - Called before the controller's beforeFilter method.
 * - `startup()` - Called after the controller's beforeFilter method,
 *   and before the controller action is called.
 * - `beforeRender()` - Called before the Controller beforeRender, and
 *   before the view class is loaded.
 * - `shutdown()` - Called after the action is complete and the view has been rendered.
 *    but before Controller::afterFilter().
 * - `beforeRedirect()` - Called before Controller::redirect(), and
 *   before a redirect() is done. Allows you to replace the URL that will
 *   be redirected to with a new URL. The return of this method can either be an
 *   array or a string. If the return is an array and contains a 'url' key.
 *   You may also supply the following:
 *
 *   - `status` The status code for the redirect
 *   - `exit` Whether or not the redirect should exit.
 *
 *   If your response is a string or an array that does not contain a 'url' key it will
 *   be used as the new URL to redirect to.
 *
 * Each callback has a slightly different signature:
 *
 * - `intitalize(Event $event)`
 * - `startup(Event $event)`
 * - `beforeRender(Event $event)`
 * - `beforeRedirect(Event $event $url, Response $response)`
 * - `shutdown(Event $event)`
 *
 * While the controller is not an explicit argument it is the subject of each event
 * and can be fetched using Event::subject().
 *
 * @link http://book.cakephp.org/2.0/en/controllers/components.html
 * @see Controller::$components
 */
class Component implements EventListener {

	use InstanceConfigTrait;

/**
 * Component registry class used to lazy load components.
 *
 * @var ComponentRegistry
 */
	protected $_registry;

/**
 * Other Components this component uses.
 *
 * @var array
 */
	public $components = array();

/**
 * Default config
 *
 * These are merged with user-provided config when the component is used.
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * A component lookup table used to lazy load component objects.
 *
 * @var array
 */
	protected $_componentMap = array();

/**
 * Constructor
 *
 * @param ComponentRegistry $registry A ComponentRegistry this component can use to lazy load its components
 * @param array $config Array of configuration settings.
 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$this->_registry = $registry;

		$this->config($config);

		if (!empty($this->components)) {
			$this->_componentMap = $registry->normalizeArray($this->components);
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
			$config = array_merge((array)$this->_componentMap[$name]['config'], array('enabled' => false));
			$this->{$name} = $this->_registry->load($this->_componentMap[$name]['class'], $config);
		}
		if (isset($this->{$name})) {
			return $this->{$name};
		}
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
