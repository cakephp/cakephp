<?php
/**
 * ControllerTestCase file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

require_once CAKE . 'libs' . DS . 'dispatcher.php';
require_once CAKE_TESTS_LIB . 'cake_test_case.php';
App::import('Core', array('Router', 'CakeRequest', 'CakeResponse', 'Helper'));

/**
 * ControllerTestDispatcher class
 *
 * @package       cake.tests.lib
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
	function _getController($request) {
		if ($this->testController === null) {
			$this->testController = parent::_getController($request);
		}
		$this->testController->helpers = array_merge(array('InterceptContent'), $this->testController->helpers);
		$this->testController->setRequest($request);
		$this->testController->response = $this->response;
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
 * @package       cake.tests.lib
 */
class InterceptContentHelper extends Helper {

/**
 * Intercepts and stores the contents of the view before the layout is rendered
 *
 * @param string $viewFile The view file
 */
	function afterRender($viewFile) {
		$this->_View->_viewNoLayout = $this->_View->output;
		$this->_View->Helpers->unload('InterceptContent');
	}
}

/**
 * ControllerTestCase class
 *
 * @package       cake.tests.lib
 */
class ControllerTestCase extends CakeTestCase {

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
	public $autoMock = false;

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
 * Used to enable calling ControllerTestCase::testAction() without the testing
 * framework thinking that it's a test case
 *
 * @param string $name The name of the function
 * @param array $arguments Array of arguments
 * @return Function
 */
	public function __call($name, $arguments) {
		if ($name == 'testAction') {
			return call_user_func_array(array($this, '_testAction'), $arguments);
		}
	}

/**
 * Tests a controller action.
 *
 * ### Options:
 * - `data` POST or GET data to pass
 * - `method` POST or GET
 *
 * @param string $url The url to test
 * @param array $options See options
 */
	private function _testAction($url = '', $options = array()) {
		$this->vars = $this->result = $this->view = $this->contents = $this->headers = null;

		$options = array_merge(array(
			'data' => array(),
			'method' => 'POST',
			'return' => 'result'
		), $options);

		if (strtoupper($options['method']) == 'GET') {
			$_GET = $options['data'];
			$_POST = array();
		} else {
			$_POST = array('data' => $options['data']);
			$_GET = array();
		}
		$request = new CakeRequest($url);
		$Dispatch = new ControllerTestDispatcher();
		foreach (Router::$routes as $route) {
			if (is_a($route, 'RedirectRoute')) {
				$route->response = $this->getMock('CakeResponse', array('send'));
			}
		}
		$Dispatch->loadRoutes = $this->loadRoutes;
		$request = $Dispatch->parseParams($request);
		if (!isset($request->params['controller'])) {
			$this->headers = Router::currentRoute()->response->header();
			return;
		}
		if ($this->controller !== null && Inflector::camelize($request->params['controller']) !== $this->controller->name) {
			$this->controller = null;
		}
		if ($this->controller === null && $this->autoMock) {
			$this->generate(Inflector::camelize($request->params['controller']));
		}
		$params = array();
		if ($options['return'] == 'result') {
			$params['return'] = 1;
			$params['bare'] = 1;
			$params['requested'] = 1;
		}
		$Dispatch->testController = $this->controller;
		$Dispatch->response = $this->getMock('CakeResponse', array('send'));
		$this->result = $Dispatch->dispatch($request, $params);
		$this->controller = $Dispatch->testController;
		if ($options['return'] != 'result') {
			$this->vars = $this->controller->View->viewVars;
			$this->view = $this->controller->View->_viewNoLayout;
			$this->contents = $this->controller->response->body();
		}
		$this->headers = $Dispatch->response->header();
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
 *   time they are instatiated the mock will be created. Pass as key value pairs
 *   with the value being specific methods on the model to mock. If `true` or
 *   no value is passed, the entire model will be mocked.
 * - `components` Components to mock. Components are only mocked on this controller
 *   and not within each other (i.e., components on components)
 *
 * @param string $controller Controller name
 * @param array $mocks List of classes and methods to mock
 * @return Controller Mocked controller
 */
	public function generate($controller, $mocks = array()) {
		if (!class_exists($controller.'Controller') && App::import('Controller', $controller) === false) {
			throw new MissingControllerException(array('controller' => $controller.'Controller'));
		}
		ClassRegistry::flush();
		
		$mocks = array_merge_recursive(array(
			'methods' => array('_stop'),
			'models' => array(), 
			'components' => array()
		), (array)$mocks);

		$_controller = $this->getMock($controller.'Controller', $mocks['methods'], array(), '', false);
		$_controller->name = $controller;
		$_controller->__construct();

		$config = ClassRegistry::config('Model');
		foreach ($mocks['models'] as $model => $methods) {
			if (is_string($methods)) {
				$model = $methods;
				$methods = true;
			}
			if ($methods === true) {
				$methods = array();
			}
			$config = array_merge((array)$config, array('name' => $model));
			$_model = $this->getMock($model, $methods, array($config));
			ClassRegistry::removeObject($model);
			ClassRegistry::addObject($model, $_model);
		}

		foreach ($mocks['components'] as $component => $methods) {
			if (is_string($methods)) {
				$component = $methods;
				$methods = true;
			}
			if ($methods === true) {
				$methods = array();
			}
			if (!App::import('Component', $component)) {
				throw new MissingComponentFileException(array(
					'file' => Inflector::underscore($component) . '.php',
					'class' => $componentClass
				));
			}
			$_component = $this->getMock($component.'Component', $methods, array(), '', false);
			$_controller->Components->set($component, $_component);
		}

		$_controller->constructClasses();

		$this->controller = $_controller;
		return $this->controller;
	}
}