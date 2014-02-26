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
namespace Cake\Controller\Component\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\Auth\AbstractPasswordHasher;
use Cake\Core\App;
use Cake\Error;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Security;

/**
 * Base Authentication class with common methods and properties.
 *
 */
abstract class BaseAuthenticate {

/**
 * Settings for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The alias for users table, defaults to Users.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `['Users.is_active' => 1].`
 * - `contain` Extra models to contain and store in session.
 * - `passwordHasher` Password hasher class. Can be a string specifying class name
 *    or an array containing `className` key, any other keys will be passed as
 *    settings to the class. Defaults to 'Blowfish'.
 *
 * @var array
 */
	public $settings = [
		'fields' => [
			'username' => 'username',
			'password' => 'password'
		],
		'userModel' => 'Users',
		'scope' => [],
		'contain' => null,
		'passwordHasher' => 'Blowfish'
	];

/**
 * A Component registry, used to get more components.
 *
 * @var ComponentRegistry
 */
	protected $_registry;

/**
 * Password hasher instance.
 *
 * @var AbstractPasswordHasher
 */
	protected $_passwordHasher;

/**
 * Constructor
 *
 * @param ComponentRegistry $registry The Component registry used on this request.
 * @param array $settings Array of settings to use.
 */
	public function __construct(ComponentRegistry $registry, $settings) {
		$this->_registry = $registry;
		$this->settings = Hash::merge($this->settings, $settings);
	}

/**
 * Find a user record using the username and password provided.
 *
 * Input passwords will be hashed even when a user doesn't exist. This
 * helps mitigate timing attacks that are attempting to find valid usernames.
 *
 * @param string $username The username/identifier.
 * @param string $password The password, if not provide password checking is skipped
 *   and result of find is returned.
 * @return boolean|array Either false on failure, or an array of user data.
 */
	protected function _findUser($username, $password = null) {
		$userModel = $this->settings['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		$conditions = [$model . '.' . $fields['username'] => $username];

		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}

		$table = TableRegistry::get($userModel)->find('all');
		if ($this->settings['contain']) {
			$table = $table->contain($this->settings['contain']);
		}
		$result = $table
			->where($conditions)
			->hydrate(false)
			->first();

		if (empty($result)) {
			return false;
		}

		if ($password !== null) {
			if (!$this->passwordHasher()->check($password, $result[$fields['password']])) {
				return false;
			}
			unset($result[$fields['password']]);
		}

		return $result;
	}

/**
 * Return password hasher object
 *
 * @return AbstractPasswordHasher Password hasher instance
 * @throws \Cake\Error\Exception If password hasher class not found or
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
		$className = App::classname($class, 'Controller/Component/Auth', 'PasswordHasher');
		if (!class_exists($className)) {
			throw new Error\Exception(sprintf('Password hasher class "%s" was not found.', $class));
		}

		$this->_passwordHasher = new $className($config);
		if (!($this->_passwordHasher instanceof AbstractPasswordHasher)) {
			throw new Error\Exception('Password hasher must extend AbstractPasswordHasher class.');
		}
		return $this->_passwordHasher;
	}

/**
 * Authenticate a user based on the request information.
 *
 * @param \Cake\Network\Request $request Request to get authentication information from.
 * @param \Cake\Network\Response $response A response object that can have headers added.
 * @return mixed Either false on failure, or an array of user data on success.
 */
	abstract public function authenticate(Request $request, Response $response);

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
 * @param \Cake\Network\Request $request Request object.
 * @return mixed Either false or an array of user information
 */
	public function getUser(Request $request) {
		return false;
	}

/**
 * Handle unauthenticated access attempt. In implementation, will return true to indicate
 * the unauthenticated request has been dealt with and no more action is required by
 * AuthComponent or void (default).
 *
 * @param \Cake\Network\Request $request A request object.
 * @param \Cake\Network\Response $response A response object.
 * @return void
 */
	public function unauthenticated(Request $request, Response $response) {
	}

}
