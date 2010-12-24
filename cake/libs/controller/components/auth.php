<?php
/**
 * Authentication component
 *
 * Manages user logins and permissions.
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
 * @package       cake.libs.controller.components
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', 'Router', false);
App::import('Core', 'Security', false);

/**
 * Authentication control component class
 *
 * Binds access control with user authentication and session management.
 *
 * @package       cake.libs.controller.components
 * @link http://book.cakephp.org/view/1250/Authentication
 */
class AuthComponent extends Component {

/**
 * Maintains current user login state.
 *
 * @var boolean
 */
	protected $_loggedIn = false;

/**
 * Other components utilized by AuthComponent
 *
 * @var array
 */
	public $components = array('Session', 'RequestHandler');

/**
 * A reference to the object used for authentication
 *
 * @var object
 * @link http://book.cakephp.org/view/1278/authenticate
 */
	public $authenticate = null;

/**
 * The name of the component to use for Authorization or set this to
 * 'controller' will validate against Controller::isAuthorized()
 * 'actions' will validate Controller::action against an AclComponent::check()
 * 'crud' will validate mapActions against an AclComponent::check()
 * array('model'=> 'name'); will validate mapActions against model $name::isAuthorized(user, controller, mapAction)
 * 'object' will validate Controller::action against object::isAuthorized(user, controller, action)
 *
 * @var mixed
 * @link http://book.cakephp.org/view/1275/authorize
 */
	public $authorize = false;

/**
 * The name of an optional view element to render when an Ajax request is made
 * with an invalid or expired session
 *
 * @var string
 * @link http://book.cakephp.org/view/1277/ajaxLogin
 */
	public $ajaxLogin = null;

/**
 * The name of the element used for SessionComponent::setFlash
 *
 * @var string
 */
	public $flashElement = 'default';

/**
 * The name of the model that represents users which will be authenticated.  Defaults to 'User'.
 *
 * @var string
 * @link http://book.cakephp.org/view/1266/userModel
 */
	public $userModel = 'User';

/**
 * Additional query conditions to use when looking up and authenticating users,
 * i.e. array('User.is_active' => 1).
 *
 * @var array
 * @link http://book.cakephp.org/view/1268/userScope
 */
	public $userScope = array();

/**
 * Allows you to specify non-default login name and password fields used in
 * $userModel, i.e. array('username' => 'login_name', 'password' => 'passwd').
 *
 * @var array
 * @link http://book.cakephp.org/view/1267/fields
 */
	public $fields = array('username' => 'username', 'password' => 'password');

/**
 * The session key name where the record of the current user is stored.  If
 * unspecified, it will be "Auth.{$userModel name}".
 *
 * @var string
 * @link http://book.cakephp.org/view/1276/sessionKey
 */
	public $sessionKey = null;

/**
 * If using action-based access control, this defines how the paths to action
 * ACO nodes is computed.  If, for example, all controller nodes are nested
 * under an ACO node named 'Controllers', $actionPath should be set to
 * "Controllers/".
 *
 * @var string
 * @link http://book.cakephp.org/view/1279/actionPath
 */
	public $actionPath = null;

/**
 * A URL (defined as a string or array) to the controller action that handles
 * logins.
 *
 * @var mixed
 * @link http://book.cakephp.org/view/1269/loginAction
 */
	public $loginAction = null;

/**
 * Normally, if a user is redirected to the $loginAction page, the location they
 * were redirected from will be stored in the session so that they can be
 * redirected back after a successful login.  If this session value is not
 * set, the user will be redirected to the page specified in $loginRedirect.
 *
 * @var mixed
 * @link http://book.cakephp.org/view/1270/loginRedirect
 */
	public $loginRedirect = null;

/**
 * The default action to redirect to after the user is logged out.  While AuthComponent does
 * not handle post-logout redirection, a redirect URL will be returned from AuthComponent::logout().
 * Defaults to AuthComponent::$loginAction.
 *
 * @var mixed
 * @see AuthComponent::$loginAction
 * @see AuthComponent::logout()
 * @link http://book.cakephp.org/view/1271/logoutRedirect
 */
	public $logoutRedirect = null;

/**
 * The name of model or model object, or any other object has an isAuthorized method.
 *
 * @var string
 */
	public $object = null;

/**
 * Error to display when user login fails.  For security purposes, only one error is used for all
 * login failures, so as not to expose information on why the login failed.
 *
 * @var string
 * @link http://book.cakephp.org/view/1272/loginError
 */
	public $loginError = null;

/**
 * Error to display when user attempts to access an object or action to which they do not have
 * acccess.
 *
 * @var string
 * @link http://book.cakephp.org/view/1273/authError
 */
	public $authError = null;

/**
 * Determines whether AuthComponent will automatically redirect and exit if login is successful.
 *
 * @var boolean
 * @link http://book.cakephp.org/view/1274/autoRedirect
 */
	public $autoRedirect = true;

/**
 * Controller actions for which user validation is not required.
 *
 * @var array
 * @see AuthComponent::allow()
 * @link http://book.cakephp.org/view/1251/Setting-Auth-Component-Variables
 */
	public $allowedActions = array();

/**
 * Maps actions to CRUD operations.  Used for controller-based validation ($validate = 'controller').
 *
 * @var array
 * @see AuthComponent::mapActions()
 */
	public $actionMap = array(
		'index'		=> 'read',
		'add'		=> 'create',
		'edit'		=> 'update',
		'view'		=> 'read',
		'remove'	=> 'delete'
	);

/**
 * Request object
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Form data from Controller::$data
 *
 * @deprecated Use $this->request->data instead
 * @var array
 */
	public $data = array();

/**
 * Parameter data from Controller::$params
 *
 * @deprecated Use $this->request instead
 * @var array
 */
	public $params = array();

/**
 * AclComponent instance if using Acl + Auth
 *
 * @var AclComponent
 */
	public $Acl;

/**
 * Method list for bound controller
 *
 * @var array
 */
	protected $_methods = array();

/**
 * Initializes AuthComponent for use in the controller
 *
 * @param object $controller A reference to the instantiating controller object
 * @return void
 */
	public function initialize($controller) {
		$this->request = $controller->request;
		$this->params = $this->request;

		$crud = array('create', 'read', 'update', 'delete');
		$this->actionMap = array_merge($this->actionMap, array_combine($crud, $crud));
		$this->_methods = $controller->methods;

		$prefixes = Router::prefixes();
		if (!empty($prefixes)) {
			foreach ($prefixes as $prefix) {
				$this->actionMap = array_merge($this->actionMap, array(
					$prefix . '_index' => 'read',
					$prefix . '_add' => 'create',
					$prefix . '_edit' => 'update',
					$prefix . '_view' => 'read',
					$prefix . '_remove' => 'delete',
					$prefix . '_create' => 'create',
					$prefix . '_read' => 'read',
					$prefix . '_update' => 'update',
					$prefix . '_delete' => 'delete'
				));
			}
		}
		if (Configure::read('debug') > 0) {
			App::import('Debugger');
			Debugger::checkSecurityKeys();
		}
	}

/**
 * Main execution method.  Handles redirecting of invalid users, and processing
 * of login form data.
 *
 * @param object $controller A reference to the instantiating controller object
 * @return boolean
 */
	public function startup($controller) {
		$isErrorOrTests = (
			strtolower($controller->name) == 'cakeerror' ||
			(strtolower($controller->name) == 'tests' && Configure::read('debug') > 0)
		);
		if ($isErrorOrTests) {
			return true;
		}

		$methods = array_flip($controller->methods);
		$action = $controller->request->params['action'];

		$isMissingAction = (
			$controller->scaffold === false &&
			!isset($methods[$action])
		);

		if ($isMissingAction) {
			return true;
		}

		if (!$this->__setDefaults()) {
			return false;
		}
		$request = $controller->request;
		
		$this->request->data = $controller->request->data = $this->hashPasswords($request->data);
		$url = '';

		if (isset($request->query['url'])) {
			$url = $request->query['url'];
		}
		$url = Router::normalize($url);
		$loginAction = Router::normalize($this->loginAction);

		$allowedActions = $this->allowedActions;
		$isAllowed = (
			$this->allowedActions == array('*') ||
			in_array($action, $allowedActions)
		);

		if ($loginAction != $url && $isAllowed) {
			return true;
		}

		if ($loginAction == $url) {
			$model = $this->getModel();
			if (empty($request->data) || !isset($request->data[$model->alias])) {
				if (!$this->Session->check('Auth.redirect') && !$this->loginRedirect && env('HTTP_REFERER')) {
					$this->Session->write('Auth.redirect', $controller->referer(null, true));
				}
				return false;
			}

			$isValid = !empty($request->data[$model->alias][$this->fields['username']]) &&
				!empty($request->data[$model->alias][$this->fields['password']]);

			if ($isValid) {
				$username = $request->data[$model->alias][$this->fields['username']];
				$password = $request->data[$model->alias][$this->fields['password']];

				$data = array(
					$model->alias . '.' . $this->fields['username'] => $username,
					$model->alias . '.' . $this->fields['password'] => $password
				);

				if ($this->login($data)) {
					if ($this->autoRedirect) {
						$controller->redirect($this->redirect(), null, true);
					}
					return true;
				}
			}

			$this->Session->setFlash($this->loginError, $this->flashElement, array(), 'auth');
			$request->data[$model->alias][$this->fields['password']] = null;
			return false;
		} else {
			if (!$this->user()) {
				if (!$request->is('ajax')) {
					$this->Session->setFlash($this->authError, $this->flashElement, array(), 'auth');
					if (!empty($request->query) && count($request->query) >= 2) {
						$query = $request->query;
						unset($query['url'], $query['ext']);
						$url .= Router::queryString($query, array());
					}
					$this->Session->write('Auth.redirect', $url);
					$controller->redirect($loginAction);
					return false;
				} elseif (!empty($this->ajaxLogin)) {
					$controller->viewPath = 'elements';
					echo $controller->render($this->ajaxLogin, $this->RequestHandler->ajaxLayout);
					$this->_stop();
					return false;
				} else {
					$controller->redirect(null, 403);
				}
			}
		}

		if (!$this->authorize) {
			return true;
		}

		extract($this->__authType());
		switch ($type) {
			case 'controller':
				$this->object = $controller;
			break;
			case 'crud':
			case 'actions':
				if (isset($controller->Acl)) {
					$this->Acl = $controller->Acl;
				} else {
					trigger_error(__('Could not find AclComponent. Please include Acl in Controller::$components.'), E_USER_WARNING);
				}
			break;
			case 'model':
				if (!isset($object)) {
					$hasModel = (
						isset($controller->{$controller->modelClass}) &&
						is_object($controller->{$controller->modelClass})
					);
					$isUses = (
						!empty($controller->uses) && isset($controller->{$controller->uses[0]}) &&
						is_object($controller->{$controller->uses[0]})
					);

					if ($hasModel) {
						$object = $controller->modelClass;
					} elseif ($isUses) {
						$object = $controller->uses[0];
					}
				}
				$type = array('model' => $object);
			break;
		}

		if ($this->isAuthorized($type)) {
			return true;
		}

		$this->Session->setFlash($this->authError, $this->flashElement, array(), 'auth');
		$controller->redirect($controller->referer(), null, true);
		return false;
	}

/**
 * Attempts to introspect the correct values for object properties including
 * $userModel and $sessionKey.
 *
 * @param object $controller A reference to the instantiating controller object
 * @return boolean
 * @access private
 */
	function __setDefaults() {
		if (empty($this->userModel)) {
			trigger_error(__("Could not find \$userModel. Please set AuthComponent::\$userModel in beforeFilter()."), E_USER_WARNING);
			return false;
		}
		list($plugin, $model) = pluginSplit($this->userModel);
		$defaults = array(
			'loginAction' => array(
				'controller' => Inflector::underscore(Inflector::pluralize($model)),
				'action' => 'login',
				'plugin' => Inflector::underscore($plugin),
			),
			'sessionKey' => 'Auth.' . $model,
			'logoutRedirect' => $this->loginAction,
			'loginError' => __('Login failed. Invalid username or password.'),
			'authError' => __('You are not authorized to access that location.')
		);
		foreach ($defaults as $key => $value) {
			if (empty($this->{$key})) {
				$this->{$key} = $value;
			}
		}
		return true;
	}

/**
 * Determines whether the given user is authorized to perform an action.  The type of
 * authorization used is based on the value of AuthComponent::$authorize or the
 * passed $type param.
 *
 * Types:
 * 'controller' will validate against Controller::isAuthorized() if controller instance is
 * 				passed in $object
 * 'actions' will validate Controller::action against an AclComponent::check()
 * 'crud' will validate mapActions against an AclComponent::check()
 * 		array('model'=> 'name'); will validate mapActions against model
 * 		$name::isAuthorized(user, controller, mapAction)
 * 'object' will validate Controller::action against
 * 		object::isAuthorized(user, controller, action)
 *
 * @param string $type Type of authorization
 * @param mixed $object object, model object, or model name
 * @param mixed $user The user to check the authorization of
 * @return boolean True if $user is authorized, otherwise false
 */
	public function isAuthorized($type = null, $object = null, $user = null) {
		if (empty($user) && !$this->user()) {
			return false;
		} elseif (empty($user)) {
			$user = $this->user();
		}

		extract($this->__authType($type));

		if (!$object) {
			$object = $this->object;
		}

		$valid = false;
		switch ($type) {
			case 'controller':
				$valid = $object->isAuthorized();
			break;
			case 'actions':
				$valid = $this->Acl->check($user, $this->action());
			break;
			case 'crud':
				if (!isset($this->actionMap[$this->request['action']])) {
					trigger_error(
						__('Auth::startup() - Attempted access of un-mapped action "%1$s" in controller "%2$s"', $this->request['action'], $this->request['controller']),
						E_USER_WARNING
					);
				} else {
					$valid = $this->Acl->check(
						$user,
						$this->action(':controller'),
						$this->actionMap[$this->request['action']]
					);
				}
			break;
			case 'model':
				$action = $this->request['action'];
				if (isset($this->actionMap[$action])) {
					$action = $this->actionMap[$action];
				}
				if (is_string($object)) {
					$object = $this->getModel($object);
				}
			case 'object':
				if (!isset($action)) {
					$action = $this->action(':action');
				}
				if (empty($object)) {
					trigger_error(__('Could not find %s. Set AuthComponent::$object in beforeFilter() or pass a valid object', get_class($object)), E_USER_WARNING);
					return;
				}
				if (method_exists($object, 'isAuthorized')) {
					$valid = $object->isAuthorized($user, $this->action(':controller'), $action);
				} elseif ($object) {
					trigger_error(__('%s::isAuthorized() is not defined.', get_class($object)), E_USER_WARNING);
				}
			break;
			case null:
			case false:
				return true;
			break;
			default:
				trigger_error(__('Auth::isAuthorized() - $authorize is set to an incorrect value.  Allowed settings are: "actions", "crud", "model" or null.'), E_USER_WARNING);
			break;
		}
		return $valid;
	}

/**
 * Get authorization type
 *
 * @param string $auth Type of authorization
 * @return array Associative array with: type, object
 * @access private
 */
	function __authType($auth = null) {
		if ($auth == null) {
			$auth = $this->authorize;
		}
		$object = null;
		if (is_array($auth)) {
			$type = key($auth);
			$object = $auth[$type];
		} else {
			$type = $auth;
			return compact('type');
		}
		return compact('type', 'object');
	}

/**
 * Takes a list of actions in the current controller for which authentication is not required, or
 * no parameters to allow all actions.
 *
 * @param mixed $action Controller action name or array of actions
 * @param string $action Controller action name
 * @param string ... etc.
 * @return void
 * @link http://book.cakephp.org/view/1257/allow
 */
	public function allow() {
		$args = func_get_args();
		if (empty($args) || $args == array('*')) {
			$this->allowedActions = $this->_methods;
		} else {
			if (isset($args[0]) && is_array($args[0])) {
				$args = $args[0];
			}
			$this->allowedActions = array_merge($this->allowedActions, $args);
		}
	}

/**
 * Removes items from the list of allowed actions.
 *
 * @param mixed $action Controller action name or array of actions
 * @param string $action Controller action name
 * @param string ... etc.
 * @return void
 * @see AuthComponent::allow()
 * @link http://book.cakephp.org/view/1258/deny
 */
	public function deny() {
		$args = func_get_args();
		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}
		foreach ($args as $arg) {
			$i = array_search($arg, $this->allowedActions);
			if (is_int($i)) {
				unset($this->allowedActions[$i]);
			}
		}
		$this->allowedActions = array_values($this->allowedActions);
	}

