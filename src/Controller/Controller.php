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
namespace Cake\Controller;

use Cake\Controller\Exception\MissingActionException;
use Cake\Controller\Exception\PrivateActionException;
use Cake\Event\Event;
use Cake\Event\EventListener;
use Cake\Event\EventManagerTrait;
use Cake\Log\LogTrait;
use Cake\Model\ModelAwareTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\RequestActionTrait;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Utility\MergeVariablesTrait;
use Cake\View\ViewVarsTrait;
use LogicException;

/**
 * Application controller class for organization of business logic.
 * Provides basic functionality, such as rendering views inside layouts,
 * automatic model availability, redirection, callbacks, and more.
 *
 * Controllers should provide a number of 'action' methods. These are public
 * methods on a controller that are not inherited from `Controller`.
 * Each action serves as an endpoint for performing a specific action on a
 * resource or collection of resources. For example adding or editing a new
 * object, or listing a set of objects.
 *
 * You can access request parameters, using `$this->request`. The request object
 * contains all the POST, GET and FILES that were part of the request.
 *
 * After performing the required action, controllers are responsible for
 * creating a response. This usually takes the form of a generated `View`, or
 * possibly a redirection to another URL. In either case `$this->response`
 * allows you to manipulate all aspects of the response.
 *
 * Controllers are created by `Dispatcher` based on request parameters and
 * routing. By default controllers and actions use conventional names.
 * For example `/posts/index` maps to `PostsController::index()`. You can re-map
 * URLs using Router::connect() or RouterBuilder::connect().
 *
 * ### Life cycle callbacks
 *
 * CakePHP fires a number of life cycle callbacks during each request.
 * By implementing a method you can receive the related events. The available
 * callbacks are:
 *
 * - `beforeFilter(Event $event)`
 *   Called before each action. This is a good place to do general logic that
 *   applies to all actions.
 * - `beforeRender(Event $event)`
 *   Called before the view is rendered.
 * - `beforeRedirect(Event $event, $url, Response $response)`
 *    Called before a redirect is done.
 * - `afterFilter(Event $event)`
 *   Called after each action is complete and after the view is rendered.
 *
 * @property      \Cake\Controller\Component\AuthComponent $Auth
 * @property      \Cake\Controller\Component\CookieComponent $Cookie
 * @property      \Cake\Controller\Component\CsrfComponent $Csrf
 * @property      \Cake\Controller\Component\PaginatorComponent $Paginator
 * @property      \Cake\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property      \Cake\Controller\Component\SecurityComponent $Security
 * @property      \Cake\Controller\Component\SessionComponent $Session
 * @link          http://book.cakephp.org/3.0/en/controllers.html
 */
class Controller implements EventListener {

