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
App::import('Component', 'auth/base_authorize');

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
 * An array of authentication objects to use for authenticating users.  You can configure
 * multiple adapters and they will be checked sequentially when users are identified.
 *
 * @var object
 * @link http://book.cakephp.org/view/1278/authenticate
 */
	public $authenticate = array('Form');

/**
 * Objects that will be used for authentication checks.
 *
 * @var array
 */
	protected $_authenticateObjects = array();

/**
 * A hash mapping legacy properties => to settings passed into Authenticate objects.
 *
 * @var string
 * @deprecated Will be removed in 2.1+
 */
	protected $_authenticateLegacyMap = array(
		'userModel' => 'userModel',
		'userScope' => 'scope',
		'fields' => 'fields'
	);

/**
 * An array of authorization objects to use for authorizing users.  You can configure
 * multiple adapters and they will be checked sequentially when authorization checks are done.
 *
 * @var mixed
 * @link http://book.cakephp.org/view/1275/authorize
 */
	public $authorize = false;

/**
 * Objects that will be used for authorization checks.
 *
 * @var array
 */
	protected $_authorizeObjects = array();

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
				if ($this->login()) {
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
		
		if ($this->isAuthorized()) {
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
 * Uses the configured Authorization adapters to check whether or not a user is authorized.
 * Each adapter will be checked in sequence, if any of them return true, then the user will 
 * be authorized for the request.
 *
 * @param mixed $user The user to check the authorization of. If empty the user in the session will be used.
 * @param CakeRequest $request The request to authenticate for.  If empty, the current request will be used.
 * @return boolean True if $user is authorized, otherwise false
 */
	public function isAuthorized($user = null, $request = null) {
		if (empty($user) && !$this->user()) {
			return false;
		} elseif (empty($user)) {
			$user = $this->user();
		}
		if (empty($request)) {
			$request = $this->request;
		}
		if (empty($this->_authorizeObjects)) {
			$this->loadAuthorizeObjects();
		}
		foreach ($this->_authorizeObjects as $authorizer) {
			if ($authorizer->authorize($user, $request) === true) {
				return true;
			}
		}
		return false;
	}

/**
 * Loads the authorization objects configured.
 *
 * @return mixed Either null when authorize is empty, or the loaded authorization objects.
 */
	public function loadAuthorizeObjects() {
		if (empty($this->authorize)) {
			return;
		}
		$this->_authorizeObjects = array();
		foreach (Set::normalize($this->authorize) as $class => $settings) {
			$className = $class . 'Authorize';
			if (!class_exists($className) && !App::import('Component', 'auth/' . $class . '_authorize')) {
				throw new CakeException(__('Authorization adapter "%s" was not found.', $class));
			}
			if (!method_exists($className, 'authorize')) {
				throw new CakeException(__('Authorization objects must implement an authorize method.'));
			}
			$this->_authorizeObjects[] = new $className($this->_Collection->getController(), $settings);
		}
		return $this->_authorizeObjects;
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
 * Maps action names to CRUD operations. Used for controller-based authentication.  Make sure
 * to configure the authorize property before calling this method. As it delegates $map to all the
 * attached authorize objects.
 *
 * @param array $map Actions to map
 * @return void
 * @link http://book.cakephp.org/view/1260/mapActions
 */
	public function mapActions($map = array()) {
		if (empty($this->_authorizeObjects)) {
			$this->loadAuthorizeObjects();
		}
		foreach ($this->_authorizeObjects as $auth) {
			$auth->mapActions($map);
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
	public function login($request = null) {
		$this->__setDefaults();
		$this->_loggedIn = false;

		if (empty($request)) {
			$request = $this->request;
		}
		if ($user = $this->identify($request)) {
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
 * Use the configured authentication adapters, and attempt to identify the user
 * by credentials contained in $request.
 *
 * @param CakeRequest $request The request that contains authentication data.
 * @return array User record data, or false, if the user could not be identified.
 */
	public function identify(CakeRequest $request) {
		if (empty($this->_authenticateObjects)) {
			$this->loadAuthenticateObjects();
		}
		foreach ($this->_authenticateObjects as $auth) {
			$result = $auth->authenticate($request);
			if (!empty($result) && is_array($result)) {
				return $result;
			}
		}
		return false;
	}

/**
 * loads the configured authentication objects.
 *
 * @return mixed either null on empty authenticate value, or an array of loaded objects.
 */
	public function loadAuthenticateObjects() {
		if (empty($this->authenticate)) {
			return;
		}
		$this->_authenticateObjects = array();
		foreach (Set::normalize($this->authenticate) as $class => $settings) {
			$className = $class . 'Authenticate';
			if (!class_exists($className) && !App::import('Component', 'auth/' . $class . '_authenticate')) {
				throw new CakeException(__('Authentication adapter "%s" was not found.', $class));
			}
			if (!method_exists($className, 'authenticate')) {
				throw new CakeException(__('Authentication objects must implement an authenticate method.'));
			}
			foreach ($this->_authenticateLegacyMap as $old => $new) {
				if (empty($settings[$new]) && !empty($this->{$old})) {
					$settings[$new] = $this->{$old};
				}
			}
			$this->_authenticateObjects[] = new $className($settings);
		}
		return $this->_authenticateObjects;
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
