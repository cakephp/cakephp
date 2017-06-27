<?php
/**
 * Dispatcher takes the URL information, parses it for parameters and
 * tells the involved controllers what to do.
 *
 * This is the heart of CakePHP's operation.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Routing
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');
App::uses('Scaffold', 'Controller');
App::uses('View', 'View');
App::uses('Debugger', 'Utility');
App::uses('CakeEvent', 'Event');
App::uses('CakeEventManager', 'Event');
App::uses('CakeEventListener', 'Event');

/**
 * Dispatcher converts Requests into controller actions. It uses the dispatched Request
 * to locate and load the correct controller. If found, the requested action is called on
 * the controller.
 *
 * @package       Cake.Routing
 */
class Dispatcher implements CakeEventListener {

/**
 * Event manager, used to handle dispatcher filters
 *
 * @var CakeEventManager
 */
	protected $_eventManager;

/**
 * Constructor.
 *
 * @param string $base The base directory for the application. Writes `App.base` to Configure.
 */
	public function __construct($base = false) {
		if ($base !== false) {
			Configure::write('App.base', $base);
		}
	}

/**
 * Returns the CakeEventManager instance or creates one if none was
 * created. Attaches the default listeners and filters
 *
 * @return CakeEventManager
 */
	public function getEventManager() {
		if (!$this->_eventManager) {
			$this->_eventManager = new CakeEventManager();
			$this->_eventManager->attach($this);
			$this->_attachFilters($this->_eventManager);
		}
		return $this->_eventManager;
	}

/**
 * Returns the list of events this object listens to.
 *
 * @return array
 */
	public function implementedEvents() {
		return array('Dispatcher.beforeDispatch' => 'parseParams');
	}

/**
 * Attaches all event listeners for this dispatcher instance. Loads the
 * dispatcher filters from the configured locations.
 *
 * @param CakeEventManager $manager Event manager instance.
 * @return void
 * @throws MissingDispatcherFilterException
 */
	protected function _attachFilters($manager) {
		$filters = Configure::read('Dispatcher.filters');
		if (empty($filters)) {
			return;
		}

		foreach ($filters as $index => $filter) {
			$settings = array();
			if (is_array($filter) && !is_int($index) && class_exists($index)) {
				$settings = $filter;
				$filter = $index;
			}
			if (is_string($filter)) {
				$filter = array('callable' => $filter);
			}
			if (is_string($filter['callable'])) {
				list($plugin, $callable) = pluginSplit($filter['callable'], true);
				App::uses($callable, $plugin . 'Routing/Filter');
				if (!class_exists($callable)) {
					throw new MissingDispatcherFilterException($callable);
				}
				$manager->attach(new $callable($settings));
			} else {
				$on = strtolower($filter['on']);
				$options = array();
				if (isset($filter['priority'])) {
					$options = array('priority' => $filter['priority']);
				}
				$manager->attach($filter['callable'], 'Dispatcher.' . $on . 'Dispatch', $options);
			}
		}
	}

/**
 * Dispatches and invokes given Request, handing over control to the involved controller. If the controller is set
 * to autoRender, via Controller::$autoRender, then Dispatcher will render the view.
 *
 * Actions in CakePHP can be any public method on a controller, that is not declared in Controller. If you
 * want controller methods to be public and in-accessible by URL, then prefix them with a `_`.
 * For example `public function _loadPosts() { }` would not be accessible via URL. Private and protected methods
 * are also not accessible via URL.
 *
 * If no controller of given name can be found, invoke() will throw an exception.
 * If the controller is found, and the action is not found an exception will be thrown.
 *
 * @param CakeRequest $request Request object to dispatch.
 * @param CakeResponse $response Response object to put the results of the dispatch into.
 * @param array $additionalParams Settings array ("bare", "return") which is melded with the GET and POST params
 * @return string|null if `$request['return']` is set then it returns response body, null otherwise
 * @triggers Dispatcher.beforeDispatch $this, compact('request', 'response', 'additionalParams')
 * @triggers Dispatcher.afterDispatch $this, compact('request', 'response')
 * @throws MissingControllerException When the controller is missing.
 */
	public function dispatch(CakeRequest $request, CakeResponse $response, $additionalParams = array()) {
		$beforeEvent = new CakeEvent('Dispatcher.beforeDispatch', $this, compact('request', 'response', 'additionalParams'));
		$this->getEventManager()->dispatch($beforeEvent);

		$request = $beforeEvent->data['request'];
		if ($beforeEvent->result instanceof CakeResponse) {
			if (isset($request->params['return'])) {
				return $beforeEvent->result->body();
			}
			$beforeEvent->result->send();
			return null;
		}

		$controller = $this->_getController($request, $response);

		if (!($controller instanceof Controller)) {
			throw new MissingControllerException(array(
				'class' => Inflector::camelize($request->params['controller']) . 'Controller',
				'plugin' => empty($request->params['plugin']) ? null : Inflector::camelize($request->params['plugin'])
			));
		}

		$response = $this->_invoke($controller, $request);
		if (isset($request->params['return'])) {
			return $response->body();
		}

		$afterEvent = new CakeEvent('Dispatcher.afterDispatch', $this, compact('request', 'response'));
		$this->getEventManager()->dispatch($afterEvent);
		$afterEvent->data['response']->send();
	}

/**
 * Initializes the components and models a controller will be using.
 * Triggers the controller action, and invokes the rendering if Controller::$autoRender
 * is true and echo's the output. Otherwise the return value of the controller
 * action are returned.
 *
 * @param Controller $controller Controller to invoke
 * @param CakeRequest $request The request object to invoke the controller for.
 * @return CakeResponse the resulting response object
 */
	protected function _invoke(Controller $controller, CakeRequest $request) {
		$controller->constructClasses();
		$controller->startupProcess();

		$response = $controller->response;
		$render = true;
		$result = $controller->invokeAction($request);
		if ($result instanceof CakeResponse) {
			$render = false;
			$response = $result;
		}

		if ($render && $controller->autoRender) {
			$response = $controller->render();
		} elseif (!($result instanceof CakeResponse) && $response->body() === null) {
			$response->body($result);
		}
		$controller->shutdownProcess();

		return $response;
	}

/**
 * Applies Routing and additionalParameters to the request to be dispatched.
 * If Routes have not been loaded they will be loaded, and app/Config/routes.php will be run.
 *
 * @param CakeEvent $event containing the request, response and additional params
 * @return void
 */
	public function parseParams($event) {
		$request = $event->data['request'];
		Router::setRequestInfo($request);
		$params = Router::parse($request->url);
		$request->addParams($params);

		if (!empty($event->data['additionalParams'])) {
			$request->addParams($event->data['additionalParams']);
		}
	}

/**
 * Get controller to use, either plugin controller or application controller
 *
 * @param CakeRequest $request Request object
 * @param CakeResponse $response Response for the controller.
 * @return mixed name of controller if not loaded, or object if loaded
 */
	protected function _getController($request, $response) {
		$ctrlClass = $this->_loadController($request);
		if (!$ctrlClass) {
			return false;
		}
		$reflection = new ReflectionClass($ctrlClass);
		if ($reflection->isAbstract() || $reflection->isInterface()) {
			return false;
		}
		return $reflection->newInstance($request, $response);
	}

/**
 * Load controller and return controller class name
 *
 * @param CakeRequest $request Request instance.
 * @return string|bool Name of controller class name
 */
	protected function _loadController($request) {
		$pluginName = $pluginPath = $controller = null;
		if (!empty($request->params['plugin'])) {
			$pluginName = $controller = Inflector::camelize($request->params['plugin']);
			$pluginPath = $pluginName . '.';
		}
		if (!empty($request->params['controller'])) {
			$controller = Inflector::camelize($request->params['controller']);
		}
		if ($pluginPath . $controller) {
			$class = $controller . 'Controller';
			App::uses('AppController', 'Controller');
			App::uses($pluginName . 'AppController', $pluginPath . 'Controller');
			App::uses($class, $pluginPath . 'Controller');
			if (class_exists($class)) {
				return $class;
			}
		}
		return false;
	}

}
