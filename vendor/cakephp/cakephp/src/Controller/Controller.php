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
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Object;
use Cake\Core\Plugin;
use Cake\Error;
use Cake\Event\Event;
use Cake\Event\EventListener;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\RequestActionTrait;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Utility\MergeVariablesTrait;
use Cake\Utility\RepositoryAwareTrait;
use Cake\Utility\ViewVarsTrait;
use Cake\View\View;

/**
 * Application controller class for organization of business logic.
 * Provides basic functionality, such as rendering views inside layouts,
 * automatic model availability, redirection, callbacks, and more.
 *
 * Controllers should provide a number of 'action' methods. These are public methods on the controller
 * that are not prefixed with a '_' and not part of Controller. Each action serves as an endpoint for
 * performing a specific action on a resource or collection of resources. For example adding or editing a new
 * object, or listing a set of objects.
 *
 * You can access request parameters, using `$this->request`. The request object contains all the POST, GET and FILES
 * that were part of the request.
 *
 * After performing the required actions, controllers are responsible for creating a response. This usually
 * takes the form of a generated View, or possibly a redirection to another controller action. In either case
 * `$this->response` allows you to manipulate all aspects of the response.
 *
 * Controllers are created by Dispatcher based on request parameters and routing. By default controllers and actions
 * use conventional names. For example `/posts/index` maps to `PostsController::index()`. You can re-map URLs
 * using Router::connect().
 *
 * ### Life cycle callbacks
 *
 * CakePHP fires a number of life cycle callbacks during each request. By implementing a method
 * you can receive the related events. The available callbacks are:
 *
 * - `beforeFilter(Event $event)` - Called before the before each action. This is a good place to
 *   do general logic that applies to all actions.
 * - `beforeRender(Event $event)` - Called before the view is rendered.
 * - `beforeRedirect(Cake\Event\Event $event $url, Cake\Network\Response $response)` - Called before
 *   a redirect is done.
 * - `afterFilter(Event $event)` - Called after each action is complete and after the view is rendered.
 *
 * @property      AclComponent $Acl
 * @property      AuthComponent $Auth
 * @property      CookieComponent $Cookie
 * @property      EmailComponent $Email
 * @property      PaginatorComponent $Paginator
 * @property      RequestHandlerComponent $RequestHandler
 * @property      SecurityComponent $Security
 * @property      SessionComponent $Session
 * @link          http://book.cakephp.org/2.0/en/controllers.html
 */
class Controller extends Object implements EventListener {

