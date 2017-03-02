<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Network\Request;

/**
 * TestAuthenticate class
 */
class TestAuthenticate extends BaseAuthenticate
{

    public $callStack = [];

    public $authenticationProvider;

    public function implementedEvents()
    {
        return [
            'Auth.afterIdentify' => 'afterIdentify',
            'Auth.logout' => 'logout'
        ];
    }

    /**
     * @param \Cake\Http\ServerRequest $request
     * @param \Cake\Http\Response $response
     * @return array
     */
    public function authenticate(Request $request, Response $response)
    {
        return ['id' => 1, 'username' => 'admad'];
    }

    /**
     * @param \Cake\Event\Event $event
     * @param array $user
     * @return array
     */
    public function afterIdentify(Event $event, array $user)
    {
        $this->callStack[] = __FUNCTION__;
        $this->authenticationProvider = $event->data(1);

        if (!empty($this->modifiedUser)) {
            return $user + ['extra' => 'foo'];
        }
    }

    /**
     * @param \Cake\Event\Event $event
     * @param array $user
     */
    public function logout(Event $event, array $user)
    {
        $this->callStack[] = __FUNCTION__;
    }
}
