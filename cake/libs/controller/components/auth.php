<?php
/* SVN FILE: $Id$ */

/**
 * Authentication component
 *
 * Manages user logins and permissions.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('set', 'security');

/**
 * Authentication control component class
 *
 * Binds access control with user authentication and session management.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 */
class AuthComponent extends Object {
/**
 * Maintains current user login state.
 *
 * @var boolean
 * @access private
 */
	var $_loggedIn = false;

/**
 * Other components utilized by AuthComponent
 *
 * @var array
 * @access public
 */
	var $components = array('Session', 'RequestHandler');
/**
 * The name of the component to use for Authorization or set this to
 * 'controller' will validate Controller::action against Controller::isAuthorized(user, controller, action)
 * 'actions' will validate Controller::action against an AclComponent::check()
 * 'crud' will validate mapActions against an AclComponent::check()
 * array('model'=> 'name'); will validate mapActions against model $name::isAuthorize(user, controller, mapAction)
 * 'object' will validate Controller::action against object::isAuthorized(user, controller, action)
 *
 * @var string
 * @access public
 */
	var $authorize = false;
/**
 * The name of an optional view element to render when an Ajax request is made
 * with an invalid or expired session
 *
 * @var string
 * @access public
 */
	var $ajaxLogin = null;
/**
 * The name of the model that represents users which will be authenticated.  Defaults to 'User'.
 *
 * @var string
 * @access public
 */
	var $userModel = 'User';
/**
 * Additional query conditions to use when looking up and authenticating users,
 * i.e. array('User.is_active' => 1).
 *
 * @var array
 * @access public
 */
	var $userScope = array();
/**
 * Allows you to specify non-default login name and password fields used in
 * $userModel, i.e. array('username' => 'login_name', 'password' => 'passwd').
 *
 * @var array
 * @access public
 */
	var $fields = array('username' => 'username', 'password' => 'password');
/**
 * the hash function to use, options: sha1, sha256, md5
 *
 * @var string
 * @access public
 */
	var $hash = 'sha1';
/**
 * The session key name where the record of the current user is stored.  If
 * unspecified, it will be "Auth.{$userModel name}".
 *
 * @var string
 * @access public
 */
	var $sessionKey = null;
/**
 * If using action-based access control, this defines how the paths to action
 * ACO nodes is computed.  If, for example, all controller nodes are nested
 * under an ACO node named 'Controllers', $actionPath should be set to
 * "Controllers/".
 *
 * @var string
 * @access public
 */
	var $actionPath = null;
/**
 * A URL (defined as a string or array) to the controller action that handles
 * logins.
 *
 * @var mixed
 * @access public
 */
	var $loginAction = null;
/**
 * Normally, if a user is redirected to the $loginAction page, the location they
 * were redirected from will be stored in the session so that they can be
 * redirected back after a successful login.  If this session value is not
 * set, the user will be redirected to the page specified in $loginRedirect.
 *
 * @var mixed
 * @access public
 */
	var $loginRedirect = null;
/**
 * The the default action to redirect to after the user is logged out.  While AuthComponent does
 * not handle post-logout redirection, a redirect URL will be returned from AuthComponent::logout().
 * Defaults to AuthComponent::$loginAction.
 *
 * @var mixed
 * @access public
 * @see AuthComponent::$loginAction
 * @see AuthComponent::logout()
 */
	var $logoutRedirect = null;
/**
 * The name of model or model object, or any other object has an isAuthorized method.
 *
 * @var string
 * @access public
 */
	var $object = null;

/**
 * Error to display when user login fails.  For security purposes, only one error is used for all
 * login failures, so as not to expose information on why the login failed.
 *
 * @var string
 * @access public
 */
	var $loginError = 'Login failed.  Invalid username or password.';
/**
 * Error to display when user attempts to access an object or action to which they do not have
 * acccess.
 *
 * @var string
 * @access public
 */
	var $authError = 'You are not authorized to access that location.';
/**
 * Determines whether AuthComponent will automatically redirect and exit if login is successful.
 *
 * @var boolean
 * @access public
 */
	var $autoRedirect = true;
/**
 * Controller actions for which user validation is not required.
 *
 * @var array
 * @access public
 * @see AuthComponent::allow()
 */
	var $allowedActions = array();
/**
 * Maps actions to CRUD operations.  Used for controller-based validation ($validate = 'controller').
 *
 * @var array
 * @access public
 * @see AuthComponent::mapActions()
 */
	var $actionMap = array(
		'index'		=> 'read',
		'add'		=> 'create',
		'edit'		=> 'update',
		'view'		=> 'read',
		'remove'	=> 'delete'
	);
/**
 * Form data from Controller::$data
 *
 * @var array
 * @access public
 */
	var $data = array();
/**
 * Parameter data from Controller::$params
 *
 * @var array
 * @access public
 */
	var $params = array();
/**
 * Initializes AuthComponent for use in the controller
 *
 * @access public
 * @param object $controller A reference to the instantiating controller object
 * @return void
 */
	function initialize(&$controller) {
		$this->params = $controller->params;
		$crud = array('create', 'read', 'update', 'delete');
		$this->actionMap = am($this->actionMap, array_combine($crud, $crud));

		if (defined('CAKE_ADMIN')) {
			$this->actionMap = am($this->actionMap, array(
				CAKE_ADMIN . '_index'	=> 'read',
				CAKE_ADMIN . '_add'		=> 'create',
				CAKE_ADMIN . '_edit'	=> 'update',
				CAKE_ADMIN . '_view'	=> 'read',
				CAKE_ADMIN . '_remove'	=> 'delete',
				CAKE_ADMIN . '_create'	=> 'create',
				CAKE_ADMIN . '_read'	=> 'read',
				CAKE_ADMIN . '_update'	=> 'update',
				CAKE_ADMIN . '_delete'	=> 'delete'
			));
		}
		if (Configure::read() > 0) {
			uses('debugger');
			Debugger::checkSessionKey();
		}
	}
/**
 * Main execution method.  Handles redirecting of invalid users, and processing
 * of login form data.
 *
 * @access public
 * @param object $controller A reference to the instantiating controller object
 * @return void
 */
	function startup(&$controller) {
		if (low($controller->name) == 'app' || (low($controller->name) == 'tests' && Configure::read() > 0)) {
			return;
		}

		if (!$this->__setDefaults()) {
			return false;
		}

		$this->data = $controller->data = $this->hashPasswords($controller->data);

		if ($this->allowedActions == array('*') || in_array($controller->action, $this->allowedActions)) {
			return false;
		}

		if (!isset($controller->params['url']['url'])) {
			$url = '';
		} else {
			$url = $controller->params['url']['url'];
		}

		if ($this->_normalizeURL($this->loginAction) == $this->_normalizeURL($url)) {
			if (empty($controller->data) || !isset($controller->data[$this->userModel])) {
				if (!$this->Session->check('Auth.redirect')) {
					$this->Session->write('Auth.redirect', $controller->referer());
				}
				return false;
			}

			$data = array(
				$this->userModel . '.' . $this->fields['username'] => '= ' . $controller->data[$this->userModel][$this->fields['username']],
				$this->userModel . '.' . $this->fields['password'] => '= ' . $controller->data[$this->userModel][$this->fields['password']]
			);

			if ($this->login($data)) {
				if ($this->autoRedirect) {
					$controller->redirect($this->redirect(), null, true);
				}
				return true;
			} else {
				$this->Session->setFlash($this->loginError, 'default', array(), 'Auth.login');
				unset($controller->data[$this->userModel][$this->fields['password']]);
			}
			return false;
		} else {
			if (!$this->user()) {
				if (!$this->RequestHandler->isAjax()) {
					$this->Session->write('Auth.redirect', $url);
					$controller->redirect($this->_normalizeURL($this->loginAction), null, true);
					return false;
				} elseif (!empty($this->ajaxLogin)) {
					$controller->viewPath = 'elements';
					$controller->render($this->ajaxLogin, 'ajax');
					exit();
				}
			}
		}

		if ($this->authorize) {
			extract($this->__authType());
			if ($type == 'controller') {
				if ($controller->isAuthorized()) {
					return true;
				}
			} else {
				switch ($type) {
					case 'crud':
					case 'actions':
						if (isset($controller->Acl)) {
							$this->Acl =& $controller->Acl;
						} else {
							trigger_error(__('Could not find AclComponent. Please include Acl in Controller::$components.', true), E_USER_WARNING);
						}
					break;
					case 'model':
						if (!isset($object)) {
							if (isset($controller->{$controller->modelClass}) && is_object($controller->{$controller->modelClass})) {
								$object = $controller->modelClass;
							} elseif (!empty($controller->uses) && isset($controller->{$controller->uses[0]}) && is_object($controller->{$controller->uses[0]})) {
								$object = $controller->uses[0];
							}
						}
						$type = array('model' => $object);
					break;
					default:
					break;
				}
				if ($this->isAuthorized($type)) {
					return true;
				}
			}
			$this->Session->setFlash($this->authError);
			$controller->redirect($controller->referer(), null, true);
			return false;
		} else {
			return true;
		}
	}
/**
 * Attempts to introspect the correct values for object properties including
 * $userModel and $sessionKey.
 *
 * @access private
 * @param object $controller A reference to the instantiating controller object
 * @return void
 */
	function __setDefaults() {
		if (empty($this->userModel)) {
			trigger_error(__('Could not find $userModel.  Please set AuthComponent::$userModel in beforeFilter().', true), E_USER_WARNING);
			return false;
		}
		if (empty($this->loginAction)) {
			$this->loginAction = Router::url(array('controller'=> Inflector::underscore(Inflector::pluralize($this->userModel)), 'action'=>'login'));
		}
		if (empty($this->sessionKey)) {
			$this->sessionKey = 'Auth.' . $this->userModel;
		}
		if (empty($this->logoutAction)) {
			$this->logoutRedirect = $this->loginAction;
		}
		return true;
	}
/**
 * Determines whether the given user is authorized to perform an action.  The type of authorization
 * used is based on the value of AuthComponent::$authorize or the passed $type param.
 *
 * Types:
 * 'controller' will validate Controller::action against Controller::isAuthorized(user, controller, action)
 * 'actions' will validate Controller::action against an AclComponent::check()
 * 'crud' will validate mapActions against an AclComponent::check()
 * array('model'=> 'name'); will validate mapActions against model $name::isAuthorize(user, controller, mapAction)
 * 'object' will validate Controller::action against object::isAuthorized(user, controller, action)
 *
 * @access public
 * @param string $type
 * @param mixed $object object, model object, or model name
 * @param mixed $user  The user to check the authorization of
 * @return boolean True if $user is authorized, otherwise false
 */
	function isAuthorized($type = null, $object = null, $user = null) {
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
			case 'actions':
				$valid = $this->Acl->check($user, $this->action());
			break;
			case 'crud':
				$this->mapActions();
				if (!isset($this->actionMap[$this->params['action']])) {
					trigger_error(__(sprintf('Auth::startup() - Attempted access of un-mapped action "%s" in controller "%s"', $this->params['action'], $this->params['controller']), true), E_USER_WARNING);
				} else {
					$valid = $this->Acl->check($user, $this->action(':controller'), $this->actionMap[$this->params['action']]);
				}
			break;
			case 'model':
				$this->mapActions();
				$action = $this->actionMap[$this->params['action']];
				if (is_string($object)) {
					$object = $this->getModel($object);
				}
			case 'object':
				if (!isset($action)) {
					$action = $this->action(':action');
				}
				if (empty($object)) {
					trigger_error(__(sprintf('Could not find %s. Set AuthComponent::$object in beforeFilter() or pass a valid object', get_class($object)), true), E_USER_WARNING);
					return;
				}
				if (method_exists($object, 'isAuthorized')) {
					$valid = $object->isAuthorized($user, $this->action(':controller'), $action);
				} elseif ($object){
					trigger_error(__(sprintf('%s::isAuthorized() is not defined.', get_class($object)), true), E_USER_WARNING);
				}
			break;
			case null:
			case false:
				return true;
			break;
			default:
				trigger_error(__('Auth::isAuthorized() - $authorize is set to an incorrect value.  Allowed settings are: "actions", "crud", "model" or null.', true), E_USER_WARNING);
			break;
		}
		return $valid;
	}
