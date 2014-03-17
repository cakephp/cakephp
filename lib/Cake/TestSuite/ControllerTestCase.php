<?php
/**
 * ControllerTestCase file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Dispatcher', 'Routing');
App::uses('CakeTestCase', 'TestSuite');
App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Helper', 'View');
App::uses('CakeEvent', 'Event');

/**
 * ControllerTestDispatcher class
 *
 * @package       Cake.TestSuite
 */
class ControllerTestDispatcher extends Dispatcher {

/**
 * The controller to use in the dispatch process
 *
 * @var Controller
 */
	public $testController = null;

/**
 * Use custom routes during tests
 *
 * @var boolean
 */
	public $loadRoutes = true;

/**
 * Returns the test controller
 *
 * @return Controller
 */
	protected function _getController($request, $response) {
		if ($this->testController === null) {
			$this->testController = parent::_getController($request, $response);
		}
		$this->testController->helpers = array_merge(array('InterceptContent'), $this->testController->helpers);
		$this->testController->setRequest($request);
		$this->testController->response = $this->response;
		foreach ($this->testController->Components->loaded() as $component) {
			$object = $this->testController->Components->{$component};
			if (isset($object->response)) {
				$object->response = $response;
			}
			if (isset($object->request)) {
				$object->request = $request;
			}
		}
		return $this->testController;
	}

/**
 * Loads routes and resets if the test case dictates it should
 *
 * @return void
 */
	protected function _loadRoutes() {
		parent::_loadRoutes();
		if (!$this->loadRoutes) {
			Router::reload();
		}
	}

}

/**
 * InterceptContentHelper class
 *
 * @package       Cake.TestSuite
 */
class InterceptContentHelper extends Helper {

/**
 * Intercepts and stores the contents of the view before the layout is rendered
 *
 * @param string $viewFile The view file
 */
	public function afterRender($viewFile) {
		$this->_View->assign('__view_no_layout__', $this->_View->fetch('content'));
		$this->_View->Helpers->unload('InterceptContent');
	}

}

/**
 * ControllerTestCase class
 *
 * @package       Cake.TestSuite
 */
