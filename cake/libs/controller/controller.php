<?php
/**
 * Base controller class.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Include files
 */
App::import('Core', 'CakeResponse', false);
App::import('Core', 'ClassRegistry', false);
App::import('Controller', 'Component', false);
App::import('View', 'View', false);

/**
 * Application controller class for organization of business logic.
 * Provides basic functionality, such as rendering views inside layouts,
 * automatic model availability, redirection, callbacks, and more.
 *
 * Controllers should provide a number of 'action' methods.  These are public methods on the controller
 * that are not prefixed with a '_' and not part of Controller.  Each action serves as an endpoint for 
 * performing a specific action on a resource or collection of resources.  For example adding or editing a new
 * object, or listing a set of objects.
 *
 * You can access request parameters, using `$this->request`.  The request object contains all the POST, GET and FILES
 * that were part of the request. 
 *
 * After performing the required actions, controllers are responsible for creating a response.  This usually
 * takes the form of a generated View, or possibly a redirection to another controller action.  In either case
 * `$this->response` allows you to manipulate all aspects of the response.
 *
 * Controllers are created by Dispatcher based on request parameters and routing. By default controllers and actions
 * use conventional names.  For example `/posts/index` maps to `PostsController::index()`.  You can re-map urls
 * using Router::connect().
 *
 * @package    cake.libs.controller
 * @link       http://book.cakephp.org/view/956/Introduction
 */
class Controller extends Object {

/**
 * The name of this controller. Controller names are plural, named after the model they manipulate.
 *
 * @var string
 * @link http://book.cakephp.org/view/959/Controller-Attributes
 */
	public $name = null;

/**
 * An array containing the class names of models this controller uses.
 *
 * Example: `public $uses = array('Product', 'Post', 'Comment');`
 *
 * Can be set to array() to use no models.  Can be set to false to
 * use no models and prevent the merging of $uses with AppController
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @link http://book.cakephp.org/view/961/components-helpers-and-uses
 */
	public $uses = false;

/**
 * An array containing the names of helpers this controller uses. The array elements should
 * not contain the "Helper" part of the classname.
 *
 * Example: `public $helpers = array('Html', 'Javascript', 'Time', 'Ajax');`
 *
 * @var mixed A single name as a string or a list of names as an array.
 * @link http://book.cakephp.org/view/961/components-helpers-and-uses
 */
	public $helpers = array('Session', 'Html', 'Form');

/**
 * An instance of a CakeRequest object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request. 
 *
 * @var CakeRequest
 */
	public $request;

/**
 * An instance of a CakeResponse object that contains information about the impending response
 *
 * @var CakeResponse
 */
	public $response;

/**
 * The classname to use for creating the response object.
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
 * An array containing the class names of the models this controller uses.
 *
 * @var array Array of model objects.
 */
	public $modelNames = array();

/**
 * The name of the layout file to render the view inside of. The name specified
 * is the filename of the layout in /app/views/layouts without the .ctp
 * extension.
 *
 * @var string
 * @link http://book.cakephp.org/view/962/Page-related-Attributes-layout-and-pageTitle
 */
	public $layout = 'default';

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
 * Instance of ComponentCollection used to handle callbacks.
 *
 * @var string
 */
	public $Components = null;

/**
 * Array containing the names of components this controller uses. Component names
 * should not contain the "Component" portion of the classname.
 *
 * Example: `public $components = array('Session', 'RequestHandler', 'Acl');`
 *
 * @var array
 * @link http://book.cakephp.org/view/961/components-helpers-and-uses
 */
	public $components = array('Session');

/**
 * The name of the View class this controller sends output to.
 *
 * @var string
 */
	public $view = 'View';

/**
 * Instance of the View created during rendering. Won't be set until after Controller::render() is called.
 *
 * @var View
 */
	public $View;

/**
 * File extension for view templates. Defaults to Cake's conventional ".ctp".
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
 * @link http://book.cakephp.org/view/1380/Caching-in-the-Controller
 */
	public $cacheAction = false;

/**
 * Used to create cached instances of models a controller uses.
 * When set to true, all models related to the controller will be cached.
 * This can increase performance in many cases.
 *
 * @var boolean
 */
	public $persistModel = false;

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
 * @link http://book.cakephp.org/view/1103/Scaffolding
 */
	public $scaffold = false;

/**
 * Holds current methods of the controller.  This is a list of all the methods reachable
 * via url.  Modifying this array, will allow you to change which methods can be reached.
 *
 * @var array
 */
	public $methods = array();

/**
 * This controller's primary model class name, the Inflector::classify()'ed version of
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
 * Holds any validation errors produced by the last call of the validateErrors() method/
 *
 * @var array Validation errors, or false if none
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
 * Constructor.
 *
 * @param CakeRequest $request Request object for this controller can be null for testing.
 *  But expect that features that use the params will not work.
 */
	public function __construct($request = null) {
		if ($this->name === null) {
			$r = null;
			if (!preg_match('/(.*)Controller/i', get_class($this), $r)) {
				echo __("Controller::__construct() : Can not get or parse my own class name, exiting.");
				$this->_stop();
			}
			$this->name = $r[1];
		}

		if ($this->viewPath == null) {
			$this->viewPath = Inflector::underscore($this->name);
		}
		$this->modelClass = Inflector::classify($this->name);
		$this->modelKey = Inflector::underscore($this->modelClass);
		$this->Components = new ComponentCollection();

		$childMethods = get_class_methods($this);
		$parentMethods = get_class_methods('Controller');

		$this->methods = array_diff($childMethods, $parentMethods);

		if ($request instanceof CakeRequest) {
			$this->setRequest($request);
		}
		$this->getResponse();
		parent::__construct();
	}

/**
 * Provides backwards compatbility avoid problems with empty and isset to alias properties.
 *
 * @return void
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
		return false;
	}

/**
 * Provides backwards compatbility access to the request object properties.
 * Also provides the params alias.
 *
 * @return void
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
		return null;
	}

/**
 * Provides backwards compatiblity access for setting values to the request object.
 *
 * @return void
 */
	public function __set($name, $value) {
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
				return $this->request->{$name} = $value;
			case 'action':
				return $this->request->params['action'] = $value;
			case 'params':
				return $this->request->params = $value;
			case 'paginate':
				return $this->Components->load('Paginator')->settings = $value;
		}
		return $this->{$name} = $value;
	}

