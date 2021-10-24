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

use Cake\Controller\Component;
use Cake\Event\EventInterface;

/**
 * OrangeComponent class
 *
 * @property BananaComponent $Banana
 */
class OrangeComponent extends Component
{
    /**
     * components property
     *
     * @var array
     */
    protected $components = ['Banana'];

    /**
     * controller property
     *
     * @var Controller
     */
    public $Controller;

    /**
     * initialize method
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->Controller = $this->_registry->getController();
        $this->Banana->testField = 'OrangeField';
    }

    /**
     * startup method
     *
     * @return void
     */
    public function startup(EventInterface $event): void
    {
        $this->Controller->foo = 'pass';
    }
}
