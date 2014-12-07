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
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeResponse', 'Network');
App::uses('ClassRegistry', 'Utility');
App::uses('ComponentCollection', 'Controller');
App::uses('View', 'View');
App::uses('CakeEvent', 'Event');
App::uses('CakeEventListener', 'Event');
App::uses('CakeEventManager', 'Event');

/**
 * Application controller class for organization of business logic.
 * Provides basic functionality, such as rendering views inside layouts,
 * automatic model availability, redirection, callbacks, and more.
 *
 * Controllers should provide a number of 'action' methods. These are public methods on the controller
 * that are not prefixed with a '_' and not part of Controller. Each action serves as an endpoint for
 * performing a specific action on a resource or collection of resources. For example: adding or editing a new
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
 * @package       Cake.Controller
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
class Controller extends Object implements CakeEventListener {

/**
 * The name of this controller. Controller names are plural, named after the model they manipulate.
 *
 * @var string
 * @link http://book.cakephp.org/2.0/en/controllers.html#controller-attributes
 */
	public $name = null;

/**
 * An array containing the class names of models this controller uses.
 *
 * Example: `public $uses = array('Product', 'Post', 'Comment');`
 *
 * Can be set to several values to express different options:
 *
 * - `true` Use the default inflected model name.
 * - `array()` Use only models defined in the parent class.
 * - `false` Use no models at all, do not merge with parent class either.
 * - `array('Post', 'Comment')` Use only the Post and Comment models. Models
 *   Will also be merged with the parent class.
 *
 * The default value is `true`.
 *
 * @var mixed
 * @link http://book.cakephp.org/2.0/en/controllers.html#components-helpers-and-uses
 */
	public $uses = true;

/**
 * An array containing the names of helpers this controller uses. The array elements should
 * not contain the "Helper" part of the class name.
 *
 * Example: `public $helpers = array('Html', 'Js', 'Time', 'Ajax');`
 *
 * @var mixed
 * @link http://book.cakephp.org/2.0/en/controllers.html#components-helpers-and-uses
 */
	public $helpers = array();

/**
 * An instance of a CakeRequest object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request.
 *
 * @var CakeRequest
 * @link http://book.cakephp.org/2.0/en/controllers/request-response.html#cakerequest
 */
	public $request;

/**
 * An instance of a CakeResponse object that contains information about the impending response
 *
 * @var CakeResponse
 * @link http://book.cakephp.org/2.0/en/controllers/request-response.html#cakeresponse
 */
	public $response;

/**
 * The class name to use for creating the response object.
 *
 * @var string
 */
	protected $_responseClass = 'CakeResponse';

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
 * Contains variables to be handed to the view.
 *
 * @var array
 */
	public $viewVars = array();

/**
 * The name of the view file to render. The name specified
 * is the filename in /app/View/<SubFolder> without the .ctp extension.
 *
 * @var string
 */
	public $view = null;

/**
 * The name of the layout file to render the view inside of. The name specified
 * is the filename of the layout in /app/View/Layouts without the .ctp
 * extension.
 *
 * @var string
 */
	public $layout = 'default';

/**
 * Set to true to automatically render the view
 * after action logic.
 *
 * @var bool
 */
	public $autoRender = true;

/**
 * Set to true to automatically render the layout around views.
 *
 * @var bool
 */
	public $autoLayout = true;

/**
 * Instance of ComponentCollection used to handle callbacks.
 *
 * @var ComponentCollection
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
	public $viewClass = 'View';

/**
 * Instance of the View created during rendering. Won't be set until after
 * Controller::render() is called.
 *
 * @var View
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
 * Holds all params passed and named.
 *
 * @var mixed
 */
	public $passedArgs = array();

/**
 * Triggers Scaffolding
 *
 * @var mixed
 * @link http://book.cakephp.org/2.0/en/controllers/scaffolding.html
 */
	public $scaffold = false;

