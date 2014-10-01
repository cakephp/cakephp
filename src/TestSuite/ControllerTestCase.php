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
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\DispatcherFilter;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * StubControllerFilter
 *
 * A test harness dispatcher filter that allows
 * pre-generated controllers to be injected into the dispatch cycle.
 *
 */
class StubControllerFilter extends DispatcherFilter {

/**
 * Attempts to always run last.
 *
 * @var int
 */
	protected $_priority = 9999;

/**
 * The controller to use in the dispatch process
 *
 * @var \Cake\Controller\Controller
 */
	public $testController;

/**
 * Response stub to apply on the controller.
 *
 * @var \Cake\Network\Response
 */
	public $response;

/**
 * Returns the test controller
 *
 * @param \Cake\Event\Event $event The event object.
 * @return void
 */
	public function beforeDispatch(Event $event) {
		if (empty($event->data['controller'])) {
			return;
		}
		if ($this->testController !== null) {
			$event->data['controller'] = $this->testController;
		}
		$request = $event->data['request'];
		$response = $event->data['response'];
		$controller = $event->data['controller'];

		$default = array(
			'InterceptContent' => array(
				'className' => 'Cake\TestSuite\InterceptContentHelper'
			)
		);
		$controller->helpers = array_merge($default, $controller->helpers);
		$controller->setRequest($request);
		$controller->response = $this->response;
		$registry = $controller->components();
		foreach ($registry->loaded() as $component) {
			$object = $registry->{$component};
			if (isset($object->response)) {
				$object->response = $response;
			}
			if (isset($object->request)) {
				$object->request = $request;
			}
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
 * @deprecated
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
 * The default session object to use
 *
 * @var \Cake\Network\Session
 */
	protected $_session;

/**
 * Used to enable calling ControllerTestCase::testAction() without the testing
 * framework thinking that it's a test case
 *
 * @param string $name The name of the function
 * @param array $arguments Array of arguments
 * @return the return of _testAction
 * @throws \BadMethodCallException when you call methods that don't exist.
 */
	public function __call($name, $arguments) {
		if ($name === 'testAction') {
			return call_user_func_array(array($this, '_testAction'), $arguments);
		}
		throw new \BadMethodCallException("Method '{$name}' does not exist.");
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
		$this->_session = $this->_session ?: new Session();

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
			'session' => $this->_session
		);
		if (is_array($options['data'])) {
			$requestData['post'] = $options['data'];
		}

		$request = $this->getMock(
			'Cake\Network\Request',
			array('_readInput', 'method'),
			array($requestData)
		);

		$request->expects($this->any())
			->method('method')
			->will($this->returnValue($method));

		if (is_string($options['data'])) {
			$request->expects($this->any())
				->method('_readInput')
				->will($this->returnValue($options['data']));
		}

		if ($this->loadRoutes) {
			Router::reload();
		}
		$request->addParams(Router::parse($request->url));

		// Handle redirect routes.
		if (!isset($request->params['controller']) && Router::getRequest()) {
			$this->headers = Router::getRequest()->response->header();
			return;
		}

		$stubFilter = new StubControllerFilter();
		$dispatch = DispatcherFactory::create();
		$dispatch->addFilter($stubFilter);

		if ($this->_dirtyController) {
			$this->controller = null;
		}
		if ($this->controller === null && $this->autoMock) {
			$plugin = '';
			if (!empty($request->params['plugin'])) {
				$plugin = $request->params['plugin'] . '.';
			}
			$controllerName = $request->params['controller'];
			if (!empty($request->params['prefix'])) {
				$controllerName = Inflector::camelize($request->params['prefix']) . '/' . $controllerName;
			}
			$this->generate($plugin . $controllerName, [], $request);
		}
		$params = array();
		if ($options['return'] === 'result') {
			$params['return'] = 1;
			$params['bare'] = 1;
			$params['requested'] = 1;
		}
		$request->addParams($params);

		$response = $this->getMock('Cake\Network\Response', array('send', 'stop'));
		$stubFilter->response = $response;
		$stubFilter->testController = $this->controller;
		$this->result = $dispatch->dispatch($request, $response);

		$this->controller = $stubFilter->testController;
		$this->vars = $this->controller->viewVars;
		$this->contents = $this->controller->response->body();
		if (isset($this->controller->View)) {
			$this->view = $this->controller->View->fetch('__view_no_layout__');
		}
		$this->_dirtyController = true;
		$this->headers = $response->header();

		return $this->{$options['return']};
	}

/**
 * Generates a mocked controller and mocks any classes passed to `$mocks`.
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
 * @param \Cake\Network\Request $request A request object to build the controller with.
 *   This parameter is required when mocking prefixed controllers.
 * @return \Cake\Controller\Controller Mocked controller
 * @throws \Cake\Controller\Error\MissingControllerException When controllers could not be created.
 * @throws \Cake\Controller\Error\MissingComponentException When components could not be created.
 */
	public function generate($controller, array $mocks = array(), Request $request = null) {
		$className = App::className($controller, 'Controller', 'Controller');
		if (!$className) {
			list($plugin, $controller) = pluginSplit($controller);
			throw new MissingControllerException(array(
				'class' => $controller . 'Controller',
				'plugin' => $plugin
			));
		}

		$mocks += [
			'methods' => null,
			'models' => [],
			'components' => []
		];
		list(, $controllerName) = namespaceSplit($className);
		$name = substr($controllerName, 0, -10);

		$this->_session = $this->_session ?: new Session();
		$request = $request ?: $this->getMock(
			'Cake\Network\Request',
			['_readInput', 'method'],
			[['session' => $this->_session]]
		);

		$response = $this->getMock('Cake\Network\Response', array('_sendHeader', 'stop'));
		$controller = $this->getMock(
			$className,
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
			$componentClass = App::className($component, 'Controller/Component', 'Component');
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
			$controller->{$name} = $component;
		}

		$this->_dirtyController = false;
		$this->controller = $controller;
		return $this->controller;
	}

}
