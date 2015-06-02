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

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;

/**
 * Cookie-based adapter for AuthComponent.
 *
 * Provides the ability to authenticate using COOKIE
 *
 * ```
 *    $this->Auth->config('authenticate', [
 *        'Authenticate.Cookie' => [
 *            'fields' => [
 *                'identifier' => 'username',
 *                'token' => 'password'
 *                'tokenCreated' => null
 *             ],
 *            'userModel' => 'Users',
 *            'scope' => ['Users.active' => 1],
 *            'crypt' => 'aes',
 *            'cookie' => [
 *                'name' => 'RememberMe',
 *                'time' => '+2 weeks',
 *            ]
 *        ]
 *    ]);
 * ```
 */
class CookieAuthenticate extends BaseAuthenticate
{

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry
     *   used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, $config)
    {
        $this->_registry = $registry;

        $this->config([
            'cookie' => [
                'name' => 'RememberMe',
                'expires' => '+2 weeks',
            ],
            'crypt' => 'aes',
            'fields' => ['tokenCreated' => null]
        ]);

        $aliases = [
            'identifier' => 'username',
            'token' => 'password'
        ];

        foreach ($aliases as $alias => $field) {
            if (isset($config['fields'][$alias])) {
                $config['fields'][$field] = $config['fields'][$alias];
                unset($config['fields'][$alias]);
            }
        }
        $this->config($config);
    }

    /**
     * Authenticates the identity contained in the cookie.  Will use the
     * `userModel` config, and `fields` config to find COOKIE data that is used
     * to find a matching record in the model specified by `userModel`. Will return
     * false if there is no cookie data, either identifier or token is missing,
     * or if the scope conditions have not been met.
     *
     * @param Request $request The unused request object.
     * @return mixed False on login failure. An array of User data on success.
     * @throws \RuntimeException If CookieComponent is not loaded.
     */
    public function getUser(Request $request)
    {
        if (!isset($this->_registry->Cookie) ||
        !$this->_registry->Cookie instanceof CookieComponent
        ) {
            throw new MissingComponentException(['class' => 'CookieComponent']);
        }

        $cookieConfig = $this->_config['cookie'];
        $cookieName = $this->_config['cookie']['name'];
        unset($cookieConfig['name']);
        $this->_registry->Cookie->configKey($cookieName, $cookieConfig);

        $data = $this->_registry->Cookie->read($cookieName);
        if (empty($data)) {
            return false;
        }

        extract($this->_config['fields']);

        if (empty($data[$username]) || empty($data[$password])) {
            return false;
        }

        $user = $this->_findUser($data[$username], $data[$password]);

        if ($tokenCreated !== null) {
            $expiration = str_replace('+', '', $this->config('cookie.expires'));
            if (!$user[$tokenCreated]->wasWithinLast($expiration)) {
                return false;
            }

            if (isset($user['password'])) {
                unset($user['password']);
            }
        }

        if ($user) {
            $request->session()->write(
                $this->_registry->Auth->sessionKey,
                $user
            );
            return $user;
        }

        return false;
    }

    /**
     * Authenticate user
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @return array|bool Array of user info on success, false on falure.
     */
    public function authenticate(Request $request, Response $response)
    {
        return $this->getUser($request);
    }

    /**
     * Called from AuthComponent::logout()
     *
     * @param \Cake\Event\Event $event The dispatched Auth.logout event.
     * @param array $user User record.
     * @return void
     */
    public function logout(Event $event, array $user)
    {
        $this->_registry->Cookie->delete($this->_config['cookie']['name']);
    }
}

