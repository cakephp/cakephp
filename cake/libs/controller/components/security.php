<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP(tm) v 0.10.8.2156
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 */
class SecurityComponent extends Object {
/**
 * The controller method that will be called if this request is black-hole'd
 *
 * @var string
 * @access public
 */
	var $blackHoleCallback = null;
/**
 * List of controller actions for which a POST request is required
 *
 * @var array
 * @access public
 * @see SecurityComponent::requirePost()
 */
	var $requirePost = array();
/**
 * List of controller actions for which a GET request is required
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireGet()
 */
	var $requireGet = array();
/**
 * List of controller actions for which a PUT request is required
 *
 * @var array
 * @access public
 * @see SecurityComponent::requirePut()
 */
	var $requirePut = array();
/**
 * List of controller actions for which a DELETE request is required
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireDelete()
 */
	var $requireDelete = array();
/**
 * List of actions that require an SSL-secured connection
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireSecure()
 */
	var $requireSecure = array();
/**
 * List of actions that require a valid authentication key
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireAuth()
 */
	var $requireAuth = array();
/**
 * List of actions that require an HTTP-authenticated login (basic or digest)
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireLogin()
 */
	var $requireLogin = array();
/**
 * Login options for SecurityComponent::requireLogin()
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireLogin()
 */
	var $loginOptions = array('type' => '', 'prompt' => null);
/**
 * An associative array of usernames/passwords used for HTTP-authenticated logins.
 * If using digest authentication, passwords should be MD5-hashed.
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireLogin()
 */
	var $loginUsers = array();
/**
 * Controllers from which actions of the current controller are allowed to receive
 * requests.
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireAuth()
 */
	var $allowedControllers = array();
/**
 * Actions from which actions of the current controller are allowed to receive
 * requests.
 *
 * @var array
 * @access public
 * @see SecurityComponent::requireAuth()
 */
	var $allowedActions = array();
/**
 * Form fields to disable
 *
 * @var array
 * @access public
 */
	var $disabledFields = array();
/**
 * Other components used by the Security component
 *
 * @var array
 * @access public
 */
	var $components = array('RequestHandler', 'Session');
/**
 * Holds the current action of the controller
 *
 * @var string
 */
	var $__action = null;
/**
 * Component startup. All security checking happens here.
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function startup(&$controller) {
		$this->__action = strtolower($controller->action);
		$this->__methodsRequired($controller);
		$this->__secureRequired($controller);
		$this->__authRequired($controller);
		$this->__loginRequired($controller);

		if ((!isset($controller->params['requested']) || $controller->params['requested'] != 1) && ($this->RequestHandler->isPost() || $this->RequestHandler->isPut())) {
			$this->__validatePost($controller);
		}

		$this->__generateToken($controller);
	}
/**
 * Sets the actions that require a POST request, or empty for all actions
 *
 * @access public
 */
	function requirePost() {
		$args = func_get_args();
		$this->__requireMethod('Post', $args);
	}
/**
 * Sets the actions that require a GET request, or empty for all actions
 *
 * @access public
 */
	function requireGet() {
		$args = func_get_args();
		$this->__requireMethod('Get', $args);
	}
/**
 * Sets the actions that require a PUT request, or empty for all actions
 *
 * @access public
 */
	function requirePut() {
		$args = func_get_args();
		$this->__requireMethod('Put', $args);
	}
/**
 * Sets the actions that require a DELETE request, or empty for all actions
 *
 * @access public
 */
	function requireDelete() {
		$args = func_get_args();
		$this->__requireMethod('Delete', $args);
	}
/**
 * Sets the actions that require a request that is SSL-secured, or empty for all actions
 *
 * @access public
 */
	function requireSecure() {
		$args = func_get_args();
		$this->__requireMethod('Secure', $args);
	}
/**
 * Sets the actions that require an authenticated request, or empty for all actions
 *
 * @access public
 */
	function requireAuth() {
		$args = func_get_args();
		$this->__requireMethod('Auth', $args);
	}
/**
 * Sets the actions that require an HTTP-authenticated request, or empty for all actions
 *
 * @access public
 */
	function requireLogin() {
		$args = func_get_args();
		$base = $this->loginOptions;

		foreach ($args as $i => $arg) {
			if (is_array($arg)) {
				$this->loginOptions = $arg;
				unset($args[$i]);
			}
		}
		$this->loginOptions = array_merge($base, $this->loginOptions);
		$this->__requireMethod('Login', $args);

		if (isset($this->loginOptions['users'])) {
			$this->loginUsers =& $this->loginOptions['users'];
		}
	}
/**
 * Attempts to validate the login credentials for an HTTP-authenticated request
 *
 * @param string $type Either 'basic', 'digest', or null. If null/empty, will try both.
 * @return mixed If successful, returns an array with login name and password, otherwise null.
 * @access public
 */
	function loginCredentials($type = null) {
		switch (strtolower($type)) {
			case 'basic':
				$login = array('username' => env('PHP_AUTH_USER'), 'password' => env('PHP_AUTH_PW'));
				if (!empty($login['username'])) {
					return $login;
				}
			break;
			case 'digest':
			default:
				$digest = null;

				if (version_compare(phpversion(), '5.1') != -1) {
					$digest = env('PHP_AUTH_DIGEST');
				} elseif (function_exists('apache_request_headers')) {
					$headers = apache_request_headers();
					if (isset($headers['Authorization']) && !empty($headers['Authorization']) && substr($headers['Authorization'], 0, 7) == 'Digest ') {
						$digest = substr($headers['Authorization'], 7);
					}
				} else {
					// Server doesn't support digest-auth headers
					trigger_error(__('SecurityComponent::loginCredentials() - Server does not support digest authentication', true), E_USER_WARNING);
				}

				if (!empty($digest)) {
					return $this->parseDigestAuthData($digest);
				}
			break;
		}
		return null;
	}
/**
 * Generates the text of an HTTP-authentication request header from an array of options..
 *
 * @param array $options Set of options for header
 * @return string HTTP-authentication request header
 * @access public
 */
	function loginRequest($options = array()) {
		$options = array_merge($this->loginOptions, $options);
		$this->__setLoginDefaults($options);
		$auth = 'WWW-Authenticate: ' . ucfirst($options['type']);
		$out = array('realm="' . $options['realm'] . '"');

		if (strtolower($options['type']) == 'digest') {
			$out[] = 'qop="auth"';
			$out[] = 'nonce="' . uniqid() . '"'; //str_replace('-', '', String::uuid())
			$out[] = 'opaque="' . md5($options['realm']).'"';
		}

		return $auth . ' ' . join(',', $out);
	}
/**
 * Parses an HTTP digest authentication response, and returns an array of the data, or null on failure.
 *
 * @param string $digest Digest authentication response
 * @return array Digest authentication parameters
 * @access public
 */
	function parseDigestAuthData($digest) {
		if (substr($digest, 0, 7) == 'Digest ') {
			$digest = substr($digest, 7);
		}
		$keys = array();
		$match = array();
		$req = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
		preg_match_all('@(\w+)=([\'"]?)([a-zA-Z0-9=./\_-]+)\2@', $digest, $match, PREG_SET_ORDER);

		foreach ($match as $i) {
			$keys[$i[1]] = $i[3];
			unset($req[$i[1]]);
		}

		if (empty($req)) {
			return $keys;
		} else {
			return null;
		}
	}
/**
 * Generates a hash to be compared with an HTTP digest-authenticated response
 *
 * @param array $data HTTP digest response data, as parsed by SecurityComponent::parseDigestAuthData()
 * @return string Digest authentication hash
 * @access public
 * @see SecurityComponent::parseDigestAuthData()
 */
	function generateDigestResponseHash($data) {
		return md5(
			md5($data['username'] . ':' . $this->loginOptions['realm'] . ':' . $this->loginUsers[$data['username']]) .
			':' . $data['nonce'] . ':' . $data['nc'] . ':' . $data['cnonce'] . ':' . $data['qop'] . ':' .
			md5(env('REQUEST_METHOD') . ':' . $data['uri'])
		);
	}
/**
 * Black-hole an invalid request with a 404 error or custom callback. If SecurityComponent::$blackHoleCallback
 * is specified, it will use this callback by executing the method indicated in $error
 *
 * @param object $controller Instantiating controller
 * @param string $error Error method
 * @return mixed If specified, controller blackHoleCallback's response, or no return otherwise
 * @access public
 * @see SecurityComponent::$blackHoleCallback
 */
	function blackHole(&$controller, $error = '') {
		$this->Session->del('_Token');

		if ($this->blackHoleCallback == null) {
			$code = 404;
			if ($error == 'login') {
				$code = 401;
			}
			$controller->redirect(null, $code, true);
		} else {
			return $this->__callback($controller, $this->blackHoleCallback, array($error));
		}
	}
/**
 * Sets the actions that require a $method HTTP request, or empty for all actions
 *
 * @param string $method The HTTP method to assign controller actions to
 * @param array $actions Controller actions to set the required HTTP method to.
 * @access private
 */
	function __requireMethod($method, $actions = array()) {
		$this->{'require' . $method} = ife(empty($actions), array('*'), $actions);
	}
/**
 * Check if HTTP methods are required
 *
 * @param object $controller Instantiating controller
 * @return bool true if $method is required
 * @access private
 */
	function __methodsRequired(&$controller) {
		foreach (array('Post', 'Get', 'Put', 'Delete') as $method) {
			$property = 'require' . $method;
			if (is_array($this->$property) && !empty($this->$property)) {
				$require = array_map('strtolower', $this->$property);

				if (in_array($this->__action, $require) || $this->$property == array('*')) {
					if (!$this->RequestHandler->{'is' . $method}()) {
						if (!$this->blackHole($controller, strtolower($method))) {
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
 * @param object $controller Instantiating controller
 * @return bool true if secure connection required
 * @access private
 */
	function __secureRequired(&$controller) {
		if (is_array($this->requireSecure) && !empty($this->requireSecure)) {
			$requireSecure = array_map('strtolower', $this->requireSecure);

			if (in_array($this->__action, $requireSecure) || $this->requireSecure == array('*')) {
				if (!$this->RequestHandler->isSSL()) {
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
 * @param object $controller Instantiating controller
 * @return bool true if authentication required
 * @access private
 */
	function __authRequired(&$controller) {
		if (is_array($this->requireAuth) && !empty($this->requireAuth) && !empty($controller->data)) {
			$requireAuth = array_map('strtolower', $this->requireAuth);

			if (in_array($this->__action, $requireAuth) || $this->requireAuth == array('*')) {
				if (!isset($controller->data['__Token'] )) {
					if (!$this->blackHole($controller, 'auth')) {
						return null;
					}
				}

				if ($this->Session->check('_Token')) {
					$tData = unserialize($this->Session->read('_Token'));

					if (!empty($tData['allowedControllers']) && !in_array($controller->params['controller'], $tData['allowedControllers']) || !empty($tData['allowedActions']) && !in_array($controller->params['action'], $tData['allowedActions'])) {
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
 * Check if login is required
 *
 * @param object $controller Instantiating controller
 * @return bool true if login is required
 * @access private
 */
	function __loginRequired(&$controller) {
		if (is_array($this->requireLogin) && !empty($this->requireLogin)) {
			$requireLogin = array_map('strtolower', $this->requireLogin);

			if (in_array($this->__action, $requireLogin) || $this->requireLogin == array('*')) {
				$login = $this->loginCredentials($this->loginOptions['type']);

				if ($login == null) {
					// User hasn't been authenticated yet
					header($this->loginRequest());

					if (!empty($this->loginOptions['prompt'])) {
						$this->__callback($controller, $this->loginOptions['prompt']);
					} else {
						$this->blackHole($controller, 'login');
					}
				} else {
					if (isset($this->loginOptions['login'])) {
						$this->__callback($controller, $this->loginOptions['login'], array($login));
					} else {
						if (strtolower($this->loginOptions['type']) == 'digest') {
							// Do digest authentication
							if ($login && isset($this->loginUsers[$login['username']])) {
								if ($login['response'] == $this->generateDigestResponseHash($login)) {
									return true;
								}
							}
							$this->blackHole($controller, 'login');
						} else {
							if (!(in_array($login['username'], array_keys($this->loginUsers)) && $this->loginUsers[$login['username']] == $login['password'])) {
								$this->blackHole($controller, 'login');
							}
						}
					}
				}
			}
		}
		return true;
	}
/**
 * Validate submitted form
 *
 * @param object $controller Instantiating controller
 * @return bool true if submitted form is valid
 * @access private
 */
	function __validatePost(&$controller) {
		if (!empty($controller->data)) {
			if (!isset($controller->data['__Token'])) {
				if (!$this->blackHole($controller, 'auth')) {
					return null;
				}
			}
			$token = $controller->data['__Token']['key'];

			if ($this->Session->check('_Token')) {
				$tData = unserialize($this->Session->read('_Token'));

				if ($tData['expires'] < time() || $tData['key'] !== $token) {
					if (!$this->blackHole($controller, 'auth')) {
						return null;
					}
				}
			}

			if (!isset($controller->data['__Token']['fields'])) {
				if (!$this->blackHole($controller, 'auth')) {
					return null;
				}
			}
			$form = $controller->data['__Token']['fields'];
			$check = $controller->data;
			unset($check['__Token']['fields']);

			if (!empty($this->disabledFields)) {
				foreach ($check as $model => $fields) {
					foreach ($fields as $field => $value) {
						$key[] = $model . '.' . $field;
					}
					unset($field);
				}

				foreach ($this->disabledFields as $value) {
					$parts = preg_split('/\/|\./', $value);

					if (count($parts) == 1) {
						$key1[] = $controller->modelClass . '.' . $parts['0'];
					} elseif (count($parts) == 2) {
						$key1[] = $parts['0'] . '.' . $parts['1'];
					}
				}

				foreach ($key1 as $value) {
					if (in_array($value, $key)) {
						$remove = explode('.', $value);
						unset($check[$remove['0']][$remove['1']]);
					} elseif (in_array('_' . $value, $key)) {
						$remove = explode('.', $value);
						$controller->data[$remove['0']][$remove['1']] = $controller->data['_' . $remove['0']][$remove['1']];
						unset($check['_' . $remove['0']][$remove['1']]);
					}
				}
			}
			ksort($check);
			foreach ($check as $key => $value) {
				$merge = array();
				if ($key === '__Token') {
					$field[$key] = $value;
					continue;
				}
				$string = substr($key, 0, 1);

				if ($string === '_') {
					$newKey = substr($key, 1);

					if (!isset($controller->data[$newKey])) {
						$controller->data[$newKey] = array();

						if (array_keys($controller->data[$key]) === array($newKey)) {
							$field[$newKey] = array($newKey);
						}
					}

					if (is_array($value)) {
						$values = array_values($value);
						$k = array_keys($value);
						$count = count($k);

						if (is_numeric($k[0])) {
							for ($i = 0; $count > $i; $i++) {
								foreach ($values[$i] as $key2 => $value1) {
									if ($value1 === '0') {
										$field[$newKey][$i] = array_merge($field[$newKey][$i], array($key2));
									}
								}
							}
							$controller->data[$newKey] = Set::pushDiff($controller->data[$key], $controller->data[$newKey]);
						}

						for ($i = 0; $count > $i; $i++) {
							$field[$key][$k[$i]] = $values[$i];
						}

						foreach ($k as $lookup) {
							if (isset($controller->data[$newKey][$lookup])) {
								unset($controller->data[$key][$lookup]);
							} elseif ($controller->data[$key][$lookup] === '0') {
								$merge[] = $lookup;
							}
						}

						if (!is_numeric($k[0])) {
							if (isset($field[$newKey])) {
								$field[$newKey] = array_merge($merge, $field[$newKey]);
							} else {
								$field[$newKey] = $merge;
							}
							$controller->data[$newKey] = Set::pushDiff($controller->data[$key], $controller->data[$newKey]);
						}
						unset($controller->data[$key]);
					}
					continue;
				}
				if (is_array($value)) {
					$keys = array_keys($value);
				} else {
					$keys = $value;
				}

				if (isset($field[$key])) {
					$field[$key] = array_merge($field[$key], $keys);
				} elseif (is_array($keys) && !empty($keys) && is_numeric($keys[0])) {
					foreach ($value as $fields) {
						$merge[] = array_keys($fields);
					}
					$field[$key] = $merge;
				} else if (is_array($keys)) {
					$field[$key] = $keys;
				} else {
					$field[] = $key;
				}
			}

			foreach ($field as $key => $value) {
				if ($key[0] != '_' && is_array($field[$key])) {
					sort($field[$key]);
				}
			}
			ksort($field, SORT_STRING);

			$check = urlencode(Security::hash(serialize($field) . Configure::read('Security.salt')));
			if ($form !== $check) {
				if (!$this->blackHole($controller, 'auth')) {
					return null;
				}
			}
		}
		return true;
	}
/**
 * Add authentication key for new form posts
 *
 * @param object $controller Instantiating controller
 * @return bool Success
 * @access private
 */
	function __generateToken(&$controller) {
		if (!isset($controller->params['requested']) || $controller->params['requested'] != 1) {
			$authKey = Security::generateAuthKey();
			$expires = strtotime('+' . Security::inactiveMins() . ' minutes');
			$token = array(
				'key' => $authKey,
				'expires' => $expires,
				'allowedControllers' => $this->allowedControllers,
				'allowedActions' => $this->allowedActions,
				'disabledFields' => $this->disabledFields
			);

			if (!isset($controller->data)) {
				$controller->data = array();
			}

			if ($this->Session->check('_Token')) {
				$tData = unserialize($this->Session->read('_Token'));
				if (isset($tData['expires']) && $tData['expires'] > time() && isset($tData['key'])) {
					$token['key'] = $tData['key'];
				}
			}
			$controller->params['_Token'] = $token;
			$this->Session->write('_Token', serialize($token));
		}
		return true;
	}
/**
 * Sets the default login options for an HTTP-authenticated request
 *
 * @param array $options Default login options
 * @access private
 */
	function __setLoginDefaults(&$options) {
		$options = array_merge(array(
			'type' => 'basic',
			'realm' => env('SERVER_NAME'),
			'qop' => 'auth',
			'nonce' => String::uuid()
		), array_filter($options));
		$options = array_merge(array('opaque' => md5($options['realm'])), $options);
	}
/**
 * Calls a controller callback method
 *
 * @param object $controller Controller to run callback on
 * @param string $method Method to execute
 * @param array $params Parameters to send to method
 * @return mixed Controller callback method's response
 * @access private
 */
	function __callback(&$controller, $method, $params = array()) {
		if (is_callable(array($controller, $method))) {
			return call_user_func_array(array(&$controller, $method), empty($params) ? null : $params);
		} else {
			// Debug::warning('Callback method ' . $method . ' in controller ' . get_class($controller)
			return null;
		}
	}
}
?>