/**
 * Maps action names to CRUD operations. Used for controller-based authentication.
 *
 * @param array $map Actions to map
 * @return void
 * @link http://book.cakephp.org/view/1260/mapActions
 */
	public function mapActions($map = array()) {
		$crud = array('create', 'read', 'update', 'delete');
		foreach ($map as $action => $type) {
			if (in_array($action, $crud) && is_array($type)) {
				foreach ($type as $typedAction) {
					$this->actionMap[$typedAction] = $action;
				}
			} else {
				$this->actionMap[$action] = $type;
			}
		}
	}

/**
 * Manually log-in a user with the given parameter data.  The $data provided can be any data
 * structure used to identify a user in AuthComponent::identify().  If $data is empty or not
 * specified, POST data from Controller::$data will be used automatically.
 *
 * After (if) login is successful, the user record is written to the session key specified in
 * AuthComponent::$sessionKey.
 *
 * @param mixed $data User object
 * @return boolean True on login success, false on failure
 * @link http://book.cakephp.org/view/1261/login
 */
	public function login($data = null) {
		$this->__setDefaults();
		$this->_loggedIn = false;

		if (empty($data)) {
			$data = $this->data;
		}

		if ($user = $this->identify($data)) {
			$this->Session->write($this->sessionKey, $user);
			$this->_loggedIn = true;
		}
		return $this->_loggedIn;
	}

