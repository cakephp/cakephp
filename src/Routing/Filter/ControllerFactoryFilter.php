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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\Http\ControllerFactory;
use Cake\Routing\DispatcherFilter;

/**
 * A dispatcher filter that builds the controller to dispatch
 * in the request.
 *
 * This filter resolves the request parameters into a controller
 * instance and attaches it to the event object.
 */
class ControllerFactoryFilter extends DispatcherFilter
{

    /**
     * Priority is set high to allow other filters to be called first.
     *
     * @var int
     */
    protected $_priority = 50;

    /**
     * Resolve the request parameters into a controller and attach the controller
     * to the event object.
     *
     * @param \Cake\Event\Event $event The event instance.
     * @return void
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data('request');
        $response = $event->data('response');
        $event->setData('controller', $this->_getController($request, $response));
    }

    /**
     * Gets controller to use, either plugin or application controller.
     *
     * @param \Cake\Network\Request $request Request object
     * @param \Cake\Network\Response $response Response for the controller.
     * @return \Cake\Controller\Controller
     */
    protected function _getController($request, $response)
    {
        $factory = new ControllerFactory();

        return $factory->create($request, $response);
    }
}