abstract class ControllerTestCase extends CakeTestCase {

/**
 * The controller to test in testAction
 *
 * @var Controller
 */
	public $controller = null;

/**
 * Automatically mock controllers that aren't mocked
 *
 * @var boolean
 */
	public $autoMock = true;

/**
 * Use custom routes during tests
 *
 * @var boolean
 */
	public $loadRoutes = true;

/**
 * The resulting view vars of the last testAction call
 *
 * @var array
 */
	public $vars = null;

/**
 * The resulting rendered view of the last testAction call
 *
 * @var string
 */
	public $view = null;

/**
 * The resulting rendered layout+view of the last testAction call
 *
 * @var string
 */
	public $contents = null;

/**
 * The returned result of the dispatch (requestAction), if any
 *
 * @var string
 */
	public $result = null;

/**
 * The headers that would have been sent by the action
 *
 * @var string
 */
	public $headers = null;

/**
 * Flag for checking if the controller instance is dirty.
 * Once a test has been run on a controller it should be rebuilt
 * to clean up properties.
 *
 * @var boolean
 */
	protected $_dirtyController = false;

/**
 * Used to enable calling ControllerTestCase::testAction() without the testing
 * framework thinking that it's a test case
 *
 * @param string $name The name of the function
 * @param array $arguments Array of arguments
 * @return the return of _testAction
 * @throws BadMethodCallException when you call methods that don't exist.
 */
	public function __call($name, $arguments) {
		if ($name === 'testAction') {
			return call_user_func_array(array($this, '_testAction'), $arguments);
		}
		throw new BadMethodCallException("Method '{$name}' does not exist.");
	}

/**
 * Lets you do functional tests of a controller action.
 *
 * ### Options:
 *
 * - `data` Will be used as the request data. If the `method` is GET,
 *   data will be used a GET params. If the `method` is POST, it will be used
 *   as POST data. By setting `$options['data']` to a string, you can simulate XML or JSON
 *   payloads to your controllers allowing you to test REST webservices.
 * - `method` POST or GET. Defaults to POST.
 * - `return` Specify the return type you want. Choose from:
 *     - `vars` Get the set view variables.
 *     - `view` Get the rendered view, without a layout.
 *     - `contents` Get the rendered view including the layout.
 *     - `result` Get the return value of the controller action. Useful
 *       for testing requestAction methods.
 *
 * @param string $url The url to test
 * @param array $options See options
 * @return mixed
 */
	protected function _testAction($url = '', $options = array()) {
		$this->vars = $this->result = $this->view = $this->contents = $this->headers = null;

		$options = array_merge(array(
			'data' => array(),
			'method' => 'POST',
			'return' => 'result'
		), $options);

		$restore = array('get' => $_GET, 'post' => $_POST);

		$_SERVER['REQUEST_METHOD'] = strtoupper($options['method']);
		if (is_array($options['data'])) {
			if (strtoupper($options['method']) === 'GET') {
				$_GET = $options['data'];
				$_POST = array();
			} else {
				$_POST = $options['data'];
				$_GET = array();
			}
		}
		$request = $this->getMock('CakeRequest', array('_readInput'), array($url));

		if (is_string($options['data'])) {
			$request->expects($this->any())
				->method('_readInput')
				->will($this->returnValue($options['data']));
		}

		$Dispatch = new ControllerTestDispatcher();
		foreach (Router::$routes as $route) {
			if ($route instanceof RedirectRoute) {
				$route->response = $this->getMock('CakeResponse', array('send'));
			}
		}
		$Dispatch->loadRoutes = $this->loadRoutes;
		$Dispatch->parseParams(new CakeEvent('ControllerTestCase', $Dispatch, array('request' => $request)));
		if (!isset($request->params['controller']) && Router::currentRoute()) {
			$this->headers = Router::currentRoute()->response->header();
			return;
		}
		if ($this->_dirtyController) {
			$this->controller = null;
		}

		$plugin = empty($request->params['plugin']) ? '' : Inflector::camelize($request->params['plugin']) . '.';
		if ($this->controller === null && $this->autoMock) {
			$this->generate($plugin . Inflector::camelize($request->params['controller']));
		}
		$params = array();
		if ($options['return'] === 'result') {
			$params['return'] = 1;
			$params['bare'] = 1;
			$params['requested'] = 1;
		}
		$Dispatch->testController = $this->controller;
		$Dispatch->response = $this->getMock('CakeResponse', array('send'));
		$this->result = $Dispatch->dispatch($request, $Dispatch->response, $params);
		$this->controller = $Dispatch->testController;
		$this->vars = $this->controller->viewVars;
		$this->contents = $this->controller->response->body();
		if (isset($this->controller->View)) {
			$this->view = $this->controller->View->fetch('__view_no_layout__');
		}
		$this->_dirtyController = true;
		$this->headers = $Dispatch->response->header();

		$_GET = $restore['get'];
		$_POST = $restore['post'];

		return $this->{$options['return']};
	}

/**
 * Generates a mocked controller and mocks any classes passed to `$mocks`. By
 * default, `_stop()` is stubbed as is sending the response headers, so to not
 * interfere with testing.
 *
 * ### Mocks:
 *
 * - `methods` Methods to mock on the controller. `_stop()` is mocked by default
 * - `models` Models to mock. Models are added to the ClassRegistry so they any
 *   time they are instantiated the mock will be created. Pass as key value pairs
 *   with the value being specific methods on the model to mock. If `true` or
 *   no value is passed, the entire model will be mocked.
 * - `components` Components to mock. Components are only mocked on this controller
 *   and not within each other (i.e., components on components)
 *
 * @param string $controller Controller name
 * @param array $mocks List of classes and methods to mock
 * @return Controller Mocked controller
 * @throws MissingControllerException When controllers could not be created.
 * @throws MissingComponentException When components could not be created.
 */
	public function generate($controller, $mocks = array()) {
		list($plugin, $controller) = pluginSplit($controller);
		if ($plugin) {
			App::uses($plugin . 'AppController', $plugin . '.Controller');
			$plugin .= '.';
		}
		App::uses($controller . 'Controller', $plugin . 'Controller');
		if (!class_exists($controller . 'Controller')) {
			throw new MissingControllerException(array(
				'class' => $controller . 'Controller',
				'plugin' => substr($plugin, 0, -1)
			));
		}
		ClassRegistry::flush();

		$mocks = array_merge_recursive(array(
			'methods' => array('_stop'),
			'models' => array(),
			'components' => array()
		), (array)$mocks);

		list($plugin, $name) = pluginSplit($controller);
		$controllerObj = $this->getMock($name . 'Controller', $mocks['methods'], array(), '', false);
		$controllerObj->name = $name;
		$request = $this->getMock('CakeRequest');
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$controllerObj->__construct($request, $response);
		$controllerObj->Components->setController($controllerObj);

		$config = ClassRegistry::config('Model');
		foreach ($mocks['models'] as $model => $methods) {
			if (is_string($methods)) {
				$model = $methods;
				$methods = true;
			}
			if ($methods === true) {
				$methods = array();
			}
			$this->getMockForModel($model, $methods, $config);
		}

		foreach ($mocks['components'] as $component => $methods) {
			if (is_string($methods)) {
				$component = $methods;
				$methods = true;
			}
			if ($methods === true) {
				$methods = array();
			}
			list($plugin, $name) = pluginSplit($component, true);
			$componentClass = $name . 'Component';
			App::uses($componentClass, $plugin . 'Controller/Component');
			if (!class_exists($componentClass)) {
				throw new MissingComponentException(array(
					'class' => $componentClass
				));
			}
			$config = isset($controllerObj->components[$component]) ? $controllerObj->components[$component] : array();
			$componentObj = $this->getMock($componentClass, $methods, array($controllerObj->Components, $config));
			$controllerObj->Components->set($name, $componentObj);
			$controllerObj->Components->enable($name);
		}

		$controllerObj->constructClasses();
		$this->_dirtyController = false;

		$this->controller = $controllerObj;
		return $this->controller;
	}

}
