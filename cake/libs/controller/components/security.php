<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
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
 * @since			CakePHP v 0.10.8.2156
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

	var $Security = null;

	var $blackHoleCallback = null;

	var $requirePost = array();

	var $requireAuth = array();

	var $requireLogin = array();

	var $loginOptions = array();

	var $loginUsers = array();

	var $allowedControllers = array();

	var $allowedActions = array();

	var $components = array('RequestHandler', 'Session');

/**
 * Security class constructor
 *
 */
	function __construct () {
		$this->Security = Security::getInstance();
	}

	function startup(&$controller) {

		// Check requirePost
		if (is_array($this->requirePost) && !empty($this->requirePost)) {
			if (in_array($controller->action, $this->requirePost) || $this->requirePost == array('*')) {

				if (!$this->RequestHandler->isPost()) {
					if (!$this->blackHole($controller, 'post')) {
						return null;
					}
				}
			}
		}

		// Check requireAuth
		if (is_array($this->requireAuth) && !empty($this->requireAuth) && !empty($controller->params['form'])) {
			if (in_array($controller->action, $this->requireAuth) || $this->requireAuth == array('*')) {

				if (!isset($controller->params['data']['_Token'])) {
					if (!$this->blackHole($controller, 'auth')) {
						return null;
					}
				}

				$token = $controller->params['data']['_Token']['key'];

				if ($this->Session->check('_Token')) {
					$tData = $this->Session->read('_Token');
					if (!(intval($tData['expires']) > strtotime('now')) || $tData['key'] !== $token) {
						if (!$this->blackHole($controller, 'auth')) {
							return null;
						}
					}
					if (!empty($tData['allowedControllers']) && !in_array($controller->params['controller'], $tData['allowedControllers']) ||!empty($tData['allowedActions']) && !in_array($controller->params['action'], $tData['allowedActions'])) {
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

		// Check requireLogin
		if (is_array($this->requireLogin) && !empty($this->requireLogin)) {
			if (in_array($controller->action, $this->requireLogin) || $this->requireLogin = array('*')) {

				if (!isset($this->loginOptions['type'])) {
					$this->loginOptions['type'] = '';
				}
				$login = $this->loginCredentials($this->loginOptions['type']);
				if ($login == null) {
					// User hasn't been authenticated yet
					header($this->loginRequest());
					if (isset($this->loginOptions['prompt'])) {
						$this->__callback($controller, $this->loginOptions['prompt']);
					} else {
						$this->blackHole($controller, 'login');
					}
				} else {
					if (isset($this->loginOptions['login'])) {
						$this->__callback($controller, $this->loginOptions['login'], array($login));
					} else {
						if (low($this->loginOptions['type']) == 'digest') {
							// Do digest authentication
						} else {
							if (!(in_array($login[0], array_keys($this->loginUsers)) && $this->loginUsers[$login[0]] == $login[1])) {
								$this->blackHole($controller, 'login');
							}
						}
					}
				}
			}
		}

		// Add auth key for new form posts
		$authKey = Security::generateAuthKey();
		$expires = strtotime('+'.Security::inactiveMins().' minutes');
		$token = array(
			'key' => $authKey,
			'expires' => $expires,
			'allowedControllers' => $this->allowedControllers,
			'allowedActions' => $this->allowedActions
		);

		if (!isset($controller->params['data'])) {
			$controller->params['data'] = array();
		}
		$controller->params['_Token'] = $token;
		$this->Session->write('_Token', $token);
	}
/**
 * Black-hole an invalid request with a 404 error or custom callback
 *
 */
	function blackHole(&$controller, $error = '') {
		if ($this->blackHoleCallback == null) {
			if ($error == 'login') {
				header('HTTP/1.0 401 Unauthorized');
			} else {
				header('HTTP/1.0 404 Not Found');
			}
			exit();
		} elseif (method_exists($controller, $this->blackHoleCallback)) {
			return $controller->{$this->blackHoleCallback}($error);
		}
	}
/**
 * Sets the actions that require a POST request, or empty for all actions
 *
 */
	function requirePost() {
		$this->requirePost = func_get_args();
		if (empty($this->requirePost)) {
			$this->requirePost = array('*');
		}
	}
/**
 * Sets the actions that require an authenticated request, or empty for all actions
 *
 */
	function requireAuth() {
		$this->requireAuth = func_get_args();
		if (empty($this->requireAuth)) {
			$this->requireAuth = array('*');
		}
	}
/**
 * Sets the actions that require an HTTP-authenticated request, or empty for all actions
 *
 */
	function requireLogin() {
		$args = func_get_args();
		foreach ($args as $arg) {
			if (is_array($arg)) {
				$this->loginOptions = $arg;
			} else {
				$this->requireLogin[] = $arg;
			}
		}
		if (empty($this->requireLogin)) {
			$this->requireLogin = array('*');
		}
		if (isset($this->loginOptions['users'])) {
			$this->loginUsers =& $this->loginOptions['users'];
		}
	}
/**
 * Gets the login credentials for an HTTP-authenticated request
 *
 * @param string $type Either 'basic', 'digest', or empty. If empty, will try both.
 * @return mixed If successful, returns an array with login name and password, otherwise null.
 */
	function loginCredentials($type = '') {

		if ($type == '' || low($type) == 'basic') {
			$login = array(env('PHP_AUTH_USER'), env('PHP_AUTH_PW'));
			if ($login[0] != null) {
				return $login;
			}
		}

		if ($type == '' || low($type) == 'digest') {

			$digest = null;
			if (version_compare(phpversion(), '5.1') != -1) {
				$digest = env('PHP_AUTH_DIGEST');

			} elseif (function_exists('apache_request_headers')) {
				$headers = apache_request_headers();
				if (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
					if (substr($headers['Authorization'], 0, 7) == 'Digest ') {
						$digest = substr($headers['Authorization'], 7);
					}
				}
			} else {
				// Server doesn't support digest-auth headers
				return null;
			}

			if ($digest == null) {
				return null;
			}
			$data = $this->parseDigestAuthData($digest);
		}

		return null;
	}
/**
 * Sets the default login options for an HTTP-authenticated request
 *
 */
	function __setLoginDefaults(&$options) {
		if (!isset($options['type']) || empty($options['type'])) {
			$options['type'] = 'basic';
		}
		if (!isset($options['realm']) || empty($options['realm'])) {
			$options['realm'] = env('SERVER_NAME');
		}
		if (!isset($options['qop']) || empty($options['qop'])) {
			$options['qop'] = 'auth';
		}
		if (!isset($options['nonce']) || empty($options['nonce'])) {
			$options['nonce'] = uniqid();
		}
		if (!isset($options['opaque']) || empty($options['opaque'])) {
			$options['opaque'] = md5($options['realm']);
		}
	}
/**
 * Generates the text of an HTTP-authentication request header from an array of options
 *
 */
	function loginRequest($options = array()) {
		if (empty($options)) {
			$options = $this->loginOptions;
		}
		$this->__setLoginDefaults($options);
		$data  = 'WWW-Authenticate: ' . ucfirst($options['type']);
		$data .= ' realm="' . $options['realm'] . '"';
		
		return $data;
	}
/**
 * Parses an HTTP digest authentication response, and returns an array of the data,
 * or null on failure.
 *
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
}

?>