/**
 * Sets the request objects and configures a number of controller properties
 * based on the contents of the request.
 *
 * @param CakeRequest $request
 * @return void
 */
	public function setRequest(CakeRequest $request) {
		$this->request = $request;
		$this->plugin = isset($request->params['plugin']) ? $request->params['plugin'] : null;

		if (isset($request->params['pass']) && isset($request->params['named'])) {
			$this->passedArgs = array_merge($request->params['pass'], $request->params['named']);
		}

		if (array_key_exists('return', $request->params) && $request->params['return'] == 1) {
			$this->autoRender = false;
		}
		if (!empty($request->params['bare'])) {
			$this->autoLayout = false;
		}
	}

/**
 * Merge components, helpers, and uses vars from Controller::$_mergeParent and PluginAppController.
 *
 * @return void
 */
	protected function __mergeVars() {
		$pluginName = $pluginController = $plugin = null;

		if (!empty($this->plugin)) {
			$pluginName = Inflector::camelize($this->plugin);
			$pluginController = $pluginName . 'AppController';
			if (!is_subclass_of($this, $pluginController)) {
				$pluginController = null;
			}
			$plugin = $pluginName . '.';
		}
		
		if (is_subclass_of($this, $this->_mergeParent) || !empty($pluginController)) {
			$appVars = get_class_vars($this->_mergeParent);
			$uses = $appVars['uses'];
			$merge = array('components', 'helpers');

			if ($uses == $this->uses && !empty($this->uses)) {
				if (!in_array($plugin . $this->modelClass, $this->uses)) {
					array_unshift($this->uses, $plugin . $this->modelClass);
				} elseif ($this->uses[0] !== $plugin . $this->modelClass) {
					$this->uses = array_flip($this->uses);
					unset($this->uses[$plugin . $this->modelClass]);
					$this->uses = array_flip($this->uses);
					array_unshift($this->uses, $plugin . $this->modelClass);
				}
			} elseif (
				($this->uses !== null || $this->uses !== false) && 
				is_array($this->uses) && !empty($appVars['uses'])
			) {
				$this->uses = array_merge($this->uses, array_diff($appVars['uses'], $this->uses));
			}
			$this->_mergeVars($merge, $this->_mergeParent, true);
		}

		if ($pluginController && $pluginName != null) {
			$merge = array('components', 'helpers');
			$appVars = get_class_vars($pluginController);
			if (
				($this->uses !== null || $this->uses !== false) && 
				is_array($this->uses) && !empty($appVars['uses'])
			) {
				$this->uses = array_merge($this->uses, array_diff($appVars['uses'], $this->uses));
			}
			$this->_mergeVars($merge, $pluginController);
		}
	}