/**
 * Logs a user out, and returns the login action to redirect to.
 *
 * @param mixed $url Optional URL to redirect the user to after logout
 * @return string AuthComponent::$loginAction
 * @see AuthComponent::$loginAction
 * @link http://book.cakephp.org/view/1262/logout
 */
	public function logout() {
		$this->__setDefaults();
		$this->Session->delete($this->sessionKey);
		$this->Session->delete('Auth.redirect');
		$this->_loggedIn = false;
		return Router::normalize($this->logoutRedirect);
	}

/**
 * Get the current user from the session.
 *
 * @param string $key field to retrive.  Leave null to get entire User record
 * @return mixed User record. or null if no user is logged in.
 * @link http://book.cakephp.org/view/1264/user
 */
	public function user($key = null) {
		$this->__setDefaults();
		if (!$this->Session->check($this->sessionKey)) {
			return null;
		}

		if ($key == null) {
			$model = $this->getModel();
			return array($model->alias => $this->Session->read($this->sessionKey));
		} else {
			$user = $this->Session->read($this->sessionKey);
			if (isset($user[$key])) {
				return $user[$key];
			}
			return null;
		}
	}

/**
 * If no parameter is passed, gets the authentication redirect URL.
 *
 * @param mixed $url Optional URL to write as the login redirect URL.
 * @return string Redirect URL
 */
	public function redirect($url = null) {
		if (!is_null($url)) {
			$redir = $url;
			$this->Session->write('Auth.redirect', $redir);
		} elseif ($this->Session->check('Auth.redirect')) {
			$redir = $this->Session->read('Auth.redirect');
			$this->Session->delete('Auth.redirect');

			if (Router::normalize($redir) == Router::normalize($this->loginAction)) {
				$redir = $this->loginRedirect;
			}
		} else {
			$redir = $this->loginRedirect;
		}
		return Router::normalize($redir);
	}