	use MergeVariablesTrait;
	use RepositoryAwareTrait;
	use RequestActionTrait;
	use ViewVarsTrait;

/**
 * The name of this controller. Controller names are plural, named after the model they manipulate.
 *
 * @var string
 * @link http://book.cakephp.org/2.0/en/controllers.html#controller-attributes
 */
	public $name = null;

/**
 * An array containing the names of helpers this controller uses. The array elements should
 * not contain the "Helper" part of the class name.
 *
 * Example: `public $helpers = array('Html', 'Js', 'Time', 'Ajax');`
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @link http://book.cakephp.org/2.0/en/controllers.html#components-helpers-and-uses
 */
	public $helpers = array();

/**
 * An instance of a Cake\Network\Request object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request.
 *
 * @var Cake\Network\Request
 * @link http://book.cakephp.org/2.0/en/controllers/request-response.html#Request
 */
	public $request;

/**
 * An instance of a Response object that contains information about the impending response
 *
 * @var Cake\Network\Response
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
 * @see Cake\Controller\Component\PaginatorComponent
 */
	public $paginate = [];

/**
 * The name of the views subfolder containing views for this controller.
 *
 * @var string
 */
	public $viewPath = null;

/**
 * The name of the layouts subfolder containing layouts for this controller.
 *
 * @var string
 */
	public $layoutPath = null;

/**
 * The name of the view file to render. The name specified
 * is the filename in /app/View/<SubFolder> without the .ctp extension.
 *
 * @var string
 */
	public $view = null;

/**
 * The name of the layout file to render the view inside of. The name specified
 * is the filename of the layout in /app/View/Layout without the .ctp
 * extension.
 *
 * @var string
 */
	public $layout = 'default';

/**
 * The view theme to use.
 *
 * @see Cake\View\View::$theme
 * @var string
 */
	public $theme = null;

/**
 * Set to true to automatically render the view
 * after action logic.
 *
 * @var boolean
 */
	public $autoRender = true;

/**
 * Set to true to automatically render the layout around views.
 *
 * @var boolean
 */
	public $autoLayout = true;

/**
 * Instance of ComponentRegistry used to create Components
 *
 * @var ComponentRegistry
 */
	public $Components = null;

/**
 * Array containing the names of components this controller uses. Component names
 * should not contain the "Component" portion of the class name.
 *
 * Example: `public $components = array('Session', 'RequestHandler', 'Acl');`
 *
 * @var array
 * @link http://book.cakephp.org/2.0/en/controllers/components.html
 */
	public $components = array('Session');

/**
 * The name of the View class this controller sends output to.
 *
 * @var string
 */
	public $viewClass = 'Cake\View\View';

/**
 * Instance of the View created during rendering. Won't be set until after
 * Controller::render() is called.
 *
 * @var Cake\View\View
 */
	public $View;

/**
 * File extension for view templates. Defaults to CakePHP's conventional ".ctp".
 *
 * @var string
 */
	public $ext = '.ctp';

/**
 * Automatically set to the name of a plugin.
 *
 * @var string
 */
	public $plugin = null;

/**
 * Used to define methods a controller that will be cached. To cache a
 * single action, the value is set to an array containing keys that match
 * action names and values that denote cache expiration times (in seconds).
 *
 * Example:
 *
 * {{{
 * public $cacheAction = array(
 *		'view/23/' => 21600,
 *		'recalled/' => 86400
 *	);
 * }}}
 *
 * $cacheAction can also be set to a strtotime() compatible string. This
 * marks all the actions in the controller for view caching.
 *
 * @var mixed
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/cache.html#additional-configuration-options
 */
	public $cacheAction = false;

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
 * Holds any validation errors produced by the last call of the validateErrors() method/
 *
 * @var array Validation errors, or false if none
 */
	public $validationErrors = null;

/**
 * Instance of the Cake\Event\EventManager this controller is using
 * to dispatch inner events.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager = null;

/**
 * Constructor.
 *
 * @param Cake\Network\Request $request Request object for this controller. Can be null for testing,
 *  but expect that features that use the request parameters will not work.
 * @param Cake\Network\Response $response Response object for this controller.
 */
	public function __construct($request = null, $response = null) {
		if ($this->name === null) {
			list(, $this->name) = namespaceSplit(get_class($this));
			$this->name = substr($this->name, 0, -10);
		}

		if (!$this->viewPath) {
			$viewPath = $this->name;
			if (isset($request->params['prefix'])) {
				$viewPath = Inflector::camelize($request->params['prefix']) . DS . $viewPath;
			}
			$this->viewPath = $viewPath;
		}

		$this->_setModelClass($this->name);
		$this->repositoryFactory('Table', ['Cake\ORM\TableRegistry', 'get']);

		$childMethods = get_class_methods($this);
		$parentMethods = get_class_methods('Cake\Controller\Controller');

		$this->methods = array_diff($childMethods, $parentMethods);

		if ($request instanceof Request) {
			$this->setRequest($request);
		}
		if ($response instanceof Response) {
			$this->response = $response;
		}
		$this->Components = new ComponentRegistry($this);
	}

/**
 * Provides backwards compatibility to avoid problems with empty and isset to alias properties.
 *
 * @param string $name
 * @return boolean
 */
	public function __get($name) {
		if ($name === $this->modelClass) {
			list($plugin, $class) = pluginSplit($name, true);
			if (!$plugin) {
				$plugin = $this->plugin ? $this->plugin . '.' : null;
			}
			$this->repository($plugin . $this->modelClass);
			return $this->{$this->modelClass};
		}
		return false;
	}

/**
 * Sets the request objects and configures a number of controller properties
 * based on the contents of the request. The properties that get set are
 *
 * - $this->request - To the $request parameter
 * - $this->plugin - To the $request->params['plugin']
 * - $this->view - To the $request->params['action']
 * - $this->autoLayout - To the false if $request->params['bare']; is set.
 * - $this->autoRender - To false if $request->params['return'] == 1
 * - $this->passedArgs - The the combined results of params['named'] and params['pass]
 *
 * @param Cake\Network\Request $request
 * @return void
 */
	public function setRequest(Request $request) {
		$this->request = $request;
		$this->plugin = isset($request->params['plugin']) ? Inflector::camelize($request->params['plugin']) : null;
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
 * @param Cake\Network\Request $request
 * @return mixed The resulting response.
 * @throws Cake\Error\PrivateActionException When actions are not public or prefixed by _
 * @throws Cake\Error\MissingActionException When actions are not defined and scaffolding is
 *    not enabled.
 */
	public function invokeAction(Request $request) {
		try {
			$method = new \ReflectionMethod($this, $request->params['action']);

			if ($this->_isPrivateAction($method, $request)) {
				throw new Error\PrivateActionException(array(
					'controller' => $this->name . "Controller",
					'action' => $request->params['action'],
					'prefix' => isset($request->params['prefix']) ? $request->params['prefix'] : '',
					'plugin' => $request->params['plugin'],
				));
			}
			return $method->invokeArgs($this, $request->params['pass']);

		} catch (\ReflectionException $e) {
			throw new Error\MissingActionException(array(
				'controller' => $this->name . "Controller",
				'action' => $request->params['action'],
				'prefix' => isset($request->params['prefix']) ? $request->params['prefix'] : '',
				'plugin' => $request->params['plugin'],
			));
		}
	}

/**
 * Check if the request's action is marked as private, with an underscore,
 * or if the request is attempting to directly accessing a prefixed action.
 *
 * @param \ReflectionMethod $method The method to be invoked.
 * @param Cake\Network\Request $request The request to check.
 * @return boolean
 */
	protected function _isPrivateAction(\ReflectionMethod $method, Request $request) {
		$privateAction = (
			$method->name[0] === '_' ||
			!$method->isPublic() ||
			!in_array($method->name, $this->methods)
		);
		$prefixes = Router::prefixes();

		if (!$privateAction && !empty($prefixes)) {
			if (empty($request->params['prefix']) && strpos($request->params['action'], '_') > 0) {
				list($prefix) = explode('_', $request->params['action']);
				$privateAction = in_array($prefix, $prefixes);
			}
		}
		return $privateAction;
	}

/**
 * Merge components, helpers vars from
 * parent classes.
 *
 * @return void
 */
	protected function _mergeControllerVars() {
		$pluginDot = null;
		if (!empty($this->plugin)) {
			$pluginDot = $this->plugin . '.';
		}
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
 * Loads Model and Component classes.
 *
 * Using the $components properties, classes are loaded
 * and components have their callbacks attached to the EventManager.
 * It is also at this time that Controller callbacks are bound.
 *
 * See Controller::repository(); for more information on how models are loaded.
 *
 * @return mixed true if models found and instance created.
 * @see Controller::repository()
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::constructClasses
 * @throws MissingModelException
 */
	public function constructClasses() {
		$this->_mergeControllerVars();
		$this->_loadComponents();
		$this->getEventManager()->attach($this);
		return true;
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
		$components = $this->Components->normalizeArray($this->components);
		foreach ($components as $properties) {
			list(, $class) = pluginSplit($properties['class']);
			$this->{$class} = $this->Components->load($properties['class'], $properties['settings']);
		}
	}

/**
 * Returns the Cake\Event\EventManager manager instance for this controller.
 *
 * You can use this instance to register any new listeners or callbacks to the
 * controller events, or create your own events and trigger them at will.
 *
 * @return Cake\Event\EventManager
 */
	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new EventManager();
		}
		return $this->_eventManager;
	}

/**
 * Overwrite the existing EventManager
 *
 * Useful for testing
 *
 * @param Cake\Event\EventManager $eventManager
 * @return void
 */
	public function setEventManager($eventManager) {
		$this->_eventManager = $eventManager;
	}

/**
 * Perform the startup process for this controller.
 * Fire the Components and Controller callbacks in the correct order.
 *
 * - Initializes components, which fires their `initialize` callback
 * - Calls the controller `beforeFilter`.
 * - triggers Component `startup` methods.
 *
 * @return void
 */
	public function startupProcess() {
		$this->getEventManager()->dispatch(new Event('Controller.initialize', $this));
		$this->getEventManager()->dispatch(new Event('Controller.startup', $this));
	}

/**
 * Perform the various shutdown processes for this controller.
 * Fire the Components and Controller callbacks in the correct order.
 *
 * - triggers the component `shutdown` callback.
 * - calls the Controller's `afterFilter` method.
 *
 * @return void
 */
	public function shutdownProcess() {
		$this->getEventManager()->dispatch(new Event('Controller.shutdown', $this));
	}

/**
 * Redirects to given $url, after turning off $this->autoRender.
 * Script execution is halted after the redirect.
 *
 * @param string|array $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param integer $status Optional HTTP status code (eg: 404)
 * @param boolean $exit If true, exit() will be called after the redirect
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::redirect
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->autoRender = false;

		$response = $this->response;
		if ($status && $response->statusCode() === 200) {
			$response->statusCode($status);
		}

		$event = new Event('Controller.beforeRedirect', $this, [$response, $url, $status]);
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return;
		}

		if ($url !== null && !$response->location()) {
			$response->location(Router::url($url, true));
		}

		if ($exit) {
			$response->send();
			$this->_stop();
		}
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
 * @return Cake\Network\Response A response object containing the rendered view.
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::render
 */
	public function render($view = null, $layout = null) {
		$event = new Event('Controller.beforeRender', $this);
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			$this->autoRender = false;
			return $this->response;
		}

