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

use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\Http\ActionDispatcher;
use Cake\Http\ServerRequest;
use Cake\Network\Response;

/**
 * Dispatcher converts Requests into controller actions. It uses the dispatched Request
 * to locate and load the correct controller. If found, the requested action is called on
 * the controller
 */
class Dispatcher
{

    use EventDispatcherTrait;

    /**
     * Connected filter objects
     *
     * @var \Cake\Event\EventListenerInterface[]
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
     * @param \Cake\Http\ServerRequest $request Request object to dispatch.
     * @param \Cake\Network\Response $response Response object to put the results of the dispatch into.
     * @return string|null if `$request['return']` is set then it returns response body, null otherwise
     * @throws \LogicException When the controller did not get created in the Dispatcher.beforeDispatch event.
     */
    public function dispatch(ServerRequest $request, Response $response)
    {
        $actionDispatcher = new ActionDispatcher(null, $this->eventManager(), $this->_filters);
        $response = $actionDispatcher->dispatch($request, $response);
        if (isset($request->params['return'])) {
            return $response->body();
        }

        return $response->send();
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
    }

    /**
     * Get the list of connected filters.
     *
     * @return \Cake\Event\EventListenerInterface[]
     */
    public function filters()
    {
        return $this->_filters;
    }
}