/**
 * Holds current methods of the controller. This is a list of all the methods reachable
 * via URL. Modifying this array will allow you to change which methods can be reached.
 *
 * @var array
 */
	public $methods = array();

/**
 * This controller's primary model class name, the Inflector::singularize()'ed version of
 * the controller's $name property.
 *
 * Example: For a controller named 'Comments', the modelClass would be 'Comment'
 *
 * @var string
 */
	public $modelClass = null;

/**
 * This controller's model key name, an underscored version of the controller's $modelClass property.
 *
 * Example: For a controller named 'ArticleComments', the modelKey would be 'article_comment'
 *
 * @var string
 */
	public $modelKey = null;

/**
 * Holds any validation errors produced by the last call of the validateErrors() method.
 *
 * @var array
 */
	public $validationErrors = null;

/**
 * The class name of the parent class you wish to merge with.
 * Typically this is AppController, but you may wish to merge vars with a different
 * parent class.
 *
 * @var string
 */
	protected $_mergeParent = 'AppController';

/**
 * Instance of the CakeEventManager this controller is using
 * to dispatch inner events.
 *
 * @var CakeEventManager
 */
	protected $_eventManager = null;

/**
 * Constructor.
 *
 * @param CakeRequest $request Request object for this controller. Can be null for testing,
 *  but expect that features that use the request parameters will not work.
 * @param CakeResponse $response Response object for this controller.
 */
	public function __construct($request = null, $response = null) {
		if ($this->name === null) {
			$this->name = substr(get_class($this), 0, -10);
		}

		if (!$this->viewPath) {
			$this->viewPath = $this->name;
		}

		$this->modelClass = Inflector::singularize($this->name);
		$this->modelKey = Inflector::underscore($this->modelClass);
		$this->Components = new ComponentCollection();

		$childMethods = get_class_methods($this);
		$parentMethods = get_class_methods('Controller');

		$this->methods = array_diff($childMethods, $parentMethods);

		if ($request instanceof CakeRequest) {
			$this->setRequest($request);
		}
		if ($response instanceof CakeResponse) {
			$this->response = $response;
		}
		parent::__construct();
	}

/**
 * Provides backwards compatibility to avoid problems with empty and isset to alias properties.
 * Lazy loads models using the loadModel() method if declared in $uses
 *
 * @param string $name Property name to check.
 * @return bool
 */
	public function __isset($name) {
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
			case 'action':
			case 'params':
				return true;
		}

		if (is_array($this->uses)) {
			foreach ($this->uses as $modelClass) {
				list($plugin, $class) = pluginSplit($modelClass, true);
				if ($name === $class) {
					return $this->loadModel($modelClass);
				}
			}
		}

		if ($name === $this->modelClass) {
			list($plugin, $class) = pluginSplit($name, true);
			if (!$plugin) {
				$plugin = $this->plugin ? $this->plugin . '.' : null;
			}
			return $this->loadModel($plugin . $this->modelClass);
		}

		return false;
	}

/**
 * Provides backwards compatibility access to the request object properties.
 * Also provides the params alias.
 *
 * @param string $name The name of the requested value
 * @return mixed The requested value for valid variables/aliases else null
 */
	public function __get($name) {
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
				return $this->request->{$name};
			case 'action':
				return isset($this->request->params['action']) ? $this->request->params['action'] : '';
			case 'params':
				return $this->request;
			case 'paginate':
				return $this->Components->load('Paginator')->settings;
		}

		if (isset($this->{$name})) {
			return $this->{$name};
		}

		return null;
	}

