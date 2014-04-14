<?php
/**
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Error;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * Basic Authentication adapter for AuthComponent.
 *
 * Provides Basic HTTP authentication support for AuthComponent. Basic Auth will authenticate users
 * against the configured userModel and verify the username and passwords match. Clients using Basic Authentication
 * must support cookies. Since AuthComponent identifies users based on Session contents, clients using Basic
 * Auth must support cookies.
 *
 * ### Using Basic auth
 *
 * In your controller's components array, add auth + the required config
 * {{{
 *	public $components = array(
 *		'Auth' => array(
 *			'authenticate' => array('Basic')
 *		)
 *	);
 * }}}
 *
 * In your login function just call `$this->Auth->login()` without any checks for POST data. This
 * will send the authentication headers, and trigger the login dialog in the browser/client.
 */
class BasicAuthenticate extends BaseAuthenticate {

/**
 * Authenticate a user using HTTP auth. Will use the configured User model and attempt a
 * login using HTTP auth.
 *
 * @param \Cake\Network\Request $request The request to authenticate with.
 * @param \Cake\Network\Response $response The response to add headers to.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	public function authenticate(Request $request, Response $response) {
		return $this->getUser($request);
	}

/**
 * Get a user based on information in the request. Used by cookie-less auth for stateless clients.
 *
 * @param \Cake\Network\Request $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser(Request $request) {
		$username = $request->env('PHP_AUTH_USER');
		$pass = $request->env('PHP_AUTH_PW');

		if (empty($username) || empty($pass)) {
			return false;
		}
		return $this->_findUser($username, $pass);
	}

/**
 * Handles an unauthenticated access attempt by sending appropriate login headers
 *
 * @param Request $request A request object.
 * @param Response $response A response object.
 * @return void
 * @throws \Cake\Error\UnauthorizedException
 */
	public function unauthenticated(Request $request, Response $response) {
		$Exception = new Error\UnauthorizedException();
		$Exception->responseHeader(array($this->loginHeaders($request)));
		throw $Exception;
	}

/**
 * Generate the login headers
 *
 * @param \Cake\Network\Request $request Request object.
 * @return string Headers for logging in.
 */
	public function loginHeaders(Request $request) {
		$realm = $this->config('realm') ?: $request->env('SERVER_NAME');
		return sprintf('WWW-Authenticate: Basic realm="%s"', $realm);
	}

}