/**
 * Validates a user against an abstract object.
 *
 * @param mixed $object  The object to validate the user against.
 * @param mixed $user    Optional.  The identity of the user to be validated.
 *                       Uses the current user session if none specified.  For
 *                       valid forms of identifying users, see
 *                       AuthComponent::identify().
 * @param string $action Optional. The action to validate against.
 * @see AuthComponent::identify()
 * @return boolean True if the user validates, false otherwise.
 */
	public function validate($object, $user = null, $action = null) {
		if (empty($user)) {
			$user = $this->user();
		}
		if (empty($user)) {
			return false;
		}
		return $this->Acl->check($user, $object, $action);
	}

/**
 * Returns the path to the ACO node bound to a controller/action.
 *
 * @param string $action  Optional.  The controller/action path to validate the
 *                        user against.  The current request action is used if
 *                        none is specified.
 * @return boolean ACO node path
 * @link http://book.cakephp.org/view/1256/action
 */
	public function action($action = ':plugin/:controller/:action') {
		$plugin = empty($this->request['plugin']) ? null : Inflector::camelize($this->request['plugin']) . '/';
		return str_replace(
			array(':controller', ':action', ':plugin/'),
			array(Inflector::camelize($this->request['controller']), $this->request['action'], $plugin),
			$this->actionPath . $action
		);
	}

