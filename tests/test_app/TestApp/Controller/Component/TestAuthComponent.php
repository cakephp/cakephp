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
namespace TestApp\Controller\Component;

use Cake\Controller\Component\AuthComponent;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * TestAuthComponent class
 */
class TestAuthComponent extends AuthComponent
{
    /**
     * @var string|null
     */
    public $authCheckCalledFrom;

    /**
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null
     */
    public function authCheck(EventInterface $event): ?Response
    {
        if (isset($this->earlyAuthTest)) {
            if ($this->_config['checkAuthIn'] !== $event->getName()) {
                return null;
            }
            $this->authCheckCalledFrom = $event->getName();

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
