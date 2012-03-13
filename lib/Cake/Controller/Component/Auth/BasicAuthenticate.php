<?php
/**
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('BaseAuthenticate', 'Controller/Component/Auth');

/**
 * Basic Authentication adapter for AuthComponent.
 *
 * Provides Basic HTTP authentication support for AuthComponent.  Basic Auth will authenticate users
 * against the configured userModel and verify the username and passwords match.  Clients using Basic Authentication
 * must support cookies.  Since AuthComponent identifies users based on Session contents, clients using Basic
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
 * In your login function just call `$this->Auth->login()` without any checks for POST data.  This
 * will send the authentication headers, and trigger the login dialog in the browser/client.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 */
class BasicAuthenticate extends BaseAuthenticate {

/**
 * Settings for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 * - `recursive` The value of the recursive key passed to find(). Defaults to 0.
 * - `realm` The realm authentication is for.  Defaults the server name.
 *
 * @var array
 */
	public $settings = array(
		'fields' => array(
			'username' => 'username',
			'password' => 'password'
		),
		'userModel' => 'User',
		'scope' => array(),
		'recursive' => 0,
		'realm' => '',
	);

/**
 * Constructor, completes configuration for basic authentication.
 *
 * @param ComponentCollection $collection The Component collection used on this request.
 * @param array $settings An array of settings.
 */
	public function __construct(ComponentCollection $collection, $settings) {
		parent::__construct($collection, $settings);
		if (empty($this->settings['realm'])) {
			$this->settings['realm'] = env('SERVER_NAME');
		}
	}

/**
 * Authenticate a user using basic HTTP auth.  Will use the configured User model and attempt a
 * login using basic HTTP auth.
 *
 * @param CakeRequest $request The request to authenticate with.
 * @param CakeResponse $response The response to add headers to.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$result = $this->getUser($request);

		if (empty($result)) {
			$response->header($this->loginHeaders());
			$response->statusCode(401);
			$response->send();
			return false;
		}
		return $result;
	}

/**
 * Get a user based on information in the request.  Used by cookie-less auth for stateless clients.
 *
 * @param CakeRequest $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser($request) {
		$username = env('PHP_AUTH_USER');
		$pass = env('PHP_AUTH_PW');

		if (empty($username) || empty($pass)) {
			return false;
		}
		return $this->_findUser($username, $pass);
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