/**
 * Loads Model classes based on the uses property
 * see Controller::loadModel(); for more info.
 * Loads Components and prepares them for initialization.
 *
 * @return mixed true if models found and instance created.
 * @see Controller::loadModel()
 * @link http://book.cakephp.org/view/977/Controller-Methods#constructClasses-986
 * @throws MissingModelException
 */
	public function constructClasses() {
		$this->__mergeVars();
		$this->Components->init($this);

		if ($this->uses !== null || ($this->uses !== array())) {
			if (empty($this->passedArgs) || !isset($this->passedArgs['0'])) {
				$id = false;
			} else {
				$id = $this->passedArgs['0'];
			}
			$plugin = $this->plugin ? $this->plugin . '.' : null;

			if ($this->uses === false) {
				$this->loadModel($plugin . $this->modelClass, $id);
			} elseif ($this->uses) {
				$uses = is_array($this->uses) ? $this->uses : array($this->uses);
				list($plugin, $modelClassName) = pluginSplit($uses[0]);
				$this->modelClass = $modelClassName;
				foreach ($uses as $modelClass) {
					$this->loadModel($modelClass);
				}
			}
		}
		return true;
	}

/**
 * Gets the response object for this controller.  Will construct the response if it has not already been built.
 *
 * @return CakeResponse
 */
	public function getResponse() {
		if (empty($this->response)) {
			$this->response = new $this->_responseClass(array('charset' => Configure::read('App.encoding')));
		}
		return $this->response;
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
		$this->Components->trigger('initialize', array(&$this));
		$this->beforeFilter();
		$this->Components->trigger('startup', array(&$this));
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
		$this->Components->trigger('shutdown', array(&$this));
		$this->afterFilter();
	}

/**
 * Queries & sets valid HTTP response codes & messages.
 *
 * @param mixed $code If $code is an integer, then the corresponding code/message is
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
 * @return mixed Associative array of the HTTP codes as keys, and the message
 *    strings as values, or null of the given $code does not exist.
 * @deprecated Use CakeResponse::httpCodes();
 */
	public function httpCodes($code = null) {
		return $this->response->httpCodes($code);
	}

/**
 * Loads and instantiates models required by this controller.
 * If Controller::$persistModel; is true, controller will cache model instances on first request,
 * additional request will used cached models.
 * If the model is non existent, it will throw a missing database table error, as Cake generates
 * dynamic models for the time being.
 *
 * @param string $modelClass Name of model class to load
 * @param mixed $id Initial ID the instanced model class should have
 * @return mixed true when single model found and instance created, error returned if model not found.
 * @throws MissingModelException if the model class cannot be found.
 */
	public function loadModel($modelClass = null, $id = null) {
		if ($modelClass === null) {
			$modelClass = $this->modelClass;
		}
		$cached = false;
		$object = null;
		list($plugin, $modelClass) = pluginSplit($modelClass, true);

		if ($this->persistModel === true) {
			$cached = $this->_persist($modelClass, null, $object);
		}

		if (($cached === false)) {
			$this->modelNames[] = $modelClass;

			$this->{$modelClass} = ClassRegistry::init(array(
				'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
			));

			if (!$this->{$modelClass}) {
				throw new MissingModelException($modelClass);
			}

			if ($this->persistModel === true) {
				$this->_persist($modelClass, true, $this->{$modelClass});
				$registry = ClassRegistry::getInstance();
				$this->_persist($modelClass . 'registry', true, $registry->__objects, 'registry');
			}
		} else {
			$this->_persist($modelClass . 'registry', true, $object, 'registry');
			$this->_persist($modelClass, true, $object);
			$this->modelNames[] = $modelClass;
		}

		return true;
	}