/**
 * Returns a reference to the model object specified, and attempts
 * to load it if it is not found.
 *
 * @param string $name Model name (defaults to AuthComponent::$userModel)
 * @return object A reference to a model object
 */
	public function &getModel($name = null) {
		$model = null;
		if (!$name) {
			$name = $this->userModel;
		}

		$model = ClassRegistry::init($name);

		if (empty($model)) {
			trigger_error(__('Auth::getModel() - Model is not set or could not be found'), E_USER_WARNING);
			return null;
		}

		return $model;
	}

/**
 * Identifies a user based on specific criteria.
 *
 * @param mixed $user Optional. The identity of the user to be validated.
 *              Uses the current user session if none specified.
 * @param array $conditions Optional. Additional conditions to a find.
 * @return array User record data, or null, if the user could not be identified.
 */
	public function identify($user = null, $conditions = null) {
		if ($conditions === false) {
			$conditions = null;
		} elseif (is_array($conditions)) {
			$conditions = array_merge((array)$this->userScope, $conditions);
		} else {
			$conditions = $this->userScope;
		}
		$model = $this->getModel();
		if (empty($user)) {
			$user = $this->user();
			if (empty($user)) {
				return null;
			}
		} elseif (is_object($user) && is_a($user, 'Model')) {
			if (!$user->exists()) {
				return null;
			}
			$user = $user->read();
			$user = $user[$model->alias];
		} elseif (is_array($user) && isset($user[$model->alias])) {
			$user = $user[$model->alias];
		}

		if (is_array($user) && (isset($user[$this->fields['username']]) || isset($user[$model->alias . '.' . $this->fields['username']]))) {
			if (isset($user[$this->fields['username']]) && !empty($user[$this->fields['username']])  && !empty($user[$this->fields['password']])) {
				if (trim($user[$this->fields['username']]) == '=' || trim($user[$this->fields['password']]) == '=') {
					return false;
				}
				$find = array(
					$model->alias.'.'.$this->fields['username'] => $user[$this->fields['username']],
					$model->alias.'.'.$this->fields['password'] => $user[$this->fields['password']]
				);
			} elseif (isset($user[$model->alias . '.' . $this->fields['username']]) && !empty($user[$model->alias . '.' . $this->fields['username']])) {
				if (trim($user[$model->alias . '.' . $this->fields['username']]) == '=' || trim($user[$model->alias . '.' . $this->fields['password']]) == '=') {
					return false;
				}
				$find = array(
					$model->alias.'.'.$this->fields['username'] => $user[$model->alias . '.' . $this->fields['username']],
					$model->alias.'.'.$this->fields['password'] => $user[$model->alias . '.' . $this->fields['password']]
				);
			} else {
				return false;
			}
			$data = $model->find('first', array(
				'conditions' => array_merge($find, $conditions),
				'recursive' => 0
			));
			if (empty($data) || empty($data[$model->alias])) {
				return null;
			}
		} elseif (!empty($user) && is_string($user)) {
			$data = $model->find('first', array(
				'conditions' => array_merge(array($model->escapeField() => $user), $conditions),
			));
			if (empty($data) || empty($data[$model->alias])) {
				return null;
			}
		}

		if (!empty($data)) {
			if (!empty($data[$model->alias][$this->fields['password']])) {
				unset($data[$model->alias][$this->fields['password']]);
			}
			return $data[$model->alias];
		}
		return null;
	}

