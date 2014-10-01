<?php
/**
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
namespace Cake\Auth;

use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * Basic Authentication adapter for AuthComponent.
 *
 * Provides Basic HTTP authentication support for AuthComponent. Basic Auth will
 * authenticate users against the configured userModel and verify the username
 * and passwords match.
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
 * You should also set `AuthComponent::$sessionKey = false;` in your AppController's
 * beforeFilter() to prevent CakePHP from sending a session cookie to the client.
 *
 * Since HTTP Basic Authentication is stateless you don't need a login() action
 * in your controller. The user credentials will be checked on each request. If
 * valid credentials are not provided, required authentication headers will be sent
 * by this authentication provider which triggers the login dialog in the browser/client.
 *
 * You may also want to use `$this->Auth->unauthorizedRedirect = false;`.
 * By default, unauthorized users are redirected to the referrer URL,
 * `AuthComponent::$loginAction`, or '/'. If unauthorizedRedirect is set to
 * false, a ForbiddenException exception is thrown instead of redirecting.
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

		if (!is_string($username) || $username === '' || !is_string($pass) || $pass === '') {
			return false;
		}
		return $this->_findUser($username, $pass);
	}

/**
 * Handles an unauthenticated access attempt by sending appropriate login headers
 *
 * @param \Cake\Network\Request $request A request object.
 * @param \Cake\Network\Response $response A response object.
 * @return void
 * @throws \Cake\Network\Exception\UnauthorizedException
 */
	public function unauthenticated(Request $request, Response $response) {
		$Exception = new UnauthorizedException();
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
