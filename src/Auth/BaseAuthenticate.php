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
namespace Cake\Auth;

use Cake\Auth\PasswordHasherFactory;
use Cake\Controller\ComponentRegistry;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;

/**
 * Base Authentication class with common methods and properties.
 *
 */
abstract class BaseAuthenticate implements EventListenerInterface {

	use InstanceConfigTrait;

/**
 * Default config for this object.
 *
 * - `fields` The fields to use to identify a user by.
 * - `userModel` The alias for users table, defaults to Users.
 * - `scope` Additional conditions to use when looking up and authenticating users,
 *    i.e. `['Users.is_active' => 1].`
 * - `contain` Extra models to contain and store in session.
 * - `passwordHasher` Password hasher class. Can be a string specifying class name
 *    or an array containing `className` key, any other keys will be passed as
 *    config to the class. Defaults to 'Default'.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'fields' => [
			'username' => 'username',
			'password' => 'password'
		],
		'userModel' => 'Users',
		'scope' => [],
		'contain' => null,
		'passwordHasher' => 'Default'
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
 * Whether or not the user authenticated by this class
 * requires their password to be rehashed with another algorithm.
 *
 * @var bool
 */
	protected $_needsPasswordRehash = false;

/**
 * Constructor
 *
 * @param \Cake\Controller\ComponentRegistry $registry The Component registry used on this request.
 * @param array $config Array of config to use.
 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		$this->_registry = $registry;
		$this->config($config);
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
 * @return bool|array Either false on failure, or an array of user data.
 */
	protected function _findUser($username, $password = null) {
		$userModel = $this->_config['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->_config['fields'];

		$conditions = [$model . '.' . $fields['username'] => $username];

		$scope = $this->_config['scope'];
		if ($scope) {
			$conditions = array_merge($conditions, $scope);
		}

		$table = TableRegistry::get($userModel)->find('all');

		$contain = $this->_config['contain'];
		if ($contain) {
			$table = $table->contain($contain);
		}

		$result = $table
			->where($conditions)
			->hydrate(false)
			->first();

		if (empty($result)) {
			return false;
		}

		if ($password !== null) {
			$hasher = $this->passwordHasher();
			$hashedPassword = $result[$fields['password']];
			if (!$hasher->check($password, $hashedPassword)) {
				return false;
			}

			$this->_needsPasswordRehash = $hasher->needsRehash($hashedPassword);
			unset($result[$fields['password']]);
		}

		return $result;
	}

/**
 * Return password hasher object
 *
 * @return AbstractPasswordHasher Password hasher instance
 * @throws \RuntimeException If password hasher class not found or
 *   it does not extend AbstractPasswordHasher
 */
	public function passwordHasher() {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}

		$passwordHasher = $this->_config['passwordHasher'];
		return $this->_passwordHasher = PasswordHasherFactory::build($passwordHasher);
	}

/**
 * Returns whether or not the password stored in the repository for the logged in user
 * requires to be rehashed with another algorithm
 *
 * @return bool
 */
	public function needsPasswordRehash() {
		return $this->_needsPasswordRehash;
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
 * Handle unauthenticated access attempt. In implementation valid return values
 * can be:
 *
 * - Null - No action taken, AuthComponent should return appropriate response.
 * - Cake\Network\Response - A response object, which will cause AuthComponent to
 *   simply return that response.
 *
 * @param \Cake\Network\Request $request A request object.
 * @param \Cake\Network\Response $response A response object.
 * @return void
 */
	public function unauthenticated(Request $request, Response $response) {
	}

/**
 * Returns a list of all events that this authenticate class will listen to.
 *
 * An authenticate class can listen to following events fired by AuthComponent:
 *
 * - `Auth.afterIdentify` - Fired after a user has been identified using one of
 *   configured authenticate class. The callback function should have signature
 *   like `afteIndentify(Event $event, array $user)` when `$user` is the
 *   identified user record.
 *
 * - `Auth.logout` - Fired when AuthComponent::logout() is called. The callback
 *   function should have signature like `logout(Event $event, array $user)`
 *   where `$user` is the user about to be logged out.
 *
 * @return array List of events this class listens to. Defaults to `[]`.
 */
	public function implementedEvents() {
		return [];
	}

}
