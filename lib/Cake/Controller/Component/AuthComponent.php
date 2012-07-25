<?php
/**
 * Authentication component
 *
 * Manages user logins and permissions.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Component', 'Controller');
App::uses('Router', 'Routing');
App::uses('Security', 'Utility');
App::uses('Debugger', 'Utility');
App::uses('Hash', 'Utility');
App::uses('CakeSession', 'Model/Datasource');
App::uses('BaseAuthorize', 'Controller/Component/Auth');
App::uses('BaseAuthenticate', 'Controller/Component/Auth');

/**
 * Authentication control component class
 *
 * Binds access control with user authentication and session management.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#authorization
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
 */
	public static $sessionKey = 'Auth.User';

/**
 * The current user, used for stateless authentication when
 * sessions are not available.
 *
 * @var array
 */
	protected static $_user = array();

/**
 * A URL (defined as a string or array) to the controller action that handles
 * logins.  Defaults to `/users/login`
 *
 * @var mixed
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
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#AuthComponent::$loginRedirect
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
 */
	public $logoutRedirect = null;

/**
 * Error to display when user attempts to access an object or action to which they do not have
 * access.
 *
 * @var string
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#AuthComponent::$authError
 */
	public $authError = null;

/**
 * Controller actions for which user validation is not required.
 *
 * @var array
 * @see AuthComponent::allow()
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
 * @param Controller $controller A reference to the instantiating controller object
 * @return void
 */
	public function initialize(Controller $controller) {
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
 * @param Controller $controller A reference to the instantiating controller object
 * @return boolean
 */
	public function startup(Controller $controller) {
		$methods = array_flip(array_map('strtolower', $controller->methods));
		$action = strtolower($controller->request->params['action']);

		$isMissingAction = (
			$controller->scaffold === false &&
			!isset($methods[$action])
		);

		if ($isMissingAction) {
			return true;
		}

		if (!$this->_setDefaults()) {
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
			in_array($action, array_map('strtolower', $allowedActions))
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
					$this->Session->write('Auth.redirect', $request->here());
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
		$default = '/';
		if (!empty($this->loginRedirect)) {
			$default = $this->loginRedirect;
		}
		$controller->redirect($controller->referer($default), null, true);
		return false;
	}

/**
 * Attempts to introspect the correct values for object properties.
 *
 * @return boolean
 */
	protected function _setDefaults() {
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
 * @param array $user The user to check the authorization of. If empty the user in the session will be used.
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
 * @throws CakeException
 */
	public function constructAuthorize() {
		if (empty($this->authorize)) {
			return;
		}
		$this->_authorizeObjects = array();
		$config = Hash::normalize((array)$this->authorize);
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
 * `$this->Auth->allow('edit', 'add');` or
 * `$this->Auth->allow();` to allow all actions
 *
 * @param string|array $action,... Controller action name or array of actions
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#making-actions-public
 */
	public function allow($action = null) {
		$args = func_get_args();
		if (empty($args) || $action === null) {
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
 * `$this->Auth->deny('edit', 'add');` or
 * `$this->Auth->deny();` to remove all items from the allowed list
 *
 * @param string|array $action,... Controller action name or array of actions
 * @return void
 * @see AuthComponent::allow()
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#making-actions-require-authorization
 */
	public function deny($action = null) {
		$args = func_get_args();
		if (empty($args) || $action === null) {
			$this->allowedActions = array();
		} else {
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
	}

/**
 * Maps action names to CRUD operations. Used for controller-based authentication.  Make sure
 * to configure the authorize property before calling this method. As it delegates $map to all the
 * attached authorize objects.
 *
 * @param array $map Actions to map
 * @return void
 * @see BaseAuthorize::mapActions()
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#mapping-actions-when-using-crudauthorize
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
 * the user record is written to the session key specified in AuthComponent::$sessionKey. Logging in
 * will also change the session id in order to help mitigate session replays.
 *
 * @param array $user Either an array of user data, or null to identify a user using the current request.
 * @return boolean True on login success, false on failure
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#identifying-users-and-logging-them-in
 */
	public function login($user = null) {
		$this->_setDefaults();

		if (empty($user)) {
			$user = $this->identify($this->request, $this->response);
		}
		if ($user) {
			$this->Session->renew();
			$this->Session->write(self::$sessionKey, $user);
		}
		return $this->loggedIn();
	}

/**
 * Logs a user out, and returns the login action to redirect to.
 * Triggers the logout() method of all the authenticate objects, so they can perform
 * custom logout logic.  AuthComponent will remove the session data, so
 * there is no need to do that in an authentication object.  Logging out
 * will also renew the session id.  This helps mitigate issues with session replays.
 *
 * @return string AuthComponent::$logoutRedirect
 * @see AuthComponent::$logoutRedirect
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#logging-users-out
 */
	public function logout() {
		$this->_setDefaults();
		if (empty($this->_authenticateObjects)) {
			$this->constructAuthenticate();
		}
		$user = $this->user();
		foreach ($this->_authenticateObjects as $auth) {
			$auth->logout($user);
		}
		$this->Session->delete(self::$sessionKey);
		$this->Session->delete('Auth.redirect');
		$this->Session->renew();
		return Router::normalize($this->logoutRedirect);
	}

/**
 * Get the current user.
 *
 * Will prefer the static user cache over sessions.  The static user
 * cache is primarily used for stateless authentication.  For stateful authentication,
 * cookies + sessions will be used.
 *
 * @param string $key field to retrieve.  Leave null to get entire User record
 * @return mixed User record. or null if no user is logged in.
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#accessing-the-logged-in-user
 */
	public static function user($key = null) {
		if (empty(self::$_user) && !CakeSession::check(self::$sessionKey)) {
			return null;
		}
		if (!empty(self::$_user)) {
			$user = self::$_user;
		} else {
			$user = CakeSession::read(self::$sessionKey);
		}
		if ($key === null) {
			return $user;
		}
		return Hash::get($user, $key);
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
				self::$_user = $result;
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
 * @param string|array $url Optional URL to write as the login redirect URL.
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
 * @param CakeResponse $response The response
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
 * @throws CakeException
 */
	public function constructAuthenticate() {
		if (empty($this->authenticate)) {
			return;
		}
		$this->_authenticateObjects = array();
		$config = Hash::normalize((array)$this->authenticate);
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
 * This method is intended as a convenience wrapper for Security::hash().  If you want to use
 * a hashing/encryption system not supported by that method, do not use this method.
 *
 * @param string $password Password to hash
 * @return string Hashed password
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html#hashing-passwords
 */
	public static function password($password) {
		return Security::hash($password, null, true);
	}

/**
 * Component shutdown.  If user is logged in, wipe out redirect.
 *
 * @param Controller $controller Instantiating controller
 * @return void
 */
	public function shutdown(Controller $controller) {
		if ($this->loggedIn()) {
			$this->Session->delete('Auth.redirect');
		}
	}

/**
 * Check whether or not the current user has data in the session, and is considered logged in.
 *
 * @return boolean true if the user is logged in, false otherwise
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
