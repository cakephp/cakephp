<?php
/**
 * PHP 5
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
 * In your controller's components array, add auth + the required settings.
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
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 */
class BasicAuthenticate extends BaseAuthenticate {

/**
 * Constructor, completes configuration for basic authentication.
 *
 * @param ComponentRegistry $registry The Component registry used on this request.
 * @param array $settings An array of settings.
 */
	public function __construct(ComponentRegistry $registry, $settings) {
		parent::__construct($registry, $settings);
		if (empty($this->settings['realm'])) {
			$this->settings['realm'] = env('SERVER_NAME');
		}
	}

/**
 * Authenticate a user using HTTP auth. Will use the configured User model and attempt a
 * login using HTTP auth.
 *
 * @param Cake\Network\Request $request The request to authenticate with.
 * @param Cake\Network\Response $response The response to add headers to.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	public function authenticate(Request $request, Response $response) {
		return $this->getUser($request);
	}

/**
 * Get a user based on information in the request. Used by cookie-less auth for stateless clients.
 *
 * @param Cake\Network\Request $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser(Request $request) {
		$username = env('PHP_AUTH_USER');
		$pass = env('PHP_AUTH_PW');

		if (empty($username) || empty($pass)) {
			return false;
		}
		return $this->_findUser($username, $pass);
	}

/**
 * Handles an unauthenticated access attempt by sending appropriate login headers
 *
 * @param CakeRequest $request A request object.
 * @param CakeResponse $response A response object.
 * @return void
 * @throws Cake\Error\UnauthorizedException
 */
	public function unauthenticated(Request $request, Response $response) {
		$Exception = new Error\UnauthorizedException();
		$Exception->responseHeader(array($this->loginHeaders()));
		throw $Exception;
	}

/**
 * Generate the login headers
 *
 * @return string Headers for logging in.
 */
	public function loginHeaders() {
		return sprintf('WWW-Authenticate: Basic realm="%s"', $this->settings['realm']);
	}

}