/**
 * Hash any passwords found in $data using $userModel and $fields['password']
 *
 * @param array $data Set of data to look for passwords
 * @return array Data with passwords hashed
 * @link http://book.cakephp.org/view/1259/hashPasswords
 */
	public function hashPasswords($data) {
		if (is_object($this->authenticate) && method_exists($this->authenticate, 'hashPasswords')) {
			return $this->authenticate->hashPasswords($data);
		}

		if (is_array($data)) {
			$model = $this->getModel();
			
			if(isset($data[$model->alias])) {
				if (isset($data[$model->alias][$this->fields['username']]) && isset($data[$model->alias][$this->fields['password']])) {
					$data[$model->alias][$this->fields['password']] = $this->password($data[$model->alias][$this->fields['password']]);
				}
			}
		}
		return $data;
	}

/**
 * Hash a password with the application's salt value (as defined with Configure::write('Security.salt');
 *
 * @param string $password Password to hash
 * @return string Hashed password
 * @link http://book.cakephp.org/view/1263/password
 */
	public function password($password) {
		return Security::hash($password, null, true);
	}

/**
 * Component shutdown.  If user is logged in, wipe out redirect.
 *
 * @param object $controller Instantiating controller
 */
	public function shutdown($controller) {
		if ($this->_loggedIn) {
			$this->Session->delete('Auth.redirect');
		}
	}

/**
 * Sets or gets whether the user is logged in
 *
 * @param boolean $logged sets the status of the user, true to logged in, false to logged out
 * @return boolean true if the user is logged in, false otherwise
 * @access public
 */
	public function loggedIn($logged = null) {
		if (!is_null($logged)) {
			$this->_loggedIn = $logged;
		}
		return $this->_loggedIn;
	}
}
