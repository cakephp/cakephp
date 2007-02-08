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
 * Other components utilized by AuthComponent
 *
 * @var array
 * @access public
 */
	var $components = array('Session', 'Acl', 'RequestHandler');
/**
 * The name of an optional view element to render when an Ajax request is made
 * with an invalid or expired session
 *
 * @var string
 * @access public
 */
	var $ajaxLogin = null;
/**
 * The name of the model that represents users which will be authenticated.  If
 * this value is unspecified, AuthComponent will look for one of the following:
 * 'User', 'Person', 'Contact', 'Member', 'Customer', 'Account', 'Client',
 * 'Employee', 'Staff', or 'Friend', in that order.
 *
 * @var string
 * @access public
 */
	var $userModel = null;
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
 * "Controllers/:controller/:action".
 *
 * @var string
 * @access public
 */
	var $actionPath = ':controller/:action';
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
 * @var string
 * @access public
 */
	var $loginRedirect = null;
/**
 * Holds a reference to the model object specified by $userModel.
 *
 * @var object
 * @access private
 */
	var $_model = null;
/**
 * The type of automatic ACL validation to perform, where 'actions' validates
 * the controller action of the current request, 'objects' validates against
 * model objects accessed, and null prevents automatic validation.
 *
 * @var string
 * @access public
 */
	var $validate = 'actions';
/**
 * Error to display when user login fails.  For security purposes, only one error is used for all
 * login failures, so as not to expose information on why the login failed.
 *
 * @var string
 * @access public
 */
	var $loginError = 'Login failed.  Invalid username or password.';
