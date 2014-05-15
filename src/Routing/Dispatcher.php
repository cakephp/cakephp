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
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Controller\Controller;
use Cake\Controller\Error\MissingControllerException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Error\Exception;
use Cake\Event\Event;
use Cake\Event\EventListener;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Inflector;

/**
 * Dispatcher converts Requests into controller actions. It uses the dispatched Request
 * to locate and load the correct controller. If found, the requested action is called on
 * the controller.
 *
 */
class Dispatcher {

/**
 * Event manager, used to handle dispatcher filters
 *
 * @var \Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * Connected filter objects
 *
 * @var array
 */
	protected $_filters = [];

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
 * Returns the Cake\Event\EventManager instance or creates one if none was
 * created. Attaches the default listeners and filters
 *
 * @return \Cake\Event\EventManager
 */
	public function getEventManager() {
		if (!$this->_eventManager) {
			$this->_eventManager = new EventManager();
		}
		return $this->_eventManager;
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
 * @param \Cake\Network\Request $request Request object to dispatch.
 * @param \Cake\Network\Response $response Response object to put the results of the dispatch into.
 * @return string|void if `$request['return']` is set then it returns response body, null otherwise
 * @throws \Cake\Controller\Error\MissingControllerException When the controller is missing.
 */
	public function dispatch(Request $request, Response $response) {
		$beforeEvent = new Event('Dispatcher.beforeDispatch', $this, compact('request', 'response'));
		$this->getEventManager()->dispatch($beforeEvent);

		$request = $beforeEvent->data['request'];
		if ($beforeEvent->result instanceof Response) {
			if (isset($request->params['return'])) {
				return $beforeEvent->result->body();
			}
			$beforeEvent->result->send();
			return;
		}

		$controller = $this->_getController($request, $response);

		if (!($controller instanceof Controller)) {
			throw new MissingControllerException(array(
				'class' => Inflector::camelize($request->params['controller']),
				'plugin' => empty($request->params['plugin']) ? null : Inflector::camelize($request->params['plugin']),
				'prefix' => empty($request->params['prefix']) ? null : Inflector::camelize($request->params['prefix']),
				'_ext' => empty($request->params['_ext']) ? null : $request->params['_ext']
			));
		}

		$response = $this->_invoke($controller);
		if (isset($request->params['return'])) {
			return $response->body();
		}

		$afterEvent = new Event('Dispatcher.afterDispatch', $this, compact('request', 'response'));
		$this->getEventManager()->dispatch($afterEvent);
		$afterEvent->data['response']->send();
	}

/**
 * Initializes the components and models a controller will be using.
 * Triggers the controller action and invokes the rendering if Controller::$autoRender
 * is true. If a response object is returned by controller action that is returned
 * else controller's $response property is returned.
 *
 * @param Controller $controller Controller to invoke
 * @return \Cake\Network\Response The resulting response object
 * @throws \Cake\Error\Exception If data returned by controller action is not an
 *   instance of Response
 */
	protected function _invoke(Controller $controller) {
		$controller->constructClasses();
		$result = $controller->startupProcess();
		if ($result instanceof Response) {
			return $result;
		}

		$response = $controller->invokeAction();
		if ($response !== null && !($response instanceof Response)) {
			throw new Exception('Controller action can only return an instance of Response');
		}

		if (!$response && $controller->autoRender) {
			$response = $controller->render();
		} elseif (!$response) {
			$response = $controller->response;
		}

		$result = $controller->shutdownProcess();
		if ($result instanceof Response) {
			return $result;
		}

		return $response;
	}

/**
 * Get controller to use, either plugin controller or application controller
 *
 * @param \Cake\Network\Request $request Request object
 * @param \Cake\Network\Response $response Response for the controller.
 * @return mixed name of controller if not loaded, or object if loaded
 */
	protected function _getController($request, $response) {
		$pluginPath = $controller = null;
		$namespace = 'Controller';
		if (!empty($request->params['plugin'])) {
			$pluginPath = Inflector::camelize($request->params['plugin']) . '.';
		}
		if (!empty($request->params['controller'])) {
			$controller = Inflector::camelize($request->params['controller']);
		}
		if (!empty($request->params['prefix'])) {
			$namespace .= '/' . Inflector::camelize($request->params['prefix']);
		}
		$className = false;
		if ($pluginPath . $controller) {
			$className = App::classname($pluginPath . $controller, $namespace, 'Controller');
		}
		if (!$className) {
			return false;
		}
		$reflection = new \ReflectionClass($className);
		if ($reflection->isAbstract() || $reflection->isInterface()) {
			return false;
		}
		return $reflection->newInstance($request, $response);
	}

/**
 * Load controller and return controller class name
 *
 * @param \Cake\Network\Request $request
 * @return string|bool Name of controller class name
 */
	protected function _loadController($request) {
		$pluginName = $pluginPath = $controller = null;
		$namespace = 'Controller';
		if (!empty($request->params['plugin'])) {
			$pluginName = Inflector::camelize($request->params['plugin']);
			$pluginPath = $pluginName . '.';
		}
		if (!empty($request->params['controller'])) {
			$controller = Inflector::camelize($request->params['controller']);
		}
		if (!empty($request->params['prefix'])) {
			$namespace .= '/' . Inflector::camelize($request->params['prefix']);
		}
		if ($pluginPath . $controller) {
			return App::className($pluginPath . $controller, $namespace, 'Controller');
		}
		return false;
	}

/**
 * Add a filter to this dispatcher.
 *
 * The added filter will be attached to the event manager used
 * by this dispatcher.
 *
 * @param \Cake\Event\EventListener $filter The filter to connect. Can be
 *   any EventListener. Typically an instance of \Cake\Routing\DispatcherFilter.
 * @return void
 */
	public function addFilter(EventListener $filter) {
		$this->_filters[] = $filter;
		$this->getEventManager()->attach($filter);
	}

/**
 * Get the list of connected filters.
 *
 * @return array
 */
	public function filters() {
		return $this->_filters;
	}

}