		$this->View = $this->_getViewObject();

		$this->autoRender = false;
		$this->response->body($this->View->render($view, $layout));
		return $this->response;
	}

/**
 * Returns the referring URL for this request.
 *
 * @param string $default Default URL to use if HTTP_REFERER cannot be read from headers
 * @param boolean $local If true, restrict referring URLs to local server
 * @return string Referring URL
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::referer
 */
	public function referer($default = null, $local = false) {
		if (!$this->request) {
			return '/';
		}

		$referer = $this->request->referer($local);
		if ($referer === '/' && $default) {
			return Router::url($default, true);
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
 * @param Table|string $object Table to paginate (e.g: Table instance, or 'Model')
 * @return ORM\ResultSet Query results
 * @link http://book.cakephp.org/3.0/en/controllers.html#Controller::paginate
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

		$this->Paginator = $this->Components->load('Paginator');
		if (
			!in_array('Paginator', $this->helpers) &&
			!array_key_exists('Paginator', $this->helpers)
		) {
			$this->helpers[] = 'Paginator';
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
 * If this method returns false the controller will not continue on to redirect the request.
 * The $url, $status and $exit variables have same meaning as for the controller's method. You can also
 * return a string which will be interpreted as the URL to redirect to or return associative array with
 * key 'url' and optionally 'status' and 'exit'.
 *
 * @param Event $event An Event instance
 * @param string|array $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param integer $status Optional HTTP status code (eg: 404)
 * @param boolean $exit If true, exit() will be called after the redirect
 * @return mixed
 *   false to stop redirection event,
 *   string controllers a new redirection URL or
 *   array with the keys url, status and exit to be used by the redirect method.
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeRedirect(Event $event, $url, $status = null, $exit = true) {
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

/**
 * Constructs the view class instance based on the controller property
 *
 * @return View
 */
	protected function _getViewObject() {
		$viewClass = $this->viewClass;
		if ($this->viewClass !== 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass, true);
			$viewClass = App::classname($viewClass, 'View', 'View');
		}
		return new $viewClass($this);
	}

}
