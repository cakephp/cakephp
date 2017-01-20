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
namespace TestApp\Controller\Component;

use Cake\Controller\Component\AuthComponent;
use Cake\Event\Event;

/**
 * TestAuthComponent class
 */
class TestAuthComponent extends AuthComponent
{
    /**
     * @var string|null
     */
    public $authCheckCalledFrom = null;

    /**
     * @param Event $event
     * @return \Cake\Network\Response|null
     */
    public function authCheck(Event $event)
    {
        if (isset($this->earlyAuthTest)) {
            if ($this->_config['checkAuthIn'] !== $event->name()) {
                return null;
            }
            $this->authCheckCalledFrom = $event->name();

            return null;
        }

        return parent::authCheck($event);
    }

    /**
     * Helper method to add/set an authenticate object instance
     *
     * @param int $index The index at which to add/set the object
     * @param Object $object The object to add/set
     * @return void
     */
    public function setAuthenticateObject($index, $object)
    {
        $this->_authenticateObjects[$index] = $object;
    }

    /**
     * Helper method to add/set an authorize object instance
     *
     * @param int $index The index at which to add/set the object
     * @param Object $object The object to add/set
     * @return void
     */
    public function setAuthorizeObject($index, $object)
    {
        $this->_authorizeObjects[$index] = $object;
    }
}
