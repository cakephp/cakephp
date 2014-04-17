<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Controller\Error\MissingComponentException;
use Cake\Controller\Error\MissingControllerException;
use Cake\Core\App;
use Cake\Error;
use Cake\Event\Event;
use Cake\Routing\Dispatcher;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * ControllerTestDispatcher class
 *
 */
class ControllerTestDispatcher extends Dispatcher {

/**
 * The controller to use in the dispatch process
 *
 * @var \Cake\Controller\Controller
 */
	public $testController = null;

/**
 * Use custom routes during tests
 *
 * @var bool
 */
	public $loadRoutes = true;

/**
 * Returns the test controller
 *
 * @param \Cake\Network\Request $request Request object
 * @param \Cake\Network\Response $response Response for the controller.
 * @return \Cake\Controller\Controller
 */
	protected function _getController($request, $response) {
		if ($this->testController === null) {
			$this->testController = parent::_getController($request, $response);
		}
		$default = array('InterceptContent' => array('className' => 'Cake\TestSuite\InterceptContentHelper'));
		$this->testController->helpers = array_merge($default, $this->testController->helpers);
		$this->testController->setRequest($request);
		$this->testController->response = $this->response;
		$registry = $this->testController->components();
		foreach ($registry->loaded() as $component) {
			$object = $registry->{$component};
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
 */
class InterceptContentHelper extends Helper {

/**
 * Intercepts and stores the contents of the view before the layout is rendered
 *
 * @param string $viewFile The view file
 * @return void
 */
	public function afterRender($viewFile) {
		$this->_View->assign('__view_no_layout__', $this->_View->fetch('content'));
		$this->_View->helpers()->unload('InterceptContent');
	}

}

/**
 * ControllerTestCase class
 *
 */
abstract class ControllerTestCase extends TestCase {

/**
 * The controller to test in testAction
 *
 * @var \Cake\Controller\Controller
 */
	public $controller = null;

/**
 * Automatically mock controllers that aren't mocked
 *
 * @var bool
 */
	public $autoMock = true;

/**
 * Use custom routes during tests
 *
 * @var bool
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
 * @var bool
 */
	protected $_dirtyController = false;

/**
 * Used to enable calling ControllerTestCase::testAction() without the testing
 * framework thinking that it's a test case
 *
 * @param string $name The name of the function
 * @param array $arguments Array of arguments
 * @return the return of _testAction
 * @throws \Cake\Error\BadMethodCallException when you call methods that don't exist.
 */
	public function __call($name, $arguments) {
		if ($name === 'testAction') {
			return call_user_func_array(array($this, '_testAction'), $arguments);
		}
		throw new Error\BadMethodCallException("Method '{$name}' does not exist.");
	}

/**
 * Lets you do functional tests of a controller action.
 *
 * ### Options:
 *
 * - `data` The data to use for POST or PUT requests. If `method` is GET
 *   and `query` is empty, the data key will be used as GET parameters. By setting
 *   `data to a string you can simulate XML or JSON payloads allowing you to test
 *   REST webservices.
 * - `query` The query string parameters to set.
 * - `cookies` The cookie data to use for the request.
 * - `method` POST or GET. Defaults to GET.
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

		$options += array(
			'query' => array(),
			'data' => array(),
			'cookies' => array(),
			'method' => 'GET',
			'return' => 'result'
		);

		$method = strtoupper($options['method']);
		$_SERVER['REQUEST_METHOD'] = $method;

		if ($method === 'GET' && is_array($options['data']) && empty($options['query'])) {
			$options['query'] = $options['data'];
			$options['data'] = array();
		}
		$requestData = array(
			'url' => $url,
			'cookies' => $options['cookies'],
			'query' => $options['query'],
		);
		if (is_array($options['data'])) {
			$requestData['post'] = $options['data'];
		}

		$request = $this->getMock(
			'Cake\Network\Request',
			array('_readInput'),
			array($requestData)
		);

		if (is_string($options['data'])) {
			$request->expects($this->any())
				->method('_readInput')
				->will($this->returnValue($options['data']));
		}

		$Dispatch = new ControllerTestDispatcher();
		$Dispatch->loadRoutes = $this->loadRoutes;
		$Dispatch->parseParams(new Event('ControllerTestCase', $Dispatch, array('request' => $request)));
		if (!isset($request->params['controller']) && Router::getRequest()) {
			$this->headers = Router::getRequest()->response->header();
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
		$Dispatch->response = $this->getMock('Cake\Network\Response', array('send', 'stop'));
		$this->result = $Dispatch->dispatch($request, $Dispatch->response, $params);
		$this->controller = $Dispatch->testController;
		$this->vars = $this->controller->viewVars;
		$this->contents = $this->controller->response->body();
		if (isset($this->controller->View)) {
			$this->view = $this->controller->View->fetch('__view_no_layout__');
		}
		$this->_dirtyController = true;
		$this->headers = $Dispatch->response->header();

		return $this->{$options['return']};
	}

/**
 * Generates a mocked controller and mocks any classes passed to `$mocks`. By
 * default, `stop()` is stubbed as is sending the response headers, so to not
 * interfere with testing.
 *
 * ### Mocks:
 *
 * - `methods` Methods to mock on the controller.
 * - `models` Models to mock. Models are added to the ClassRegistry so any
 *   time they are instantiated the mock will be created. Pass as key value pairs
 *   with the value being specific methods on the model to mock. If `true` or
 *   no value is passed, the entire model will be mocked.
 * - `components` Components to mock. Components are only mocked on this controller
 *   and not within each other (i.e., components on components)
 *
 * @param string $controller Controller name
 * @param array $mocks List of classes and methods to mock
 * @return \Cake\Controller\Controller Mocked controller
 * @throws \Cake\Controller\Error\MissingControllerException When controllers could not be created.
 * @throws \Cake\Controller\Error\MissingComponentException When components could not be created.
 */
	public function generate($controller, array $mocks = array()) {
		$classname = App::classname($controller, 'Controller', 'Controller');
		if (!$classname) {
			list($plugin, $controller) = pluginSplit($controller);
			throw new MissingControllerException(array(
				'class' => $controller . 'Controller',
				'plugin' => $plugin
			));
		}

		$mocks = array_merge(array(
			'methods' => null,
			'models' => array(),
			'components' => array()
		), $mocks);
		list(, $controllerName) = namespaceSplit($classname);
		$name = substr($controllerName, 0, -10);

		$request = $this->getMock('Cake\Network\Request');
		$response = $this->getMock('Cake\Network\Response', array('_sendHeader', 'stop'));
		$controller = $this->getMock(
			$classname,
			$mocks['methods'],
			array($request, $response, $name)
		);

		foreach ($mocks['models'] as $model => $methods) {
			if (is_string($methods)) {
				$model = $methods;
				$methods = true;
			}
			if ($methods === true) {
				$methods = array();
			}
			$this->getMockForModel($model, $methods);
		}

		foreach ($mocks['components'] as $component => $methods) {
			if (is_string($methods)) {
				$component = $methods;
				$methods = true;
			}
			if ($methods === true) {
				$methods = array();
			}
			$componentClass = App::classname($component, 'Controller/Component', 'Component');
			list(, $name) = pluginSplit($component, true);
			if (!$componentClass) {
				throw new MissingComponentException(array(
					'class' => $name . 'Component'
				));
			}
			$registry = $controller->components();

			$config = isset($controller->components[$component]) ? $controller->components[$component] : array();
			$component = $this->getMock($componentClass, $methods, array($registry, $config));
			$registry->set($name, $component);
		}

		$controller->constructClasses();
		$this->_dirtyController = false;

		$this->controller = $controller;
		return $this->controller;
	}

}
