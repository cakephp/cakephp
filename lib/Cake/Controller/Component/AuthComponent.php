<?php
/**
 * Authentication component
 *
 * Manages user logins and permissions.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.controller.components
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


App::uses('Component', 'Controller');
App::uses('Router', 'Routing');
App::uses('Security', 'Utility');
App::uses('Debugger', 'Utility');
App::uses('CakeSession', 'Model/Datasource');
App::uses('BaseAuthorize', 'Controller/Component/Auth');
App::uses('BaseAuthenticate', 'Controller/Component/Auth');

/**
 * Authentication control component class
 *
 * Binds access control with user authentication and session management.
 *
 * @package       cake.libs.controller.components
 * @link http://book.cakephp.org/view/1250/Authentication
 */
class AuthComponent extends Component {

	const ALL = 'all';

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
 * {{{
 *	$this->Auth->authenticate = array(
 *		'Form' => array(
 *			'userModel' => 'Users.User'
 *		)
 *	);
 * }}}
 *
 * Using the class name without 'Authenticate' as the key, you can pass in an array of settings for each
 * authentication object.  Additionally you can define settings that should be set to all authentications objects
 * using the 'all' key:
 *
 * {{{
 *	$this->Auth->authenticate = array(
 *		'all' => array(
 *			'userModel' => 'Users.User',
 *			'scope' => array('User.active' => 1)
 *		),
 *		'Form',
 *		'Basic'
 *	);
 * }}}
 *
 * You can also use AuthComponent::ALL instead of the string 'all'.
 *
 * @var array
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
 * An array of authorization objects to use for authorizing users.  You can configure
 * multiple adapters and they will be checked sequentially when authorization checks are done.
 *
 * {{{
 *	$this->Auth->authorize = array(
 *		'Crud' => array(
 *			'actionPath' => 'controllers/'
 *		)
 *	);
 * }}}
 *
 * Using the class name without 'Authorize' as the key, you can pass in an array of settings for each
 * authorization object.  Additionally you can define settings that should be set to all authorization objects
 * using the 'all' key:
 *
 * {{{
 *	$this->Auth->authorize = array(
 *		'all' => array(
 *			'actionPath' => 'controllers/'
 *		),
 *		'Crud',
 *		'CustomAuth'
 *	);
 * }}}
 *
 * You can also use AuthComponent::ALL instead of the string 'all'
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
 * Settings to use when Auth needs to do a flash message with SessionComponent::setFlash().
 * Available keys are:
 *
 * - `element` - The element to use, defaults to 'default'.
 * - `key` - The key to use, defaults to 'auth'
 * - `params` - The array of additional params to use, defaults to array()
 *
 * @var array
 */
	public $flash = array(
		'element' => 'default',
		'key' => 'auth',
		'params' => array()
	);

/**
 * The session key name where the record of the current user is stored.  If
 * unspecified, it will be "Auth.User".
 *
 * @var string
 * @link http://book.cakephp.org/view/1276/sessionKey
 */
	public static $sessionKey = 'Auth.User';

/**
 * A URL (defined as a string or array) to the controller action that handles
 * logins.  Defaults to `/users/login`
 *
 * @var mixed
 * @link http://book.cakephp.org/view/1269/loginAction
 */
	public $loginAction = array(
		'controller' => 'users',
		'action' => 'login',
		'plugin' => null
	);

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
 * Error to display when user attempts to access an object or action to which they do not have
 * acccess.
 *
 * @var string
 * @link http://book.cakephp.org/view/1273/authError
 */
	public $authError = null;

/**
 * Controller actions for which user validation is not required.
 *
 * @var array
 * @see AuthComponent::allow()
 * @link http://book.cakephp.org/view/1251/Setting-Auth-Component-Variables
 */
	public $allowedActions = array();

/**
 * Request object
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Response object
 *
 * @var CakeResponse
 */
	public $response;

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
		$this->response = $controller->response;
		$this->_methods = $controller->methods;

