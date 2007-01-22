<?php
/* SVN FILE: $Id$ */

/**
 * Authentication component
 *
 * Manages user logins and permissions.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP v 0.10.0.1076
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

		if ($this->allowedActions == array('*') || in_array($controller->action, $this->allowedActions)) {
			return;
		}

		$this->_setDefaults($controller);

		if (empty($this->userModel)) {
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
				$this->userModel . '.' . $this->fields['username'] => $controller->data[$this->userModel][$this->fields['username']],
				$this->userModel . '.' . $this->fields['password'] => Security::hash($controller->data[$this->userModel][$this->fields['password']])
			);

			if ($user = $this->identify($data)) {
				$this->Session->write($this->sessionKey, $user);
				if ($this->Session->check('Auth.redirect')) {
					$redir = $this->Session->read('Auth.redirect');
					$this->Session->delete('Auth.redirect');
				} else {
					$redir = $this->loginRedirect;
				}
				$controller->redirect('/' . $redir, null, true);
			} else {
				$this->Session->setFlash(__($this->loginError), 'default', array(), 'Auth.login');
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
	
			} else {
	
				$this->UserData = $this->Session->read('Contact');
				$this->set('UserData', $this->UserData);
			}
		}

		switch ($this->validate) {
			case 'actions':
				
			break;
			case 'objects':
				
			break;
			case null:  break;
			case false: break;
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

		if (empty($this->loginAction)) {
			$this->loginAction = Inflector::underscore(Inflector::pluralize($this->userModel)) . '/login';
		}

		if (empty($this->sessionKey) && !empty($this->userModel)) {
			$this->sessionKey = 'Auth.' . $this->userModel;
		}
	}
/**
 * Takes a list of actions in the current controller for which validation is not required, or
 * no parameters to allow all actions.
 *
 * @access public
 * @param string $action
 * @param string $action
 * @param string ...
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
		if ($user == null) {
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
		if ($user == null) {
			$model =& $this->getUserModel();
		} else if (is_object($user) && is_a($user, 'model')) {
			
		} else if (is_array($user) && isset($user[$this->userModel])) {
			$user = $user[$this->userModel];
		} else if (is_array($user) && (isset($user[$this->fields['username']]) || isset($user[$this->userModel . '.' . $this->fields['username']]))) {
			$model =& $this->getUserModel();
			$data = $model->find($user, null, null, -1);

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
 * Allows setting of multiple properties of AuthComponent in a single line of code.
 *
 * @access public
 * @param array $properties An associative array containing AuthComponent
 *                          properties and corresponding values.
 * @return void
 */
	function set($properties = array()) {
		if (is_array($properties) && !empty($properties)) {
			$vars = get_object_vars($this);
			foreach ($properties as $key => $val) {
				if (array_key_exists($key, $vars)) {
					$this->{$key} = $val;
				}
			}
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
		return r('//', '/', '/' . $url . '/');
	}
}

?>