	use EventManagerTrait;
	use LogTrait;
	use MergeVariablesTrait;
	use ModelAwareTrait;
	use RequestActionTrait;
	use ViewVarsTrait;

/**
 * The name of this controller. Controller names are plural, named after the model they manipulate.
 *
 * Set automatically using conventions in Controller::__construct().
 *
 * @var string
 * @link http://book.cakephp.org/2.0/en/controllers.html#controller-attributes
 */
	public $name = null;

/**
 * An array containing the names of helpers this controller uses. The array elements should
 * not contain the "Helper" part of the class name.
 *
 * Example: `public $helpers = ['Form', 'Html', 'Time'];`
 *
 * @var mixed
 * @link http://book.cakephp.org/2.0/en/controllers.html#components-helpers-and-uses
 */
	public $helpers = array();

/**
 * An instance of a Cake\Network\Request object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request.
 *
 * @var \Cake\Network\Request
 * @link http://book.cakephp.org/2.0/en/controllers/request-response.html#Request
 */
	public $request;

/**
 * An instance of a Response object that contains information about the impending response
 *
 * @var \Cake\Network\Response
 * @link http://book.cakephp.org/2.0/en/controllers/request-response.html#cakeresponse
 */
	public $response;

/**
 * The class name to use for creating the response object.
 *
 * @var string
 */
	protected $_responseClass = 'Cake\Network\Response';

/**
 * Settings for pagination.
 *
 * Used to pre-configure pagination preferences for the various
 * tables your controller will be paginating.
 *
 * @var array
 * @see \Cake\Controller\Component\PaginatorComponent
 */
	public $paginate = [];

/**
 * Set to true to automatically render the view
 * after action logic.
 *
 * @var bool
 */
	public $autoRender = true;

/**
 * Instance of ComponentRegistry used to create Components
 *
 * @var \Cake\Controller\ComponentRegistry
 */
	protected $_components = null;

/**
 * Array containing the names of components this controller uses. Component names
 * should not contain the "Component" portion of the class name.
 *
 * Example: `public $components = array('Session', 'RequestHandler', 'Acl');`
 *
 * @var array
 * @link http://book.cakephp.org/2.0/en/controllers/components.html
 */
	public $components = array();

/**
 * The name of the View class this controller sends output to.
 *
 * @var string
 */
	public $viewClass = 'Cake\View\View';

/**
 * The path to this controllers view templates.
 * Example `Articles`
 *
 * Set automatically using conventions in Controller::__construct().
 *
 * @var string
 */
	public $viewPath;

/**
 * The name of the view file to render. The name specified
 * is the filename in /app/Template/<SubFolder> without the .ctp extension.
 *
 * @var string
 */
	public $view = null;

/**
 * Instance of the View created during rendering. Won't be set until after
 * Controller::render() is called.
 *
 * @var \Cake\View\View
 */
	public $View;

/**
 * These Controller properties will be passed from the Controller to the View as options.
 *
 * @var array
 * @see \Cake\View\View
 */
	protected $_validViewOptions = [
		'viewVars', 'autoLayout', 'helpers', 'view', 'layout', 'name', 'theme', 'layoutPath',
		'viewPath', 'plugin', 'passedArgs'
	];

/**
 * Automatically set to the name of a plugin.
 *
 * @var string
 */
	public $plugin = null;

/**
 * Holds all passed params.
 *
 * @var mixed
 */
	public $passedArgs = array();

/**
 * Holds current methods of the controller. This is a list of all the methods reachable
 * via URL. Modifying this array, will allow you to change which methods can be reached.
 *
 * @var array
 */
	public $methods = array();

/**
 * Constructor.
 *
 * Sets a number of properties based on conventions if they are empty. To override the
 * conventions CakePHP uses you can define properties in your class declaration.
 *
 * @param \Cake\Network\Request $request Request object for this controller. Can be null for testing,
 *   but expect that features that use the request parameters will not work.
 * @param \Cake\Network\Response $response Response object for this controller.
 * @param string $name Override the name useful in testing when using mocks.
 * @param \Cake\Event\EventManager $eventManager The event manager. Defaults to a new instance.
 */
	public function __construct(Request $request = null, Response $response = null, $name = null, $eventManager = null) {
		if ($this->name === null && $name === null) {
			list(, $name) = namespaceSplit(get_class($this));
			$name = substr($name, 0, -10);
		}
		if ($name !== null) {
			$this->name = $name;
		}

		if (!$this->viewPath) {
			$viewPath = $this->name;
			if (isset($request->params['prefix'])) {
				$viewPath = Inflector::camelize($request->params['prefix']) . DS . $viewPath;
			}
			$this->viewPath = $viewPath;
		}

		$childMethods = get_class_methods($this);
		$baseMethods = get_class_methods('Cake\Controller\Controller');
		$this->methods = array_diff($childMethods, $baseMethods);

		if ($request instanceof Request) {
			$this->setRequest($request);
		}
		if ($response instanceof Response) {
			$this->response = $response;
		}
		if ($eventManager) {
			$this->eventManager($eventManager);
		}

		$this->modelFactory('Table', ['Cake\ORM\TableRegistry', 'get']);
		$modelClass = ($this->plugin ? $this->plugin . '.' : '') . $this->name;
		$this->_setModelClass($modelClass);

		$this->initialize();

		$this->_mergeControllerVars();
		$this->_loadComponents();
		$this->eventManager()->attach($this);
	}

/**
 * Initialization hook method.
 *
 * Implement this method to avoid having to overwrite
 * the constructor and call parent.
 *
 * @return void
 */
	public function initialize() {
	}

/**
 * Get the component registry for this controller.
 *
 * @return \Cake\Controller\ComponentRegistry
 */
	public function components() {
		if ($this->_components === null) {
			$this->_components = new ComponentRegistry($this);
		}
		return $this->_components;
	}

/**
 * Alias for loadComponent() for backwards compatibility.
 *
 * @param string $name The name of the component to load.
 * @param array $config The config for the component.
 * @return \Cake\Controller\Component
 * @deprecated 3.0.0 Use loadComponent() instead.
 */
	public function addComponent($name, array $config = []) {
		trigger_error(
			'addComponent() is deprecated, use loadComponent() instead.',
			E_USER_DEPRECATED
		);
		return $this->loadComponent($name, $config);
	}

/**
 * Add a component to the controller's registry.
 *
 * This method will also set the component to a property.
 * For example:
 *
 * `$this->loadComponent('Acl.Acl');`
 *
 * Will result in a `Toolbar` property being set.
 *
 * @param string $name The name of the component to load.
 * @param array $config The config for the component.
 * @return \Cake\Controller\Component
 */
	public function loadComponent($name, array $config = []) {
		list(, $prop) = pluginSplit($name);
		$this->{$prop} = $this->components()->load($name, $config);
		return $this->{$prop};
	}

/**
 * Magic accessor for model autoloading.
 *
 * @param string $name Property name
 * @return bool|object The model instance or false
 */
	public function __get($name) {
		list($plugin, $class) = pluginSplit($this->modelClass, true);
		if ($class !== $name) {
			return false;
		}
		return $this->loadModel($plugin . $class);
	}

/**
 * Sets the request objects and configures a number of controller properties
 * based on the contents of the request. Controller acts as a proxy for certain View variables
 * which must also be updated here. The properties that get set are:
 *
 * - $this->request - To the $request parameter
 * - $this->plugin - To the $request->params['plugin']
 * - $this->autoRender - To false if $request->params['return'] == 1
 * - $this->passedArgs - The the combined results of params['named'] and params['pass]
 * - View::$passedArgs - $this->passedArgs
 * - View::$plugin - $this->plugin
 * - View::$view - To the $request->params['action']
 * - View::$autoLayout - To the false if $request->params['bare']; is set.
 *
 * @param \Cake\Network\Request $request Request instance.
 * @return void
 */
	public function setRequest(Request $request) {
		$this->request = $request;
		$this->plugin = isset($request->params['plugin']) ? $request->params['plugin'] : null;
		$this->view = isset($request->params['action']) ? $request->params['action'] : null;

		if (isset($request->params['pass'])) {
			$this->passedArgs = $request->params['pass'];
		}
		if (!empty($request->params['return']) && $request->params['return'] == 1) {
			$this->autoRender = false;
		}
		if (!empty($request->params['bare'])) {
			$this->autoLayout = false;
		}
	}

/**
 * Dispatches the controller action. Checks that the action
 * exists and isn't private.
 *
 * @return mixed The resulting response.
 * @throws \LogicException When request is not set.
 * @throws \Cake\Controller\Exception\PrivateActionException When actions are not public or prefixed by _
 * @throws \Cake\Controller\Exception\MissingActionException When actions are not defined.
 */
	public function invokeAction() {
		try {
			$request = $this->request;
			if (!isset($request)) {
				throw new LogicException('No Request object configured. Cannot invoke action');
			}
			$method = new \ReflectionMethod($this, $request->params['action']);
			if ($this->_isPrivateAction($method, $request)) {
				throw new PrivateActionException(array(
					'controller' => $this->name . "Controller",
					'action' => $request->params['action'],
					'prefix' => isset($request->params['prefix']) ? $request->params['prefix'] : '',
					'plugin' => $request->params['plugin'],
				));
			}
			return $method->invokeArgs($this, $request->params['pass']);

		} catch (\ReflectionException $e) {
			throw new MissingActionException(array(
				'controller' => $this->name . "Controller",
				'action' => $request->params['action'],
				'prefix' => isset($request->params['prefix']) ? $request->params['prefix'] : '',
				'plugin' => $request->params['plugin'],
			));
		}
	}

/**
 * Check if the request's action is a public method.
 *
 * @param \ReflectionMethod $method The method to be invoked.
 * @return bool
 */
	protected function _isPrivateAction(\ReflectionMethod $method) {
		return (
			!$method->isPublic() ||
			!in_array($method->name, $this->methods)
		);
	}

/**
 * Merge components, helpers vars from
 * parent classes.
 *
 * @return void
 */
	protected function _mergeControllerVars() {
		$this->_mergeVars(
			['components', 'helpers'],
			['associative' => ['components', 'helpers']]
		);
	}

/**
 * Returns a list of all events that will fire in the controller during it's lifecycle.
 * You can override this function to add you own listener callbacks
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Controller.initialize' => 'beforeFilter',
			'Controller.beforeRender' => 'beforeRender',
			'Controller.beforeRedirect' => 'beforeRedirect',
			'Controller.shutdown' => 'afterFilter',
		);
	}

/**
 * No-op for backwards compatibility.
 *
 * The code that used to live here is now in Controller::__construct().
 *
 * @deprecated 3.0.0 Will be removed in 3.0.0.
 * @return void
 */
	public function constructClasses() {
		trigger_error(
			'Controller::constructClasses() is deprecated and will be removed in the first RC release',
			E_USER_DEPRECATED
		);
	}

/**
 * Loads the defined components using the Component factory.
 *
 * @return void
 */
	protected function _loadComponents() {
		if (empty($this->components)) {
			return;
		}
		$registry = $this->components();
		$components = $registry->normalizeArray($this->components);
		foreach ($components as $properties) {
			$this->loadComponent($properties['class'], $properties['config']);
		}
	}

/**
 * Perform the startup process for this controller.
 * Fire the Components and Controller callbacks in the correct order.
 *
 * - Initializes components, which fires their `initialize` callback
 * - Calls the controller `beforeFilter`.
 * - triggers Component `startup` methods.
 *
 * @return void|\Cake\Network\Response
 */
	public function startupProcess() {
		$event = $this->dispatchEvent('Controller.initialize');
		if ($event->result instanceof Response) {
			return $event->result;
		}
		$event = $this->dispatchEvent('Controller.startup');
		if ($event->result instanceof Response) {
			return $event->result;
		}
	}

/**
 * Perform the various shutdown processes for this controller.
 * Fire the Components and Controller callbacks in the correct order.
 *
 * - triggers the component `shutdown` callback.
 * - calls the Controller's `afterFilter` method.
 *
 * @return void|\Cake\Network\Response
 */
	public function shutdownProcess() {
		$event = $this->dispatchEvent('Controller.shutdown');
		if ($event->result instanceof Response) {
			return $event->result;
		}
	}

/**
 * Redirects to given $url, after turning off $this->autoRender.
 * Script execution is halted after the redirect.
 *
 * @param string|array $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param int $status Optional HTTP status code (eg: 404)
 * @return void|\Cake\Network\Response
 * @link http://book.cakephp.org/3.0/en/controllers.html#Controller::redirect
 */
	public function redirect($url, $status = null) {
		$this->autoRender = false;

		$response = $this->response;
		if ($status && $response->statusCode() === 200) {
			$response->statusCode($status);
		}

		$event = $this->dispatchEvent('Controller.beforeRedirect', [$url, $response]);
		if ($event->result instanceof Response) {
			return $event->result;
		}
		if ($event->isStopped()) {
			return;
		}

		if ($url !== null && !$response->location()) {
			$response->location(Router::url($url, true));
		}

		return $response;
	}

/**
 * Internally redirects one action to another. Does not perform another HTTP request unlike Controller::redirect()
 *
 * Examples:
 *
 * {{{
 * setAction('another_action');
 * setAction('action_with_parameters', $parameter1);
 * }}}
 *
 * @param string $action The new action to be 'redirected' to.
 *   Any other parameters passed to this method will be passed as parameters to the new action.
 * @return mixed Returns the return value of the called action
 */
	public function setAction($action) {
		$this->request->params['action'] = $action;
		$this->view = $action;
		$args = func_get_args();
		unset($args[0]);
		return call_user_func_array(array(&$this, $action), $args);
	}

/**
 * Instantiates the correct view class, hands it its data, and uses it to render the view output.
 *
 * @param string $view View to use for rendering
 * @param string $layout Layout to use
 * @return \Cake\Network\Response A response object containing the rendered view.
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::render
 */
	public function render($view = null, $layout = null) {
		$event = $this->dispatchEvent('Controller.beforeRender');
		if ($event->result instanceof Response) {
			$this->autoRender = false;
			return $event->result;
		}
		if ($event->isStopped()) {
			$this->autoRender = false;
			return $this->response;
		}

		$this->View = $this->createView();

		$this->autoRender = false;
		$this->response->body($this->View->render($view, $layout));
		return $this->response;
	}

/**
 * Returns the referring URL for this request.
 *
 * @param string $default Default URL to use if HTTP_REFERER cannot be read from headers
 * @param bool $local If true, restrict referring URLs to local server
 * @return string Referring URL
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::referer
 */
	public function referer($default = null, $local = false) {
		if (!$this->request) {
			return '/';
		}

		$referer = $this->request->referer($local);
		if ($referer === '/' && $default) {
			return Router::url($default, !$local);
		}
		return $referer;
	}

/**
 * Handles pagination of records in Table objects.
 *
 * Will load the referenced Table object, and have the PaginatorComponent
 * paginate the query using the request date and settings defined in `$this->paginate`.
 *
 * This method will also make the PaginatorHelper available in the view.
 *
 * @param \Cake\ORM\Table|string|\Cake\ORM\Query $object Table to paginate
 * (e.g: Table instance, 'TableName' or a Query object)
 * @return \Cake\ORM\ResultSet Query results
 * @link http://book.cakephp.org/3.0/en/controllers.html#Controller::paginate
 * @throws \RuntimeException When no compatible table object can be found.
 */
	public function paginate($object = null) {
		if (is_object($object)) {
			$table = $object;
		}

		if (is_string($object) || $object === null) {
			$try = [$object, $this->modelClass];
			foreach ($try as $tableName) {
				if (empty($tableName)) {
					continue;
				}
				$table = TableRegistry::get($tableName);
				break;
			}
		}

		$this->loadComponent('Paginator');
		if (empty($table)) {
			throw new \RuntimeException('Unable to locate an object compatible with paginate.');
		}
		return $this->Paginator->paginate($table, $this->paginate);
	}

/**
 * Called before the controller action. You can use this method to configure and customize components
 * or perform logic that needs to happen before each controller action.
 *
 * @param Event $event An Event instance
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeFilter(Event $event) {
	}

/**
 * Called after the controller action is run, but before the view is rendered. You can use this method
 * to perform logic or set view variables that are required on every request.
 *
 * @param Event $event An Event instance
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeRender(Event $event) {
	}

/**
 * The beforeRedirect method is invoked when the controller's redirect method is called but before any
 * further action.
 *
 * If the event is stopped the controller will not continue on to redirect the request.
 * The $url and $status variables have same meaning as for the controller's method.
 * You can set the event result to response instance or modify the redirect location
 * using controller's response instance.
 *
 * @param Event $event An Event instance
 * @param string|array $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param \Cake\Network\Response $response The response object.
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeRedirect(Event $event, $url, Response $response) {
	}

/**
 * Called after the controller action is run and rendered.
 *
 * @param Event $event An Event instance
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function afterFilter(Event $event) {
	}

}
