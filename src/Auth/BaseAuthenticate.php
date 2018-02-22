<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Base Authentication class with common methods and properties.
 *
 * @mixin \Cake\Core\InstanceConfigTrait
 */
abstract class BaseAuthenticate implements EventListenerInterface
{

    use InstanceConfigTrait;
    use LocatorAwareTrait;

    /**
     * Default config for this object.
     *
     * - `fields` The fields to use to identify a user by.
     * - `userModel` The alias for users table, defaults to Users.
     * - `finder` The finder method to use to fetch user record. Defaults to 'all'.
     *   You can set finder name as string or an array where key is finder name and value
     *   is an array passed to `Table::find()` options.
     *   E.g. ['finderName' => ['some_finder_option' => 'some_value']]
     * - `passwordHasher` Password hasher class. Can be a string specifying class name
     *    or an array containing `className` key, any other keys will be passed as
     *    config to the class. Defaults to 'Default'.
     * - Options `scope` and `contain` have been deprecated since 3.1. Use custom
     *   finder instead to modify the query to fetch user record.
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
        'finder' => 'all',
        'contain' => null,
        'passwordHasher' => 'Default'
    ];

    /**
     * A Component registry, used to get more components.
     *
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $_registry;

    /**
     * Password hasher instance.
     *
     * @var \Cake\Auth\AbstractPasswordHasher
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
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;
        $this->setConfig($config);

        if ($this->getConfig('scope') || $this->getConfig('contain')) {
            deprecationWarning(
                'The `scope` and `contain` options for Authentication are deprecated. ' .
                'Use the `finder` option instead to define additional conditions.'
            );
        }
    }

    /**
     * Find a user record using the username and password provided.
     *
     * Input passwords will be hashed even when a user doesn't exist. This
     * helps mitigate timing attacks that are attempting to find valid usernames.
     *
     * @param string $username The username/identifier.
     * @param string|null $password The password, if not provided password checking is skipped
     *   and result of find is returned.
     * @return bool|array Either false on failure, or an array of user data.
     */
    protected function _findUser($username, $password = null)
    {
        $result = $this->_query($username)->first();

        if (empty($result)) {
            $hasher = $this->passwordHasher();
            $hasher->hash((string)$password);

            return false;
        }

        $passwordField = $this->_config['fields']['password'];
        if ($password !== null) {
            $hasher = $this->passwordHasher();
            $hashedPassword = $result->get($passwordField);
            if (!$hasher->check($password, $hashedPassword)) {
                return false;
            }

            $this->_needsPasswordRehash = $hasher->needsRehash($hashedPassword);
            $result->unsetProperty($passwordField);
        }
        $hidden = $result->getHidden();
        if ($password === null && in_array($passwordField, $hidden)) {
            $key = array_search($passwordField, $hidden);
            unset($hidden[$key]);
            $result->setHidden($hidden);
        }

        return $result->toArray();
    }

    /**
     * Get query object for fetching user from database.
     *
     * @param string $username The username/identifier.
     * @return \Cake\ORM\Query
     */
    protected function _query($username)
    {
        $config = $this->_config;
        $table = $this->getTableLocator()->get($config['userModel']);

        $options = [
            'conditions' => [$table->aliasField($config['fields']['username']) => $username]
        ];

        if (!empty($config['scope'])) {
            $options['conditions'] = array_merge($options['conditions'], $config['scope']);
        }
        if (!empty($config['contain'])) {
            $options['contain'] = $config['contain'];
        }

        $finder = $config['finder'];
        if (is_array($finder)) {
            $options += current($finder);
            $finder = key($finder);
        }

        if (!isset($options['username'])) {
            $options['username'] = $username;
        }

        return $table->find($finder, $options);
    }

    /**
     * Return password hasher object
     *
     * @return \Cake\Auth\AbstractPasswordHasher Password hasher instance
     * @throws \RuntimeException If password hasher class not found or
     *   it does not extend AbstractPasswordHasher
     */
    public function passwordHasher()
    {
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
    public function needsPasswordRehash()
    {
        return $this->_needsPasswordRehash;
    }

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Cake\Http\ServerRequest $request Request to get authentication information from.
     * @param \Cake\Http\Response $response A response object that can have headers added.
     * @return mixed Either false on failure, or an array of user data on success.
     */
    abstract public function authenticate(ServerRequest $request, Response $response);

    /**
     * Get a user based on information in the request. Primarily used by stateless authentication
     * systems like basic and digest auth.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return mixed Either false or an array of user information
     */
    public function getUser(ServerRequest $request)
    {
        return false;
    }

    /**
     * Handle unauthenticated access attempt. In implementation valid return values
     * can be:
     *
     * - Null - No action taken, AuthComponent should return appropriate response.
     * - Cake\Http\Response - A response object, which will cause AuthComponent to
     *   simply return that response.
     *
     * @param \Cake\Http\ServerRequest $request A request object.
     * @param \Cake\Http\Response $response A response object.
     * @return void
     */
    public function unauthenticated(ServerRequest $request, Response $response)
    {
    }

    /**
     * Returns a list of all events that this authenticate class will listen to.
     *
     * An authenticate class can listen to following events fired by AuthComponent:
     *
     * - `Auth.afterIdentify` - Fired after a user has been identified using one of
     *   configured authenticate class. The callback function should have signature
     *   like `afterIdentify(Event $event, array $user)` when `$user` is the
     *   identified user record.
     *
     * - `Auth.logout` - Fired when AuthComponent::logout() is called. The callback
     *   function should have signature like `logout(Event $event, array $user)`
     *   where `$user` is the user about to be logged out.
     *
     * @return array List of events this class listens to. Defaults to `[]`.
     */
    public function implementedEvents()
    {
        return [];
    }
}
