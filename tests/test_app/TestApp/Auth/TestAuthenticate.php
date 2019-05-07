<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * TestAuthenticate class
 */
class TestAuthenticate extends BaseAuthenticate
{
    public $callStack = [];

    public $authenticationProvider;

    /**
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Auth.afterIdentify' => 'afterIdentify',
            'Auth.logout' => 'logout',
        ];
    }

    /**
     * @param \Cake\Http\ServerRequest $request
     * @param \Cake\Http\Response $response
     * @return array
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        return ['id' => 1, 'username' => 'admad'];
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @param array $user
     * @return array
     */
    public function afterIdentify(EventInterface $event, array $user)
    {
        $this->callStack[] = __FUNCTION__;
        $this->authenticationProvider = $event->getData('1');

        if (!empty($this->modifiedUser)) {
            return $user + ['extra' => 'foo'];
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @param array $user
     */
    public function logout(EventInterface $event, array $user)
    {
        $this->callStack[] = __FUNCTION__;
    }
}