/**
 * Maintains current user login state.
 *
 * @var boolean
 * @access private
 */
	var $_loggedIn = false;
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
 * Main execution method.  Handles redirecting of invalid users, and processing
 * of login form data.
 *
 * @access public
 * @param object $controller A reference to the instantiating controller object
 * @return void
 */
	function startup(&$controller) {
		if (low($controller->name) == 'app' || (low($controller->name) == 'tests' && DEBUG > 0)) {
			return;
		}
		if (!$this->_setDefaults($controller)) {
			return;
		}

		// Hash incoming passwords
		if (isset($controller->data[$this->userModel])) {
			if (isset($controller->data[$this->userModel][$this->fields['username']]) && isset($controller->data[$this->userModel][$this->fields['password']])) {
				$model =& $this->getUserModel();
				$controller->data[$this->userModel][$this->fields['password']] = Security::hash($controller->data[$this->userModel][$this->fields['password']]);
			}
		}
		$this->data = $controller->data;

		if ($this->allowedActions == array('*') || in_array($controller->action, $this->allowedActions)) {
			return false;
		}
		if (!isset($controller->params['url']['url'])) {
			$url = '';
		} else {
			$url = $controller->params['url']['url'];
		}

		if ($this->_normalizeURL($this->loginAction) == $this->_normalizeURL($url)) {
			// We're already at the login action
			if (empty($controller->data) || !isset($controller->data[$this->userModel])) {
				return;
			}
			$data = array(
				$this->userModel . '.' . $this->fields['username'] => '= ' . $controller->data[$this->userModel][$this->fields['username']],
				$this->userModel . '.' . $this->fields['password'] => '= ' . $controller->data[$this->userModel][$this->fields['password']]
			);
			if ($this->login($data) && $this->autoRedirect) {
				$controller->redirect($this->redirect(), null, true);
			} else {
				$this->Session->setFlash($this->loginError, 'default', array(), 'Auth.login');
				unset($controller->data[$this->userModel][$this->fields['password']]);
			}
			return;
		} else {
			if (!$this->Session->check($this->sessionKey)) {
				if (!$this->RequestHandler->isAjax()) {
					$this->Session->write('Auth.redirect', $url);
					$controller->redirect('/' . $this->loginAction, null, true);
				} elseif ($this->ajaxLogin != null) {
					$this->viewPath = 'elements';
					$this->render($this->ajaxLogin, 'ajax');
					exit();
				}
			}
		}

		switch ($this->validate) {
			case 'actions':
				
			break;
			case 'objects':
				
			break;
			case null:
			case false:
				return;
			break;
			default:
				trigger_error(__('Auth::startup() - $type is set to an incorrect value.  Should be "actions", "objects", or null.'), E_USER_WARNING);
			break;
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
	function _setDefaults(&$controller) {
		if (empty($this->userModel)) {
			$classes = array_values(array_intersect(
				array_map('strtolower', get_declared_classes()),
				array('user', 'person', 'contact', 'member', 'customer', 'account', 'client', 'employee', 'staff', 'friend')
			));

			if (!empty($classes)) {
				$this->userModel = ucwords($classes[0]);
			}
		}

		if (empty($this->userModel)) {
			trigger_error(__('Could not find $userModel.  Please set AuthComponent::$userModel in beforeFilter().'), E_USER_WARNING);
			return false;
		}

		if (empty($this->loginAction)) {
			$this->loginAction = Inflector::underscore(Inflector::pluralize($this->userModel)) . '/login';
		}

		if (empty($this->sessionKey)) {
			$this->sessionKey = 'Auth.' . $this->userModel;
		}
		return true;
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
			$this->allowedActions = $args;
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
		$this->Session->del($this->sessionKey);
		$this->Session->del('Auth.redirect');
		$this->_loggedIn = false;
		return $this->_normalizeURL($this->loginAction);
	}
/**
 * Get the current user from the session.
 *
 * @access public
 * @return array User record, or null if no user is logged in.
 */
	function user($key = null) {
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
 * Gets the authentication redirect URL
 *
 * @access public
 * @return string Redirect URL
 */
	function redirect() {
		if ($this->Session->check('Auth.redirect')) {
			$redir = $this->Session->read('Auth.redirect');
			$this->Session->delete('Auth.redirect');
		} else {
			$redir = $this->loginRedirect;
		}
		return $this->_normalizeURL('/' . $redir);
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
	function validate($object, $user = null) {
		$user = $this->identify($user);
		if (empty($user)) {
			return false;
		}
		pr($user);
	}
/**
 * Validates a user against a controller action.
 *
 * @access public
 * @param string $action  Optional.  The controller/action path to validate the
 *                        user against.  The current request action is used if
 *                        none is specified.
 * @param mixed  $user    Optional.  The identity of the user to be validated.
 *                        Uses the current user session if none specified.  For
 *                        valid forms of identifying users, see
 *                        AuthComponent::identify().
 * @see AuthComponent::validate()
 * @return boolean True if the user validates, false otherwise.
 */
	function validateAction($action = null, $user = null) {
		$path = r(
			array(':controller', ':action'),
			array($this->params['controller'], $this->params['action']),
			$path
		);
		return $this->validate($path, $user);
	}
/**
 * Returns a reference to the model object specified by $userModel, and attempts
 * to load it if it is not found.
 *
 * @access public
 * @return object A reference to a model object.
 */
	function &getUserModel() {
		if (!ClassRegistry::isKeySet($this->userModel)) {
			if (!loadModel($this->userModel)) {
				trigger_error(__('Auth::getUserModel() - $userModel is not set or could not be found') . $this->userModel, E_USER_WARNING);
				return null;
			}
		}
		if (PHP5) {
			$user = ClassRegistry::getObject($this->userModel);
		} else {
			$user =& ClassRegistry::getObject($this->userModel);
		}
		if (empty($user)) {
			trigger_error(__('Auth::getUserModel() - $userModel is not set or could not be found ') . $this->userModel, E_USER_WARNING);
			return null;
		}
		return $user;
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
		} else if (is_object($user) && is_a($user, 'Model')) {
			if (!$user->exists()) {
				return null;
			}
			$user = $user->read();
			$user = $user[$this->userModel];
		} else if (is_array($user) && isset($user[$this->userModel])) {
			$user = $user[$this->userModel];
		}

		if (is_array($user) && (isset($user[$this->fields['username']]) || isset($user[$this->userModel . '.' . $this->fields['username']]))) {
			if (isset($user[$this->fields['username']])) {
				$find = array(
					$this->fields['username'] => $user[$this->fields['username']],
					$this->fields['password'] => $user[$this->fields['password']]
				);
			} else {
				$find = array(
					$this->fields['username'] => $user[$this->userModel . '.' . $this->fields['username']],
					$this->fields['password'] => $user[$this->userModel . '.' . $this->fields['password']]
				);
			}

			$model =& $this->getUserModel();
			$data = $model->find(am($find, $this->userScope), null, null, -1);

			if (empty($data) || empty($data[$this->userModel])) {
				return null;
			}
			return $data[$this->userModel];
		} else if (is_numeric($user)) {
			// Assume it's a user's ID
			$model =& $this->getUserModel();
			$data = $model->find(array($this->userModel . $model->primaryKey => $user));

			if (empty($data) || empty($data[$this->userModel])) {
				return null;
			}
			return $data[$this->userModel];
		} else {
			return null;
		}
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
 * @access private
 */
	function _normalizeURL($url = '/') {
		if (is_array($url)) {
			$url = Router::url($url);
			$paths = Router::getPaths();
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