		if (Configure::read('debug') > 0) {
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
		if ($controller->name == 'CakeError') {
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

		$url = '';

		if (isset($request->url)) {
			$url = $request->url;
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
			if (empty($request->data)) {
				if (!$this->Session->check('Auth.redirect') && !$this->loginRedirect && env('HTTP_REFERER')) {
					$this->Session->write('Auth.redirect', $controller->referer(null, true));
				}
			}
			return true;
		} else {
			if (!$this->_getUser()) {
				if (!$request->is('ajax')) {
					$this->flash($this->authError);
					$this->Session->write('Auth.redirect', Router::reverse($request));
					$controller->redirect($loginAction);
					return false;
				} elseif (!empty($this->ajaxLogin)) {
					$controller->viewPath = 'Elements';
					echo $controller->render($this->ajaxLogin, $this->RequestHandler->ajaxLayout);
					$this->_stop();
					return false;
				} else {
					$controller->redirect(null, 403);
				}
			}
		}
		if (empty($this->authorize) || $this->isAuthorized($this->user())) {
			return true;
		}

		$this->flash($this->authError);
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
		$defaults = array(
			'logoutRedirect' => $this->loginAction,
			'authError' => __d('cake', 'You are not authorized to access that location.')
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
			$this->constructAuthorize();
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
	public function constructAuthorize() {
		if (empty($this->authorize)) {
			return;
		}
		$this->_authorizeObjects = array();
		$config = Set::normalize($this->authorize);
		$global = array();
		if (isset($config[AuthComponent::ALL])) {
			$global = $config[AuthComponent::ALL];
			unset($config[AuthComponent::ALL]);
		}
		foreach ($config as $class => $settings) {
			list($plugin, $class) = pluginSplit($class, true);
			$className = $class . 'Authorize';
			App::uses($className, $plugin . 'Controller/Component/Auth');
			if (!class_exists($className)) {
				throw new CakeException(__d('cake_dev', 'Authorization adapter "%s" was not found.', $class));
			}
			if (!method_exists($className, 'authorize')) {
				throw new CakeException(__d('cake_dev', 'Authorization objects must implement an authorize method.'));
			}
			$settings = array_merge($global, (array)$settings);
			$this->_authorizeObjects[] = new $className($this->_Collection, $settings);
		}
		return $this->_authorizeObjects;
	}

/**
 * Takes a list of actions in the current controller for which authentication is not required, or
 * no parameters to allow all actions.
 *
 * You can use allow with either an array, or var args.
 *
 * `$this->Auth->allow(array('edit', 'add'));` or
 * `$this->Auth->allow('edit', 'add');`
 *
 * allow() also supports '*' as a wildcard to mean all actions.
 *
 * `$this->Auth->allow('*');`
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
 * Removes items from the list of allowed/no authentication required actions.
 *
 * You can use deny with either an array, or var args.
 *
 * `$this->Auth->deny(array('edit', 'add'));` or
 * `$this->Auth->deny('edit', 'add');`
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
			$this->constructAuthorize();
		}
		foreach ($this->_authorizeObjects as $auth) {
			$auth->mapActions($map);
		}
	}

/**
 * Log a user in. If a $user is provided that data will be stored as the logged in user.  If `$user` is empty or not
 * specified, the request will be used to identify a user. If the identification was successful,
 * the user record is written to the session key specified in AuthComponent::$sessionKey.
 *
 * @param mixed $user Either an array of user data, or null to identify a user using the current request.
 * @return boolean True on login success, false on failure
 * @link http://book.cakephp.org/view/1261/login
 */
	public function login($user = null) {
		$this->__setDefaults();

		if (empty($user)) {
			$user = $this->identify($this->request, $this->response);
		}
		if ($user) {
			$this->Session->write(self::$sessionKey, $user);
		}
		return $this->loggedIn();
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
		$this->Session->delete(self::$sessionKey);
		$this->Session->delete('Auth.redirect');
		return Router::normalize($this->logoutRedirect);
	}

/**
 * Get the current user from the session.
 *
 * @param string $key field to retrive.  Leave null to get entire User record
 * @return mixed User record. or null if no user is logged in.
 * @link http://book.cakephp.org/view/1264/user
 */
	public static function user($key = null) {
		if (!CakeSession::check(self::$sessionKey)) {
			return null;
		}

		if ($key == null) {
			return CakeSession::read(self::$sessionKey);
		}

		$user = CakeSession::read(self::$sessionKey);
		if (isset($user[$key])) {
			return $user[$key];
		}
		return null;
	}

/**
 * Similar to AuthComponent::user() except if the session user cannot be found, connected authentication
 * objects will have their getUser() methods called.  This lets stateless authentication methods function correctly.
 *
 * @return boolean true if a user can be found, false if one cannot.
 */
	protected function _getUser() {
		$user = $this->user();
		if ($user) {
			return true;
		}
		if (empty($this->_authenticateObjects)) {
			$this->constructAuthenticate();
		}
		foreach ($this->_authenticateObjects as $auth) {
			$result = $auth->getUser($this->request);
			if (!empty($result) && is_array($result)) {
				return true;
			}
		}
		return false;
	}

/**
 * If no parameter is passed, gets the authentication redirect URL.  Pass a url in to
 * set the destination a user should be redirected to upon logging in.  Will fallback to
 * AuthComponent::$loginRedirect if there is no stored redirect value.
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
 * Use the configured authentication adapters, and attempt to identify the user
 * by credentials contained in $request.
 *
 * @param CakeRequest $request The request that contains authentication data.
 * @return array User record data, or false, if the user could not be identified.
 */
	public function identify(CakeRequest $request, CakeResponse $response) {
		if (empty($this->_authenticateObjects)) {
			$this->constructAuthenticate();
		}
		foreach ($this->_authenticateObjects as $auth) {
			$result = $auth->authenticate($request, $response);
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
	public function constructAuthenticate() {
		if (empty($this->authenticate)) {
			return;
		}
		$this->_authenticateObjects = array();
		$config = Set::normalize($this->authenticate);
		$global = array();
		if (isset($config[AuthComponent::ALL])) {
			$global = $config[AuthComponent::ALL];
			unset($config[AuthComponent::ALL]);
		}
		foreach ($config as $class => $settings) {
			list($plugin, $class) = pluginSplit($class, true);
			$className = $class . 'Authenticate';
			App::uses($className, $plugin . 'Controller/Component/Auth');
			if (!class_exists($className)) {
				throw new CakeException(__d('cake_dev', 'Authentication adapter "%s" was not found.', $class));
			}
			if (!method_exists($className, 'authenticate')) {
				throw new CakeException(__d('cake_dev', 'Authentication objects must implement an authenticate method.'));
			}
			$settings = array_merge($global, (array)$settings);
			$this->_authenticateObjects[] = new $className($this->_Collection, $settings);
		}
		return $this->_authenticateObjects;
	}

/**
 * Hash a password with the application's salt value (as defined with Configure::write('Security.salt');
 *
 * @param string $password Password to hash
 * @return string Hashed password
 * @link http://book.cakephp.org/view/1263/password
 */
	public static function password($password) {
		return Security::hash($password, null, true);
	}

/**
 * Component shutdown.  If user is logged in, wipe out redirect.
 *
 * @param object $controller Instantiating controller
 */
	public function shutdown($controller) {
		if ($this->loggedIn()) {
			$this->Session->delete('Auth.redirect');
		}
	}

/**
 * Check whether or not the current user has data in the session, and is considered logged in.
 *
 * @return boolean true if the user is logged in, false otherwise
 * @access public
 */
	public function loggedIn() {
		return $this->user() != array();
	}

/**
 * Set a flash message.  Uses the Session component, and values from AuthComponent::$flash.
 *
 * @param string $message The message to set.
 * @return void
 */
	public function flash($message) {
		$this->Session->setFlash($message, $this->flash['element'], $this->flash['params'], $this->flash['key']);
	}
}
