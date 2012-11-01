<?php
/**
 * Security Component
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
 * @since         CakePHP(tm) v 0.10.8.2156
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Component', 'Controller');
App::uses('String', 'Utility');
App::uses('Hash', 'Utility');
App::uses('Security', 'Utility');

/**
 * The Security Component creates an easy way to integrate tighter security in
 * your application. It provides methods for various tasks like:
 *
 * - Restricting which HTTP methods your application accepts.
 * - CSRF protection.
 * - Form tampering protection
 * - Requiring that SSL be used.
 * - Limiting cross controller communication.
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/security-component.html
 */
class SecurityComponent extends Component {

/**
 * The controller method that will be called if this request is black-hole'd
 *
 * @var string
 */
	public $blackHoleCallback = null;

/**
 * List of controller actions for which a POST request is required
 *
 * @var array
 * @see SecurityComponent::requirePost()
 */
	public $requirePost = array();

/**
 * List of controller actions for which a GET request is required
 *
 * @var array
 * @see SecurityComponent::requireGet()
 */
	public $requireGet = array();

/**
 * List of controller actions for which a PUT request is required
 *
 * @var array
 * @see SecurityComponent::requirePut()
 */
	public $requirePut = array();

/**
 * List of controller actions for which a DELETE request is required
 *
 * @var array
 * @see SecurityComponent::requireDelete()
 */
	public $requireDelete = array();

/**
 * List of actions that require an SSL-secured connection
 *
 * @var array
 * @see SecurityComponent::requireSecure()
 */
	public $requireSecure = array();

/**
 * List of actions that require a valid authentication key
 *
 * @var array
 * @see SecurityComponent::requireAuth()
 */
	public $requireAuth = array();

/**
 * Controllers from which actions of the current controller are allowed to receive
 * requests.
 *
 * @var array
 * @see SecurityComponent::requireAuth()
 */
	public $allowedControllers = array();

/**
 * Actions from which actions of the current controller are allowed to receive
 * requests.
 *
 * @var array
 * @see SecurityComponent::requireAuth()
 */
	public $allowedActions = array();

/**
 * Deprecated property, superseded by unlockedFields.
 *
 * @var array
 * @deprecated
 * @see SecurityComponent::$unlockedFields
 */
	public $disabledFields = array();

/**
 * Form fields to exclude from POST validation. Fields can be unlocked
 * either in the Component, or with FormHelper::unlockField().
 * Fields that have been unlocked are not required to be part of the POST
 * and hidden unlocked fields do not have their values checked.
 *
 * @var array
 */
	public $unlockedFields = array();

/**
 * Whether to validate POST data.  Set to false to disable for data coming from 3rd party
 * services, etc.
 *
 * @var boolean
 */
	public $validatePost = true;

/**
 * Whether to use CSRF protected forms.  Set to false to disable CSRF protection on forms.
 *
 * @var boolean
 * @see http://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)
 * @see SecurityComponent::$csrfExpires
 */
	public $csrfCheck = true;

/**
 * The duration from when a CSRF token is created that it will expire on.
 * Each form/page request will generate a new token that can only be submitted once unless
 * it expires.  Can be any value compatible with strtotime()
 *
 * @var string
 */
	public $csrfExpires = '+30 minutes';

/**
 * Controls whether or not CSRF tokens are use and burn.  Set to false to not generate
 * new tokens on each request.  One token will be reused until it expires. This reduces
 * the chances of users getting invalid requests because of token consumption.
 * It has the side effect of making CSRF less secure, as tokens are reusable.
 *
 * @var boolean
 */
	public $csrfUseOnce = true;

/**
 * Control the number of tokens a user can keep open.
 * This is most useful with one-time use tokens.  Since new tokens
 * are created on each request, having a hard limit on the number of open tokens
 * can be useful in controlling the size of the session file.
 *
 * When tokens are evicted, the oldest ones will be removed, as they are the most likely
 * to be dead/expired.
 *
 * @var integer
 */
	public $csrfLimit = 100;

/**
 * Other components used by the Security component
 *
 * @var array
 */
	public $components = array('Session');

/**
 * Holds the current action of the controller
 *
 * @var string
 */
	protected $_action = null;

/**
 * Request object
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Component startup. All security checking happens here.
 *
 * @param Controller $controller Instantiating controller
 * @return void
 */
	public function startup(Controller $controller) {
		$this->request = $controller->request;
		$this->_action = $this->request->params['action'];
		$this->_methodsRequired($controller);
		$this->_secureRequired($controller);
		$this->_authRequired($controller);

		$isPost = ($this->request->is('post') || $this->request->is('put'));
		$isNotRequestAction = (
			!isset($controller->request->params['requested']) ||
			$controller->request->params['requested'] != 1
		);

		if ($isPost && $isNotRequestAction && $this->validatePost) {
			if ($this->_validatePost($controller) === false) {
				return $this->blackHole($controller, 'auth');
			}
		}
		if ($isPost && $isNotRequestAction && $this->csrfCheck) {
			if ($this->_validateCsrf($controller) === false) {
				return $this->blackHole($controller, 'csrf');
			}
		}
		$this->generateToken($controller->request);
		if ($isPost && is_array($controller->request->data)) {
			unset($controller->request->data['_Token']);
		}
	}

/**
 * Sets the actions that require a POST request, or empty for all actions
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#SecurityComponent::requirePost
 */
	public function requirePost() {
		$args = func_get_args();
		$this->_requireMethod('Post', $args);
	}

/**
 * Sets the actions that require a GET request, or empty for all actions
 *
 * @return void
 */
	public function requireGet() {
		$args = func_get_args();
		$this->_requireMethod('Get', $args);
	}

/**
 * Sets the actions that require a PUT request, or empty for all actions
 *
 * @return void
 */
	public function requirePut() {
		$args = func_get_args();
		$this->_requireMethod('Put', $args);
	}

/**
 * Sets the actions that require a DELETE request, or empty for all actions
 *
 * @return void
 */
	public function requireDelete() {
		$args = func_get_args();
		$this->_requireMethod('Delete', $args);
	}

/**
 * Sets the actions that require a request that is SSL-secured, or empty for all actions
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#SecurityComponent::requireSecure
 */
	public function requireSecure() {
		$args = func_get_args();
		$this->_requireMethod('Secure', $args);
	}

/**
 * Sets the actions that require an authenticated request, or empty for all actions
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#SecurityComponent::requireAuth
 */
	public function requireAuth() {
		$args = func_get_args();
		$this->_requireMethod('Auth', $args);
	}

/**
 * Black-hole an invalid request with a 400 error or custom callback. If SecurityComponent::$blackHoleCallback
 * is specified, it will use this callback by executing the method indicated in $error
 *
 * @param Controller $controller Instantiating controller
 * @param string $error Error method
 * @return mixed If specified, controller blackHoleCallback's response, or no return otherwise
 * @see SecurityComponent::$blackHoleCallback
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/security-component.html#handling-blackhole-callbacks
 * @throws BadRequestException
 */
	public function blackHole(Controller $controller, $error = '') {
		if ($this->blackHoleCallback == null) {
			throw new BadRequestException(__d('cake_dev', 'The request has been black-holed'));
		} else {
			return $this->_callback($controller, $this->blackHoleCallback, array($error));
		}
	}

/**
 * Sets the actions that require a $method HTTP request, or empty for all actions
 *
 * @param string $method The HTTP method to assign controller actions to
 * @param array $actions Controller actions to set the required HTTP method to.
 * @return void
 */
	protected function _requireMethod($method, $actions = array()) {
		if (isset($actions[0]) && is_array($actions[0])) {
			$actions = $actions[0];
		}
		$this->{'require' . $method} = (empty($actions)) ? array('*'): $actions;
	}

/**
 * Check if HTTP methods are required
 *
 * @param Controller $controller Instantiating controller
 * @return boolean true if $method is required
 */
	protected function _methodsRequired(Controller $controller) {
		foreach (array('Post', 'Get', 'Put', 'Delete') as $method) {
			$property = 'require' . $method;
			if (is_array($this->$property) && !empty($this->$property)) {
				$require = $this->$property;
				if (in_array($this->_action, $require) || $this->$property == array('*')) {
					if (!$this->request->is($method)) {
						if (!$this->blackHole($controller, $method)) {
							return null;
						}
					}
				}
			}
		}
		return true;
	}

/**
 * Check if access requires secure connection
 *
 * @param Controller $controller Instantiating controller
 * @return boolean true if secure connection required
 */
	protected function _secureRequired(Controller $controller) {
		if (is_array($this->requireSecure) && !empty($this->requireSecure)) {
			$requireSecure = $this->requireSecure;

			if (in_array($this->_action, $requireSecure) || $this->requireSecure == array('*')) {
				if (!$this->request->is('ssl')) {
					if (!$this->blackHole($controller, 'secure')) {
						return null;
					}
				}
			}
		}
		return true;
	}

/**
 * Check if authentication is required
 *
 * @param Controller $controller Instantiating controller
 * @return boolean true if authentication required
 */
	protected function _authRequired(Controller $controller) {
		if (is_array($this->requireAuth) && !empty($this->requireAuth) && !empty($this->request->data)) {
			$requireAuth = $this->requireAuth;

			if (in_array($this->request->params['action'], $requireAuth) || $this->requireAuth == array('*')) {
				if (!isset($controller->request->data['_Token'] )) {
					if (!$this->blackHole($controller, 'auth')) {
						return null;
					}
				}

				if ($this->Session->check('_Token')) {
					$tData = $this->Session->read('_Token');

					if (
						!empty($tData['allowedControllers']) &&
						!in_array($this->request->params['controller'], $tData['allowedControllers']) ||
						!empty($tData['allowedActions']) &&
						!in_array($this->request->params['action'], $tData['allowedActions'])
					) {
						if (!$this->blackHole($controller, 'auth')) {
							return null;
						}
					}
				} else {
					if (!$this->blackHole($controller, 'auth')) {
						return null;
					}
				}
			}
		}
		return true;
	}

/**
 * Validate submitted form
 *
 * @param Controller $controller Instantiating controller
 * @return boolean true if submitted form is valid
 */
	protected function _validatePost(Controller $controller) {
		if (empty($controller->request->data)) {
			return true;
		}
		$data = $controller->request->data;

		if (!isset($data['_Token']) || !isset($data['_Token']['fields']) || !isset($data['_Token']['unlocked'])) {
			return false;
		}

		$locked = '';
		$check = $controller->request->data;
		$token = urldecode($check['_Token']['fields']);
		$unlocked = urldecode($check['_Token']['unlocked']);

		if (strpos($token, ':')) {
			list($token, $locked) = explode(':', $token, 2);
		}
		unset($check['_Token']);

		$locked = explode('|', $locked);
		$unlocked = explode('|', $unlocked);

		$lockedFields = array();
		$fields = Hash::flatten($check);
		$fieldList = array_keys($fields);
		$multi = array();

		foreach ($fieldList as $i => $key) {
			if (preg_match('/(\.\d+)+$/', $key)) {
				$multi[$i] = preg_replace('/(\.\d+)+$/', '', $key);
				unset($fieldList[$i]);
			}
		}
		if (!empty($multi)) {
			$fieldList += array_unique($multi);
		}

		$unlockedFields = array_unique(
			array_merge((array)$this->disabledFields, (array)$this->unlockedFields, $unlocked)
		);

		foreach ($fieldList as $i => $key) {
			$isLocked = (is_array($locked) && in_array($key, $locked));

			if (!empty($unlockedFields)) {
				foreach ($unlockedFields as $off) {
					$off = explode('.', $off);
					$field = array_values(array_intersect(explode('.', $key), $off));
					$isUnlocked = ($field === $off);
					if ($isUnlocked) {
						break;
					}
				}
			}

			if ($isUnlocked || $isLocked) {
				unset($fieldList[$i]);
				if ($isLocked) {
					$lockedFields[$key] = $fields[$key];
				}
			}
		}
		sort($unlocked, SORT_STRING);
		sort($fieldList, SORT_STRING);
		ksort($lockedFields, SORT_STRING);

		$fieldList += $lockedFields;
		$unlocked = implode('|', $unlocked);
		$check = Security::hash(serialize($fieldList) . $unlocked . Configure::read('Security.salt'));
		return ($token === $check);
	}

/**
 * Manually add CSRF token information into the provided request object.
 *
 * @param CakeRequest $request The request object to add into.
 * @return boolean
 */
	public function generateToken(CakeRequest $request) {
		if (isset($request->params['requested']) && $request->params['requested'] === 1) {
			if ($this->Session->check('_Token')) {
				$request->params['_Token'] = $this->Session->read('_Token');
			}
			return false;
		}
		$authKey = Security::generateAuthKey();
		$token = array(
			'key' => $authKey,
			'allowedControllers' => $this->allowedControllers,
			'allowedActions' => $this->allowedActions,
			'unlockedFields' => array_merge($this->disabledFields, $this->unlockedFields),
			'csrfTokens' => array()
		);

		$tokenData = array();
		if ($this->Session->check('_Token')) {
			$tokenData = $this->Session->read('_Token');
			if (!empty($tokenData['csrfTokens']) && is_array($tokenData['csrfTokens'])) {
				$token['csrfTokens'] = $this->_expireTokens($tokenData['csrfTokens']);
			}
		}
		if ($this->csrfUseOnce || empty($token['csrfTokens'])) {
			$token['csrfTokens'][$authKey] = strtotime($this->csrfExpires);
		}
		if (!$this->csrfUseOnce) {
			$csrfTokens = array_keys($token['csrfTokens']);
			$token['key'] = $csrfTokens[0];
		}
		$this->Session->write('_Token', $token);
		$request->params['_Token'] = array(
			'key' => $token['key'],
			'unlockedFields' => $token['unlockedFields']
		);
		return true;
	}

/**
 * Validate that the controller has a CSRF token in the POST data
 * and that the token is legit/not expired.  If the token is valid
 * it will be removed from the list of valid tokens.
 *
 * @param Controller $controller A controller to check
 * @return boolean Valid csrf token.
 */
	protected function _validateCsrf(Controller $controller) {
		$token = $this->Session->read('_Token');
		$requestToken = $controller->request->data('_Token.key');
		if (isset($token['csrfTokens'][$requestToken]) && $token['csrfTokens'][$requestToken] >= time()) {
			if ($this->csrfUseOnce) {
				$this->Session->delete('_Token.csrfTokens.' . $requestToken);
			}
			return true;
		}
		return false;
	}

/**
 * Expire CSRF nonces and remove them from the valid tokens.
 * Uses a simple timeout to expire the tokens.
 *
 * @param array $tokens An array of nonce => expires.
 * @return array An array of nonce => expires.
 */
	protected function _expireTokens($tokens) {
		$now = time();
		foreach ($tokens as $nonce => $expires) {
			if ($expires < $now) {
				unset($tokens[$nonce]);
			}
		}
		$overflow = count($tokens) - $this->csrfLimit;
		if ($overflow > 0) {
			$tokens = array_slice($tokens, $overflow + 1, null, true);
		}
		return $tokens;
	}

/**
 * Calls a controller callback method
 *
 * @param Controller $controller Controller to run callback on
 * @param string $method Method to execute
 * @param array $params Parameters to send to method
 * @return mixed Controller callback method's response
 * @throws BadRequestException When a the blackholeCallback is not callable.
 */
	protected function _callback(Controller $controller, $method, $params = array()) {
		if (is_callable(array($controller, $method))) {
			return call_user_func_array(array(&$controller, $method), empty($params) ? null : $params);
		} else {
			throw new BadRequestException(__d('cake_dev', 'The request has been black-holed'));
		}
	}

}