/**
 *
 * @access private
 * @param string $auth
 * @return type, object, asssoc
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
		}
		return compact('type', 'object');
	}
/**
 * Takes a list of actions in the current controller for which authentication is not required, or
 * no parameters to allow all actions.
 *
 * @access public
 * @param string $action Controller action name
 * @param string $action Controller action name
 * @param string ... etc.
 * @return void
 */
	function allow() {
		$args = func_get_args();
		if (empty($args)) {
			$this->allowedActions = array('*');
		} else {
			$this->allowedActions = am($this->allowedActions, $args);
		}
	}
/**
 * Removes items from the list of allowed actions.
 *
 * @access public
 * @param string $action Controller action name
 * @param string $action Controller action name
 * @param string ... etc.
 * @return void
 * @see AuthComponent::allow()
 */
	function deny() {
		$args = func_get_args();
		foreach ($args as $arg) {
			$i = array_search($arg, $this->allowedActions);
			if (is_int($i)) {
				unset($this->allowedActions[$i]);
			}
		}
		$this->allowedActions = array_values($this->allowedActions);
	}
/**
 * Maps action names to CRUD operations.  Used for controller-based authentication.
 *
 * @param array $map
 * @access public
 * @return void
 */
	function mapActions($map = array()) {
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
 * @access public
 * @param mixed $data User object
 * @return boolean True on login success, false on failure
 */
	function login($data = null) {
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
 * @access public
 * @param mixed $url Optional URL to redirect the user to after logout
 * @return string AuthComponent::$loginAction
 * @see AuthComponent::$loginAction
 */
	function logout() {
		$this->__setDefaults();
		$this->Session->del($this->sessionKey);
		$this->Session->del('Auth.redirect');
		$this->_loggedIn = false;
		return $this->_normalizeURL($this->logoutRedirect);
	}
/**
 * Get the current user from the session.
 *
 * @access public
 * @return array User record, or null if no user is logged in.
 */
	function user($key = null) {
		$this->__setDefaults();
		if (!$this->Session->check($this->sessionKey)) {
			return null;
		}

		if ($key == null) {
			return array($this->userModel => $this->Session->read($this->sessionKey));
		} else {
			$user = $this->Session->read($this->sessionKey);
			if (isset($user[$key])) {
				return $user[$key];
			} else {
				return null;
			}
		}
	}
/**
 * If no parameter is passed, gets the authentication redirect URL.
 *
 * @param mixed $url Optional URL to write as the login redirect URL.
 * @access public
 * @return string Redirect URL
 */
	function redirect($url = null) {
		if (!is_null($url)) {
			return $this->Session->write('Auth.redirect', $url);
		}
		if ($this->Session->check('Auth.redirect')) {
			$redir = $this->Session->read('Auth.redirect');
			$this->Session->delete('Auth.redirect');

			if ($this->_normalizeURL($redir) == $this->_normalizeURL($this->loginAction)) {
				$redir = $this->loginRedirect;
			}
		} else {
			$redir = $this->loginRedirect;
		}
		return $this->_normalizeURL($redir);
	}
/**
 * Validates a user against an abstract object.
 *
 * @access public
 * @param mixed $object  The object to validate the user against.
 * @param mixed $user    Optional.  The identity of the user to be validated.
 *                       Uses the current user session if none specified.  For
 *                       valid forms of identifying users, see
 *                       AuthComponent::identify().
 * @see AuthComponent::identify()
 * @return boolean True if the user validates, false otherwise.
 */
	function validate($object, $user = null, $action = null) {
		if (empty($user)) {
			$this->getModel();
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
 * @access public
 * @param string $action  Optional.  The controller/action path to validate the
 *                        user against.  The current request action is used if
 *                        none is specified.
 * @return boolean ACO node path
 */
	function action($action = ':controller/:action') {
		return r(
			array(':controller', ':action'),
			array(Inflector::camelize($this->params['controller']), $this->params['action']),
			$this->actionPath . $action
		);
	}
/**
 * Returns a reference to the model object specified by $userModel, and attempts
 * to load it if it is not found.
 *
 * @access public
 * @return object A reference to a model object.
 */
	function &getModel($name = null) {
		$model = null;
		if (!$name) {
			$name = $this->userModel;
		}
		if (!ClassRegistry::isKeySet($name)) {
			if (!loadModel(Inflector::underscore($name))) {
				trigger_error(__(sprintf('Auth::getModel() - %s is not set or could not be found', $name), true), E_USER_WARNING);
				return $model;
			} else {
				$model = new $name();
			}
		}

		if (empty($model)) {
			if (PHP5) {
				$model = ClassRegistry::getObject($name);
			} else {
				$model =& ClassRegistry::getObject($name);
			}
		}

		if (empty($model)) {
			trigger_error(__(sprintf('Auth::getModel() - %s is not set or could not be found', $name), true) . $name, E_USER_WARNING);
			return null;
		}

		return $model;
	}
/**
 * Identifies a user based on specific criteria.
 *
 * @access public
 * @param mixed  $user    Optional.  The identity of the user to be validated.
 *                        Uses the current user session if none specified.
 * @return array User record data, or null, if the user could not be identified.
 */
	function identify($user = null) {
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
			$user = $user[$this->userModel];
		} elseif (is_array($user) && isset($user[$this->userModel])) {
			$user = $user[$this->userModel];
		}

		if (is_array($user) && (isset($user[$this->fields['username']]) || isset($user[$this->userModel . '.' . $this->fields['username']]))) {
			$find = array();
			if (isset($user[$this->fields['username']]) && !empty($user[$this->fields['username']])  && !empty($user[$this->fields['password']])) {
				if (trim($user[$this->fields['username']]) == '=' || trim($user[$this->fields['password']]) == '=') {
					return false;
				}
				$find = array(
					$this->fields['username'] => $user[$this->fields['username']],
					$this->fields['password'] => $user[$this->fields['password']]
				);
			} elseif (isset($user[$this->userModel . '.' . $this->fields['username']]) && !empty($user[$this->userModel . '.' . $this->fields['username']])) {
				if (trim($user[$this->userModel . '.' . $this->fields['username']]) == '=' || trim($user[$this->userModel . '.' . $this->fields['password']]) == '=') {
					return false;
				}
				$find = array(
					$this->fields['username'] => $user[$this->userModel . '.' . $this->fields['username']],
					$this->fields['password'] => $user[$this->userModel . '.' . $this->fields['password']]
				);
			}
			$model =& $this->getModel();

			$data = $model->find(am($find, $this->userScope), null, null, -1);

			if (empty($data) || empty($data[$this->userModel])) {
				return null;
			}
		} elseif (is_numeric($user)) {
			// Assume it's a user's ID
			$model =& $this->getModel();
			$data = $model->find(am(array($model->escapeField() => $user), $this->userScope));

			if (empty($data) || empty($data[$this->userModel])) {
				return null;
			}
		}
		if (isset($data) && !empty($data)) {
			if (!empty($data[$this->userModel][$this->fields['password']])) {
				unset($data[$this->userModel][$this->fields['password']]);
			}
			return $data[$this->userModel];
		} else {
			return null;
		}
	}
/**
 * Hash any passwords found in $data using $userModel and $fields['password']
 *
 * @access public
 * @param array $data
 * @param array $hash sha1, sha256, md5
 * @return array
 */
	function hashPasswords($data) {
		if (isset($data[$this->userModel])) {
			if (!empty($data[$this->userModel][$this->fields['username']]) && !empty($data[$this->userModel][$this->fields['password']])) {
				$data[$this->userModel][$this->fields['password']] = $this->password($data[$this->userModel][$this->fields['password']]);
			}
		}
		return $data;
	}
/**
 * Hash a password with the application's salt value (as defined in CAKE_SESSION_STRING)
 *
 * @access public
 * @param string $password
 * @param array $hash sha1, sha256, md5
 * @return string
 */
	function password($password) {
		return Security::hash(CAKE_SESSION_STRING . $password, $this->hash);
	}
/**
 * Component shutdown.  If user is logged in, wipe out redirect.
 *
 * @access public
 * @param object $controller
 * @return void
 */
	function shutdown(&$controller) {
		if ($this->_loggedIn) {
			$this->Session->del('Auth.redirect');
		}
	}
/**
 * @access protected
 */
	function _normalizeURL($url = '/') {
		if (is_array($url)) {
			$url = Router::url($url);
		}

		$paths = Router::getPaths();
		if (!empty($paths['base']) && stristr($url, $paths['base'])) {
			$url = r($paths['base'], '', $url);
		}

		$url = '/' . $url . '/';

		while (strpos($url, '//') !== false) {
			$url = r('//', '/', $url);
		}
		return $url;
	}
}
?>