/**
 * Redirects to given $url, after turning off $this->autoRender.
 * Script execution is halted after the redirect.
 *
 * @param mixed $url A string or array-based URL pointing to another location within the app,
 *     or an absolute URL
 * @param integer $status Optional HTTP status code (eg: 404)
 * @param boolean $exit If true, exit() will be called after the redirect
 * @return mixed void if $exit = false. Terminates script if $exit = true
 * @link http://book.cakephp.org/view/982/redirect
 */
	public function redirect($url, $status = null, $exit = true) {
		$this->autoRender = false;

		if (is_array($status)) {
			extract($status, EXTR_OVERWRITE);
		}
		$response = $this->Components->trigger(
			'beforeRedirect', 
			array(&$this, $url, $status, $exit),
			array('break' => true, 'breakOn' => false, 'collectReturn' => true)
		);

		if ($response === false) {
			return;
		}
		if (is_array($response)) {
			foreach ($response as $resp) {
				if (is_array($resp) && isset($resp['url'])) {
					extract($resp, EXTR_OVERWRITE);
				} elseif ($resp !== null) {
					$url = $resp;
				}
			}
		}

		if (function_exists('session_write_close')) {
			session_write_close();
		}

		if (!empty($status) && is_string($status)) {
			$codes = array_flip($this->response->httpCodes());
			if (isset($codes[$status])) {
				$status = $codes[$status];
			}
		}

		if ($url !== null) {
			$this->response->header('Location', Router::url($url, true));
		}

		if (!empty($status) && ($status >= 300 && $status < 400)) {
			$this->response->statusCode($status);
		}

		if ($exit) {
			$this->response->send();
			$this->_stop();
		}
	}

/**
 * Convenience and object wrapper method for CakeResponse::header().
 *
 * @param string $status The header message that is being set.
 * @return void
 * @deprecated Use CakeResponse::header()
 */
	public function header($status) {
		$this->response->header($status);
	}

/**
 * Saves a variable for use inside a view template.
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 *   Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
 * @link http://book.cakephp.org/view/979/set
 */
	public function set($one, $two = null) {
		$data = array();

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
 * @param string $action The new action to be 'redirected' to
 * @param mixed  Any other parameters passed to this method will be passed as
 *    parameters to the new action.
 * @return mixed Returns the return value of the called action
 */
	public function setAction($action) {
		$this->request->action = $action;
		$args = func_get_args();
		unset($args[0]);
		return call_user_func_array(array(&$this, $action), $args);
	}

/**
 * Controller callback to tie into Auth component.
 * Only called when AuthComponent::$authorize is set to 'controller'.
 *
 * @return bool true if authorized, false otherwise
 * @link http://book.cakephp.org/view/1275/authorize
 */
	public function isAuthorized() {
		trigger_error(sprintf(
			__('%sController::isAuthorized() is not defined.'), $this->name
		), E_USER_WARNING);
		return false;
	}

/**
 * Returns number of errors in a submitted FORM.
 *
 * @return integer Number of errors
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
 * Validates models passed by parameters. Example:
 *
 * `$errors = $this->validateErrors($this->Article, $this->User);`
 *
 * @param mixed A list of models as a variable argument
 * @return array Validation errors, or false if none
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
 * @param string $action Action name to render
 * @param string $layout Layout to use
 * @param string $file File to use for rendering
 * @return string Full output string of view contents
 * @link http://book.cakephp.org/view/980/render
 */
	public function render($action = null, $layout = null, $file = null) {
		$this->beforeRender();
		$this->Components->trigger('beforeRender', array(&$this));

		$viewClass = $this->view;
		if ($this->view != 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->view);
		}

		$this->request->params['models'] = $this->modelNames;

		$View = new $viewClass($this);

		if (!empty($this->modelNames)) {
			$models = array();
			foreach ($this->modelNames as $currentModel) {
				if (isset($this->$currentModel) && is_a($this->$currentModel, 'Model')) {
					$models[] = Inflector::underscore($currentModel);
				}
				$isValidModel = (
					isset($this->$currentModel) && is_a($this->$currentModel, 'Model') &&
					!empty($this->$currentModel->validationErrors)
				);
				if ($isValidModel) {
					$View->validationErrors[Inflector::camelize($currentModel)] =&
						$this->$currentModel->validationErrors;
				}
			}
			$models = array_diff(ClassRegistry::keys(), $models);
			foreach ($models as $currentModel) {
				if (ClassRegistry::isKeySet($currentModel)) {
					$currentObject = ClassRegistry::getObject($currentModel);
					if (is_a($currentObject, 'Model') && !empty($currentObject->validationErrors)) {
						$View->validationErrors[Inflector::camelize($currentModel)] =&
							$currentObject->validationErrors;
					}
				}
			}
		}

		$this->autoRender = false;
		$this->View = $View;
		return $this->response->body($View->render($action, $layout, $file));
	}

