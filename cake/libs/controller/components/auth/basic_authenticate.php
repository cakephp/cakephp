<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Component', 'auth/base_authenticate');
App::import('Core', 'String');


class BasicAuthenticate extends BaseAuthenticate {
/**
 * Settings for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
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
		'realm' => '',
	);

/**
 * Constructor, completes configuration for basic authentication.
 *
 * @return void
 */
	public function __construct($settings) {
		parent::__construct($settings);
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
		$username = env('PHP_AUTH_USER');
		$pass = env('PHP_AUTH_PW');

		if (empty($username) || empty($pass)) {
			$response->header($this->loginHeaders());
			$response->send();
			return false;
		}

		$userModel = $this->settings['userModel'];
		list($plugin, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		$conditions = array(
			$model . '.' . $fields['username'] => $username,
			$model . '.' . $fields['password'] => $this->hash($pass),
		);
		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}
		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
			'recursive' => 0
		));
		if (empty($result) || empty($result[$model])) {
			$response->header($this->loginHeaders());
			$response->header('Location', Router::reverse($request));
			$response->statusCode(401);
			$response->send();
			return false;
		}
		unset($result[$model][$fields['password']]);
		return $result[$model];
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