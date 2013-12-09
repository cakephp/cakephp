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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 * - `passwordHasher` Password hasher class. Can be a string specifying class name
 *    or an array containing `className` key, any other keys will be passed as
 *    settings to the class. Defaults to 'Simple'.
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
		'passwordHasher' => 'Simple'
	);

/**
 * A Component collection, used to get more components.
 *
 * @var ComponentCollection
 */
	protected $_Collection;

/**
 * Password hasher instance.
 *
 * @var AbstractPasswordHasher
 */
	protected $_passwordHasher;

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
 * The $username parameter can be a (string)username or an array containing
 * conditions for Model::find('first'). If the $password param is not provided
 * the password field will be present in returned array.
 *
 * Input passwords will be hashed even when a user doesn't exist. This
 * helps mitigate timing attacks that are attempting to find valid usernames.
 *
 * @param string|array $username The username/identifier, or an array of find conditions.
 * @param string $password The password, only used if $username param is string.
 * @return boolean|array Either false on failure, or an array of user data.
 */
	protected function _findUser($username, $password = null) {
		$userModel = $this->settings['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		if (is_array($username)) {
			$conditions = $username;
		} else {
			$conditions = array(
				$model . '.' . $fields['username'] => $username
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
		if (empty($result[$model])) {
			$this->passwordHasher()->hash($password);
			return false;
		}

		$user = $result[$model];
		if ($password !== null) {
			if (!$this->passwordHasher()->check($password, $user[$fields['password']])) {
				return false;
			}
			unset($user[$fields['password']]);
		}

		unset($result[$model]);
		return array_merge($user, $result);
	}

/**
 * Return password hasher object
 *
 * @return AbstractPasswordHasher Password hasher instance
 * @throws CakeException If password hasher class not found or
 *   it does not extend AbstractPasswordHasher
 */
	public function passwordHasher() {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}

		$config = array();
		if (is_string($this->settings['passwordHasher'])) {
			$class = $this->settings['passwordHasher'];
		} else {
			$class = $this->settings['passwordHasher']['className'];
			$config = $this->settings['passwordHasher'];
			unset($config['className']);
		}
		list($plugin, $class) = pluginSplit($class, true);
		$className = $class . 'PasswordHasher';
		App::uses($className, $plugin . 'Controller/Component/Auth');
		if (!class_exists($className)) {
			throw new CakeException(__d('cake_dev', 'Password hasher class "%s" was not found.', $class));
		}
		if (!is_subclass_of($className, 'AbstractPasswordHasher')) {
			throw new CakeException(__d('cake_dev', 'Password hasher must extend AbstractPasswordHasher class.'));
		}
		$this->_passwordHasher = new $className($config);
		return $this->_passwordHasher;
	}

/**
 * Hash the plain text password so that it matches the hashed/encrypted password
 * in the datasource.
 *
 * @param string $password The plain text password.
 * @return string The hashed form of the password.
 * @deprecated Since 2.4. Use a PasswordHasher class instead.
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
	public function getUser(CakeRequest $request) {
		return false;
	}

/**
 * Handle unauthenticated access attempt.
 *
 * @param CakeRequest $request A request object.
 * @param CakeResponse $response A response object.
 * @return mixed Either true to indicate the unauthenticated request has been
 *  dealt with and no more action is required by AuthComponent or void (default).
 */
	public function unauthenticated(CakeRequest $request, CakeResponse $response) {
	}

}