/**
 * Provides backwards compatibility access for setting values to the request object.
 *
 * @param string $name Property name to set.
 * @param mixed $value Value to set.
 * @return void
 */
	public function __set($name, $value) {
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
				$this->request->{$name} = $value;
				return;
			case 'action':
				$this->request->params['action'] = $value;
				return;
			case 'params':
				$this->request->params = $value;
				return;
			case 'paginate':
				$this->Components->load('Paginator')->settings = $value;
				return;
		}
		$this->{$name} = $value;
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
 * @param CakeRequest $request Request instance.
 * @return void
 */
	public function setRequest(CakeRequest $request) {
		$this->request = $request;
		$this->plugin = isset($request->params['plugin']) ? Inflector::camelize($request->params['plugin']) : null;
		$this->view = isset($request->params['action']) ? $request->params['action'] : null;
		if (isset($request->params['pass']) && isset($request->params['named'])) {
			$this->passedArgs = array_merge($request->params['pass'], $request->params['named']);
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
 * @param CakeRequest $request Request instance.
 * @return mixed The resulting response.
 * @throws PrivateActionException When actions are not public or prefixed by _
 * @throws MissingActionException When actions are not defined and scaffolding is
 *    not enabled.
 */
	public function invokeAction(CakeRequest $request) {
		try {
			$method = new ReflectionMethod($this, $request->params['action']);

			if ($this->_isPrivateAction($method, $request)) {
				throw new PrivateActionException(array(
					'controller' => $this->name . "Controller",
					'action' => $request->params['action']
				));
			}
			return $method->invokeArgs($this, $request->params['pass']);

		} catch (ReflectionException $e) {
			if ($this->scaffold !== false) {
				return $this->_getScaffold($request);
			}
			throw new MissingActionException(array(
				'controller' => $this->name . "Controller",
				'action' => $request->params['action']
			));
		}
	}

/**
 * Check if the request's action is marked as private, with an underscore,
 * or if the request is attempting to directly accessing a prefixed action.
 *
 * @param ReflectionMethod $method The method to be invoked.
 * @param CakeRequest $request The request to check.
 * @return bool
 */
	protected function _isPrivateAction(ReflectionMethod $method, CakeRequest $request) {
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
 * Returns a scaffold object to use for dynamically scaffolded controllers.
 *
 * @param CakeRequest $request Request instance.
 * @return Scaffold
 */
	protected function _getScaffold(CakeRequest $request) {
		return new Scaffold($this, $request);
	}

/**
 * Merge components, helpers, and uses vars from
 * Controller::$_mergeParent and PluginAppController.
 *
 * @return void
 */
	protected function _mergeControllerVars() {
		$pluginController = $pluginDot = null;
		$mergeParent = is_subclass_of($this, $this->_mergeParent);
		$pluginVars = array();
		$appVars = array();

		if (!empty($this->plugin)) {
			$pluginController = $this->plugin . 'AppController';
			if (!is_subclass_of($this, $pluginController)) {
				$pluginController = null;
			}
			$pluginDot = $this->plugin . '.';
		}

		if ($pluginController) {
			$merge = array('components', 'helpers');
			$this->_mergeVars($merge, $pluginController);
		}

		if ($mergeParent || !empty($pluginController)) {
			$appVars = get_class_vars($this->_mergeParent);
			$merge = array('components', 'helpers');
			$this->_mergeVars($merge, $this->_mergeParent, true);
		}

		if ($this->uses === null) {
			$this->uses = false;
		}
		if ($this->uses === true) {
			$this->uses = array($pluginDot . $this->modelClass);
		}
		if (isset($appVars['uses']) && $appVars['uses'] === $this->uses) {
			array_unshift($this->uses, $pluginDot . $this->modelClass);
		}
		if ($pluginController) {
			$pluginVars = get_class_vars($pluginController);
		}
		if ($this->uses !== false) {
			$this->_mergeUses($pluginVars);
			$this->_mergeUses($appVars);
		} else {
			$this->uses = array();
			$this->modelClass = '';
		}
	}

/**
 * Helper method for merging the $uses property together.
 *
 * Merges the elements not already in $this->uses into
 * $this->uses.
 *
 * @param array $merge The data to merge in.
 * @return void
 */
	protected function _mergeUses($merge) {
		if (!isset($merge['uses'])) {
			return;
		}
		if ($merge['uses'] === true) {
			return;
		}
		$this->uses = array_merge(
			$this->uses,
			array_diff($merge['uses'], $this->uses)
		);
	}

/**
 * Returns a list of all events that will fire in the controller during its lifecycle.
 * You can override this function to add your own listener callbacks
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Controller.initialize' => 'beforeFilter',
			'Controller.beforeRender' => 'beforeRender',
			'Controller.beforeRedirect' => array('callable' => 'beforeRedirect', 'passParams' => true),
			'Controller.shutdown' => 'afterFilter'
		);
	}

/**
 * Loads Model classes based on the uses property
 * see Controller::loadModel(); for more info.
 * Loads Components and prepares them for initialization.
 *
 * @return mixed true if models found and instance created.
 * @see Controller::loadModel()
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::constructClasses
 * @throws MissingModelException
 */
	public function constructClasses() {
		$this->_mergeControllerVars();
		if ($this->uses) {
			$this->uses = (array)$this->uses;
			list(, $this->modelClass) = pluginSplit(reset($this->uses));
		}
		$this->Components->init($this);
		return true;
	}

/**
 * Returns the CakeEventManager manager instance that is handling any callbacks.
 * You can use this instance to register any new listeners or callbacks to the
 * controller events, or create your own events and trigger them at will.
 *
 * @return CakeEventManager
 */
	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new CakeEventManager();
			$this->_eventManager->attach($this->Components);
			$this->_eventManager->attach($this);
		}
		return $this->_eventManager;
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
 * @triggers Controller.initialize $this
 * @triggers Controller.startup $this
 */
	public function startupProcess() {
		$this->getEventManager()->dispatch(new CakeEvent('Controller.initialize', $this));
		$this->getEventManager()->dispatch(new CakeEvent('Controller.startup', $this));
	}

/**
 * Perform the various shutdown processes for this controller.
 * Fire the Components and Controller callbacks in the correct order.
 *
 * - triggers the component `shutdown` callback.
 * - calls the Controller's `afterFilter` method.
 *
 * @return void
 * @triggers Controller.shutdown $this
 */
	public function shutdownProcess() {
		$this->getEventManager()->dispatch(new CakeEvent('Controller.shutdown', $this));
	}

/**
 * Queries & sets valid HTTP response codes & messages.
 *
 * @param int|array $code If $code is an integer, then the corresponding code/message is
 *        returned if it exists, null if it does not exist. If $code is an array,
 *        then the 'code' and 'message' keys of each nested array are added to the default
 *        HTTP codes. Example:
 *
 *        httpCodes(404); // returns array(404 => 'Not Found')
 *
 *        httpCodes(array(
 *            701 => 'Unicorn Moved',
 *            800 => 'Unexpected Minotaur'
 *        )); // sets these new values, and returns true
 *
 * @return array Associative array of the HTTP codes as keys, and the message
 *    strings as values, or null of the given $code does not exist.
 * @deprecated 3.0.0 Since 2.4. Will be removed in 3.0. Use CakeResponse::httpCodes().
 */
	public function httpCodes($code = null) {
		return $this->response->httpCodes($code);
	}

/**
 * Loads and instantiates models required by this controller.
 * If the model is non existent, it will throw a missing database table error, as CakePHP generates
 * dynamic models for the time being.
 *
 * @param string $modelClass Name of model class to load
 * @param int|string $id Initial ID the instanced model class should have
 * @return bool True if the model was found
 * @throws MissingModelException if the model class cannot be found.
 */
	public function loadModel($modelClass = null, $id = null) {
		if ($modelClass === null) {
			$modelClass = $this->modelClass;
		}

		$this->uses = ($this->uses) ? (array)$this->uses : array();
		if (!in_array($modelClass, $this->uses, true)) {
			$this->uses[] = $modelClass;
		}

		list($plugin, $modelClass) = pluginSplit($modelClass, true);

		$this->{$modelClass} = ClassRegistry::init(array(
			'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
		));
		if (!$this->{$modelClass}) {
			throw new MissingModelException($modelClass);
		}
		return true;
	}

/**
 * Redirects to given $url, after turning off $this->autoRender.
 * Script execution is halted after the redirect.
 *
 * @param string|array $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param int $status Optional HTTP status code (eg: 404)
 * @param bool $exit If true, exit() will be called after the redirect
 * @return void
 * @triggers Controller.beforeRedirect $this, array($url, $status, $exit)
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::redirect
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->autoRender = false;

		if (is_array($status)) {
			extract($status, EXTR_OVERWRITE);
		}
		$event = new CakeEvent('Controller.beforeRedirect', $this, array($url, $status, $exit));

		list($event->break, $event->breakOn, $event->collectReturn) = array(true, false, true);
		$this->getEventManager()->dispatch($event);

		if ($event->isStopped()) {
			return;
		}
		$response = $event->result;
		extract($this->_parseBeforeRedirect($response, $url, $status, $exit), EXTR_OVERWRITE);

		if ($url !== null) {
			$this->response->header('Location', Router::url($url, true));
		}

		if (is_string($status)) {
			$codes = array_flip($this->response->httpCodes());
			if (isset($codes[$status])) {
				$status = $codes[$status];
			}
		}

		if ($status) {
			$this->response->statusCode($status);
		}

		if ($exit) {
			$this->response->send();
			$this->_stop();
		}
	}

/**
 * Parse beforeRedirect Response
 *
 * @param mixed $response Response from beforeRedirect callback
 * @param string|array $url The same value of beforeRedirect
 * @param int $status The same value of beforeRedirect
 * @param bool $exit The same value of beforeRedirect
 * @return array Array with keys url, status and exit
 */
	protected function _parseBeforeRedirect($response, $url, $status, $exit) {
		if (is_array($response) && array_key_exists(0, $response)) {
			foreach ($response as $resp) {
				if (is_array($resp) && isset($resp['url'])) {
					extract($resp, EXTR_OVERWRITE);
				} elseif ($resp !== null) {
					$url = $resp;
				}
			}
		} elseif (is_array($response)) {
			extract($response, EXTR_OVERWRITE);
		}
		return compact('url', 'status', 'exit');
	}

/**
 * Convenience and object wrapper method for CakeResponse::header().
 *
 * @param string $status The header message that is being set.
 * @return void
 * @deprecated 3.0.0 Will be removed in 3.0. Use CakeResponse::header().
 */
	public function header($status) {
		$this->response->header($status);
	}

/**
 * Saves a variable for use inside a view template.
 *
 * @param string|array $one A string or an array of data.
 * @param string|array $two Value in case $one is a string (which then works as the key).
 *   Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#interacting-with-views
 */
	public function set($one, $two = null) {
		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}
		$this->viewVars = $data + $this->viewVars;
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
 * Returns number of errors in a submitted FORM.
 *
 * @return int Number of errors
 * @deprecated 3.0.0 This method will be removed in 3.0
 */
	public function validate() {
		$args = func_get_args();
		$errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

		if ($errors === false) {
			return 0;
		}
		return count($errors);
	}

/**
 * Validates models passed by parameters. Takes a list of models as a variable argument.
 * Example:
 *
 * `$errors = $this->validateErrors($this->Article, $this->User);`
 *
 * @return array Validation errors, or false if none
 * @deprecated 3.0.0 This method will be removed in 3.0
 */
	public function validateErrors() {
		$objects = func_get_args();

		if (empty($objects)) {
			return false;
		}

		$errors = array();
		foreach ($objects as $object) {
			if (isset($this->{$object->alias})) {
				$object = $this->{$object->alias};
			}
			$object->set($object->data);
			$errors = array_merge($errors, $object->invalidFields());
		}

		return $this->validationErrors = (!empty($errors) ? $errors : false);
	}

/**
 * Instantiates the correct view class, hands it its data, and uses it to render the view output.
 *
 * @param string $view View to use for rendering
 * @param string $layout Layout to use
 * @return CakeResponse A response object containing the rendered view.
 * @triggers Controller.beforeRender $this
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::render
 */
	public function render($view = null, $layout = null) {
		$event = new CakeEvent('Controller.beforeRender', $this);
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			$this->autoRender = false;
			return $this->response;
		}

		if (!empty($this->uses) && is_array($this->uses)) {
			foreach ($this->uses as $model) {
				list($plugin, $className) = pluginSplit($model);
				$this->request->params['models'][$className] = compact('plugin', 'className');
			}
		}

		$this->View = $this->_getViewObject();

		$models = ClassRegistry::keys();
		foreach ($models as $currentModel) {
			$currentObject = ClassRegistry::getObject($currentModel);
			if ($currentObject instanceof Model) {
				$className = get_class($currentObject);
				list($plugin) = pluginSplit(App::location($className));
				$this->request->params['models'][$currentObject->alias] = compact('plugin', 'className');
				$this->View->validationErrors[$currentObject->alias] =& $currentObject->validationErrors;
			}
		}

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
 * Forces the user's browser not to cache the results of the current request.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::disableCache
 * @deprecated 3.0.0 Will be removed in 3.0. Use CakeResponse::disableCache().
 */
	public function disableCache() {
		$this->response->disableCache();
	}

/**
 * Shows a message to the user for $pause seconds, then redirects to $url.
 * Uses flash.ctp as the default layout for the message.
 * Does not work if the current debug level is higher than 0.
 *
 * @param string $message Message to display to the user
 * @param string|array $url Relative string or array-based URL to redirect to after the time expires
 * @param int $pause Time to show the message
 * @param string $layout Layout you want to use, defaults to 'flash'
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::flash
 * @deprecated 3.0.0 Will be removed in 3.0. Use Session::setFlash().
 */
	public function flash($message, $url, $pause = 1, $layout = 'flash') {
		$this->autoRender = false;
		$this->set('url', Router::url($url));
		$this->set('message', $message);
		$this->set('pause', $pause);
		$this->set('pageTitle', $message);
		$this->render(false, $layout);
	}

/**
 * Converts POST'ed form data to a model conditions array, suitable for use in a Model::find() call.
 *
 * @param array $data POST'ed data organized by model and field
 * @param string|array $op A string containing an SQL comparison operator, or an array matching operators
 *        to fields
 * @param string $bool SQL boolean operator: AND, OR, XOR, etc.
 * @param bool $exclusive If true, and $op is an array, fields not included in $op will not be
 *        included in the returned conditions
 * @return array|null An array of model conditions
 * @deprecated 3.0.0 Will be removed in 3.0.
 */
	public function postConditions($data = array(), $op = null, $bool = 'AND', $exclusive = false) {
		if (!is_array($data) || empty($data)) {
			if (!empty($this->request->data)) {
				$data = $this->request->data;
			} else {
				return null;
			}
		}
		$cond = array();

		if ($op === null) {
			$op = '';
		}

		$arrayOp = is_array($op);
		foreach ($data as $model => $fields) {
			foreach ($fields as $field => $value) {
				$key = $model . '.' . $field;
				$fieldOp = $op;
				if ($arrayOp) {
					if (array_key_exists($key, $op)) {
						$fieldOp = $op[$key];
					} elseif (array_key_exists($field, $op)) {
						$fieldOp = $op[$field];
					} else {
						$fieldOp = false;
					}
				}
				if ($exclusive && $fieldOp === false) {
					continue;
				}
				$fieldOp = strtoupper(trim($fieldOp));
				if ($fieldOp === 'LIKE') {
					$key = $key . ' LIKE';
					$value = '%' . $value . '%';
				} elseif ($fieldOp && $fieldOp !== '=') {
					$key = $key . ' ' . $fieldOp;
				}
				$cond[$key] = $value;
			}
		}
		if ($bool && strtoupper($bool) !== 'AND') {
			$cond = array($bool => $cond);
		}
		return $cond;
	}

/**
 * Handles automatic pagination of model records.
 *
 * @param Model|string $object Model to paginate (e.g: model instance, or 'Model', or 'Model.InnerModel')
 * @param string|array $scope Conditions to use while paginating
 * @param array $whitelist List of allowed options for paging
 * @return array Model query results
 * @link http://book.cakephp.org/2.0/en/controllers.html#Controller::paginate
 */
	public function paginate($object = null, $scope = array(), $whitelist = array()) {
		return $this->Components->load('Paginator', $this->paginate)->paginate($object, $scope, $whitelist);
	}

/**
 * Called before the controller action. You can use this method to configure and customize components
 * or perform logic that needs to happen before each controller action.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeFilter() {
	}

/**
 * Called after the controller action is run, but before the view is rendered. You can use this method
 * to perform logic or set view variables that are required on every request.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeRender() {
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
 * @param string|array $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param int $status Optional HTTP status code (eg: 404)
 * @param bool $exit If true, exit() will be called after the redirect
 * @return mixed
 *   false to stop redirection event,
 *   string controllers a new redirection URL or
 *   array with the keys url, status and exit to be used by the redirect method.
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function beforeRedirect($url, $status = null, $exit = true) {
	}

/**
 * Called after the controller action is run and rendered.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers.html#request-life-cycle-callbacks
 */
	public function afterFilter() {
	}

/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called example index, edit, etc.
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/controllers.html#callbacks
 */
	public function beforeScaffold($method) {
		return true;
	}

/**
 * Alias to beforeScaffold()
 *
 * @param string $method Method name.
 * @return bool
 * @see Controller::beforeScaffold()
 * @deprecated 3.0.0 Will be removed in 3.0.
 */
	protected function _beforeScaffold($method) {
		return $this->beforeScaffold($method);
	}

/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called either edit or update.
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/controllers.html#callbacks
 */
	public function afterScaffoldSave($method) {
		return true;
	}

/**
 * Alias to afterScaffoldSave()
 *
 * @param string $method Method name.
 * @return bool
 * @see Controller::afterScaffoldSave()
 * @deprecated 3.0.0 Will be removed in 3.0.
 */
	protected function _afterScaffoldSave($method) {
		return $this->afterScaffoldSave($method);
	}

/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called either edit or update.
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/controllers.html#callbacks
 */
	public function afterScaffoldSaveError($method) {
		return true;
	}

/**
 * Alias to afterScaffoldSaveError()
 *
 * @param string $method Method name.
 * @return bool
 * @see Controller::afterScaffoldSaveError()
 * @deprecated 3.0.0 Will be removed in 3.0.
 */
	protected function _afterScaffoldSaveError($method) {
		return $this->afterScaffoldSaveError($method);
	}

/**
 * This method should be overridden in child classes.
 * If not it will render a scaffold error.
 * Method MUST return true in child classes
 *
 * @param string $method name of method called example index, edit, etc.
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/controllers.html#callbacks
 */
	public function scaffoldError($method) {
		return false;
	}

/**
 * Alias to scaffoldError()
 *
 * @param string $method Method name.
 * @return bool
 * @see Controller::scaffoldError()
 * @deprecated 3.0.0 Will be removed in 3.0.
 */
	protected function _scaffoldError($method) {
		return $this->scaffoldError($method);
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
			$viewClass = $viewClass . 'View';
			App::uses($viewClass, $plugin . 'View');
		}

		return new $viewClass($this);
	}

}
