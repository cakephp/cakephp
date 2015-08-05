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
 * @since         3.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Event;

use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * {@inheritdoc}
 */
class ControllerEvent extends Event
{

    /**
     * {@inheritdoc}
     *
     * @param string $name Name of the event
     * @param \Cake\Controller\Controller $controller the controller that this event applies to
     * @param array|null $data any value you wish to be transported with this event to it can be read by listeners
     */
    public function __construct($name, Controller $controller, $data = null)
    {
        if (strpos($name, '.') === false) {
            $name = 'Controller.' . $name;
        }

        parent::__construct($name, $controller, $data);
    }

    /**
     * Returns the controller this event applies to
     *
     * @return \Cake\Controller\Controller
     */
    public function controller()
    {
        return $this->subject();
    }
}
