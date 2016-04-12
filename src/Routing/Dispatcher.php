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
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Controller\Controller;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Exception\MissingControllerException;
use LogicException;

/**
 * Dispatcher converts Requests into controller actions. It uses the dispatched Request
 * to locate and load the correct controller. If found, the requested action is called on
 * the controller
 *
 */
class Dispatcher
{

    use EventDispatcherTrait;

    /**
     * Connected filter objects
     *
     * @var array
     */
    protected $_filters = [];

    /**
     * Dispatches and invokes given Request, handing over control to the involved controller. If the controller is set
     * to autoRender, via Controller::$autoRender, then Dispatcher will render the view.
     *
     * Actions in CakePHP can be any public method on a controller, that is not declared in Controller. If you
     * want controller methods to be public and in-accessible by URL, then prefix them with a `_`.
     * For example `public function _loadPosts() { }` would not be accessible via URL. Private and protected methods
     * are also not accessible via URL.
     *
     * If no controller of given name can be found, invoke() will throw an exception.
     * If the controller is found, and the action is not found an exception will be thrown.
     *
     * @param \Cake\Network\Request $request Request object to dispatch.
     * @param \Cake\Network\Response $response Response object to put the results of the dispatch into.
     * @return string|null if `$request['return']` is set then it returns response body, null otherwise
     * @throws \Cake\Routing\Exception\MissingControllerException When the controller is missing.
     */
    public function dispatch(Request $request, Response $response)
    {
        $beforeEvent = $this->dispatchEvent('Dispatcher.beforeDispatch', compact('request', 'response'));

        $request = $beforeEvent->data['request'];
        if ($beforeEvent->result instanceof Response) {
            if (isset($request->params['return'])) {
                return $beforeEvent->result->body();
            }
            $beforeEvent->result->send();
            return null;
        }

        $controller = false;
        if (isset($beforeEvent->data['controller'])) {
            $controller = $beforeEvent->data['controller'];
        }

        if (!($controller instanceof Controller)) {
            throw new MissingControllerException([
                'class' => $request->params['controller'],
                'plugin' => empty($request->params['plugin']) ? null : $request->params['plugin'],
                'prefix' => empty($request->params['prefix']) ? null : $request->params['prefix'],
                '_ext' => empty($request->params['_ext']) ? null : $request->params['_ext']
            ]);
        }

        $response = $this->_invoke($controller);
        if (isset($request->params['return'])) {
            return $response->body();
        }

        $afterEvent = $this->dispatchEvent('Dispatcher.afterDispatch', compact('request', 'response'));
        $afterEvent->data['response']->send();
    }

    /**
     * Initializes the components and models a controller will be using.
     * Triggers the controller action and invokes the rendering if Controller::$autoRender
     * is true. If a response object is returned by controller action that is returned
     * else controller's $response property is returned.
     *
     * @param \Cake\Controller\Controller $controller Controller to invoke
     * @return \Cake\Network\Response The resulting response object
     * @throws \LogicException If data returned by controller action is not an
     *   instance of Response
     */
    protected function _invoke(Controller $controller)
    {
        $result = $controller->startupProcess();
        if ($result instanceof Response) {
            return $result;
        }

        $response = $controller->invokeAction();
        if ($response !== null && !($response instanceof Response)) {
            throw new LogicException('Controller action can only return an instance of Response');
        }

        if (!$response && $controller->autoRender) {
            $response = $controller->render();
        } elseif (!$response) {
            $response = $controller->response;
        }

        $result = $controller->shutdownProcess();
        if ($result instanceof Response) {
            return $result;
        }

        return $response;
    }

    /**
     * Add a filter to this dispatcher.
     *
     * The added filter will be attached to the event manager used
     * by this dispatcher.
     *
     * @param \Cake\Event\EventListenerInterface $filter The filter to connect. Can be
     *   any EventListenerInterface. Typically an instance of \Cake\Routing\DispatcherFilter.
     * @return void
     */
    public function addFilter(EventListenerInterface $filter)
    {
        $this->_filters[] = $filter;
        $this->eventManager()->on($filter);
    }

    /**
     * Get the list of connected filters.
     *
     * @return array
     */
    public function filters()
    {
        return $this->_filters;
    }
}
