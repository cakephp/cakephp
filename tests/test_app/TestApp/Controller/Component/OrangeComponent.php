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

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * OrangeComponent class
 *
 */
class OrangeComponent extends Component
{

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Banana'];

    /**
     * initialize method
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config)
    {
        $this->Controller = $this->_registry->getController();
        $this->Banana->testField = 'OrangeField';
    }

    /**
     * startup method
     *
     * @param Event $event
     * @return string
     */
    public function startup(Event $event)
    {
        $this->Controller->foo = 'pass';
    }
}
