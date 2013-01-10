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
App::uses('Security', 'Utility');
App::uses('Hash', 'Utility');

/**
 * Base Authentication class with common methods and properties.
 *
 * @package       Cake.Controller.Component.Auth
 */
abstract class BaseAuthenticate {

/**
 * Settings for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The model name of the User, defaults to User.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `array('User.is_active' => 1).`
 * - `recursive` The value of the recursive key passed to find(). Defaults to 0.
 * - `contain` Extra models to contain and store in session.
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
		'contain' => null,
	);

/**
 * A Component collection, used to get more components.
 *
 * @var ComponentCollection
 */
	protected $_Collection;

/**
 * Constructor
 *
 * @param ComponentCollection $collection The Component collection used on this request.
 * @param array $settings Array of settings to use.
 */
	public function __construct(ComponentCollection $collection, $settings) {
		$this->_Collection = $collection;
		$this->settings = Hash::merge($this->settings, $settings);
	}

/**
 * Find a user record using the standard options.
 *
 * The $conditions parameter can be a (string)username or an array containing conditions for Model::find('first'). If
 * the password field is not included in the conditions the password will be returned.
 *
 * @param Mixed $conditions The username/identifier, or an array of find conditions.
 * @param Mixed $password The password, only use if passing as $conditions = 'username'.
 * @return Mixed Either false on failure, or an array of user data.
 */
	protected function _findUser($conditions, $password = null) {
		$userModel = $this->settings['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		if (!is_array($conditions)) {
			if (!$password) {
				return false;
			}
			$username = $conditions;
			$conditions = array(
				$model . '.' . $fields['username'] => $username,
				$model . '.' . $fields['password'] => $this->_password($password),
			);
		}
		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}
		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
			'recursive' => $this->settings['recursive'],
			'contain' => $this->settings['contain'],
		));
		if (empty($result) || empty($result[$model])) {
			return false;
		}
		$user = $result[$model];
		if (
			isset($conditions[$model . '.' . $fields['password']]) ||
			isset($conditions[$fields['password']])
		) {
			unset($user[$fields['password']]);
		}
		unset($result[$model]);
		return array_merge($user, $result);
	}

/**
 * Hash the plain text password so that it matches the hashed/encrypted password
 * in the datasource.
 *
 * @param string $password The plain text password.
 * @return string The hashed form of the password.
 */
	protected function _password($password) {
		return Security::hash($password, null, true);
	}

/**
 * Authenticate a user based on the request information.
 *
 * @param CakeRequest $request Request to get authentication information from.
 * @param CakeResponse $response A response object that can have headers added.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	abstract public function authenticate(CakeRequest $request, CakeResponse $response);

/**
 * Allows you to hook into AuthComponent::logout(),
 * and implement specialized logout behavior.
 *
 * All attached authentication objects will have this method
 * called when a user logs out.
 *
 * @param array $user The user about to be logged out.
 * @return void
 */
	public function logout($user) {
	}

/**
 * Get a user based on information in the request. Primarily used by stateless authentication
 * systems like basic and digest auth.
 *
 * @param CakeRequest $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser($request) {
		return false;
	}

}