/**
 * Returns the referring URL for this request.
 *
 * @param string $default Default URL to use if HTTP_REFERER cannot be read from headers
 * @param boolean $local If true, restrict referring URLs to local server
 * @return string Referring URL
 * @link http://book.cakephp.org/view/987/referer
 */
	public function referer($default = null, $local = false) {
		if ($this->request) {
			$referer = $this->request->referer($local);
			if ($referer == '/' && $default != null) {
				return Router::url($default, true);
			}
			return $referer;
		}
		return '/';
	}

/**
 * Forces the user's browser not to cache the results of the current request.
 *
 * @return void
 * @link http://book.cakephp.org/view/988/disableCache
 * @deprecated Use CakeResponse::disableCache()
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
 * @param mixed $url Relative string or array-based URL to redirect to after the time expires
 * @param integer $pause Time to show the message
 * @param string $layout Layout you want to use, defaults to 'flash'
 * @return void Renders flash layout
 * @link http://book.cakephp.org/view/983/flash
 */
	public function flash($message, $url, $pause = 1, $layout = 'flash') {
		$this->autoRender = false;
		$this->set('url', Router::url($url));
		$this->set('message', $message);
		$this->set('pause', $pause);
		$this->set('page_title', $message);
		$this->response->body($this->render(false, $layout));
	}

/**
 * Converts POST'ed form data to a model conditions array, suitable for use in a Model::find() call.
 *
 * @param array $data POST'ed data organized by model and field
 * @param mixed $op A string containing an SQL comparison operator, or an array matching operators
 *        to fields
 * @param string $bool SQL boolean operator: AND, OR, XOR, etc.
 * @param boolean $exclusive If true, and $op is an array, fields not included in $op will not be
 *        included in the returned conditions
 * @return array An array of model conditions
 * @link http://book.cakephp.org/view/989/postConditions
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
				$key = $model.'.'.$field;
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
					$key = $key.' LIKE';
					$value = '%'.$value.'%';
				} elseif ($fieldOp && $fieldOp != '=') {
					$key = $key.' '.$fieldOp;
				}
				$cond[$key] = $value;
			}
		}
		if ($bool != null && strtoupper($bool) != 'AND') {
			$cond = array($bool => $cond);
		}
		return $cond;
	}

/**
 * Handles automatic pagination of model records.
 *
 * @param mixed $object Model to paginate (e.g: model instance, or 'Model', or 'Model.InnerModel')
 * @param mixed $scope Conditions to use while paginating
 * @param array $whitelist List of allowed options for paging
 * @return array Model query results
 * @link http://book.cakephp.org/view/1232/Controller-Setup
 * @deprecated Use PaginatorComponent instead
 */
	public function paginate($object = null, $scope = array(), $whitelist = array()) {
		return $this->Components->load('Paginator', $this->paginate)->paginate($object, $scope, $whitelist);
	}

/**
 * Called before the controller action.  You can use this method to configure and customize components
 * or perform logic that needs to happen before each controller action.
 *
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function beforeFilter() {
	}

/**
 * Called after the controller action is run, but before the view is rendered. You can use this method
 * to perform logic or set view variables that are required on every request.
 *
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function beforeRender() {
	}

/**
 * Called after the controller action is run and rendered.
 *
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function afterFilter() {
	}

/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called example index, edit, etc.
 * @return boolean Success
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function _beforeScaffold($method) {
		return true;
	}

/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called either edit or update.
 * @return boolean Success
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function _afterScaffoldSave($method) {
		return true;
	}

/**
 * This method should be overridden in child classes.
 *
 * @param string $method name of method called either edit or update.
 * @return boolean Success
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function _afterScaffoldSaveError($method) {
		return true;
	}

/**
 * This method should be overridden in child classes.
 * If not it will render a scaffold error.
 * Method MUST return true in child classes
 *
 * @param string $method name of method called example index, edit, etc.
 * @return boolean Success
 * @link http://book.cakephp.org/view/984/Callbacks
 */
	public function _scaffoldError($method) {
		return false;
	}
}
