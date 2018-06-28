<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Controller\Controller;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Routing\Router;
use LogicException;

/**
 * This class provides compatibility with dispatcher filters
 * and interacting with the controller layers.
 *
 * Long term this should just be the controller dispatcher, but
 * for now it will do a bit more than that.
 */
class ActionDispatcher implements EventDispatcherInterface
{
    use EventDispatcherTrait;

    /**
     * Attached routing filters
     *
     * @var \Cake\Event\EventListenerInterface[]
     */
    protected $filters = [];

    /**
     * Controller factory instance.
     *
     * @var \Cake\Http\ControllerFactory
     */
    protected $factory;

    /**
     * Constructor
     *
     * @param \Cake\Http\ControllerFactory|null $factory A controller factory instance.
     * @param \Cake\Event\EventManager|null $eventManager An event manager if you want to inject one.
     */
    public function __construct($factory = null, $eventManager = null)
    {
        if ($eventManager) {
            $this->setEventManager($eventManager);
        }
        $this->factory = $factory ?: new ControllerFactory();
    }

    /**
     * Dispatches a Request & Response
     *
     * @param \Cake\Http\ServerRequest $request The request to dispatch.
     * @param \Cake\Http\Response $response The response to dispatch.
     * @return \Cake\Http\Response A modified/replaced response.
     * @throws \ReflectionException
     */
    public function dispatch(ServerRequest $request, Response $response)
    {
        if (Router::getRequest(true) !== $request) {
            Router::pushRequest($request);
        }
        $beforeEvent = $this->dispatchEvent('Dispatcher.beforeDispatch', compact('request', 'response'));

        $request = $beforeEvent->getData('request');
        if ($beforeEvent->getResult() instanceof Response) {
            return $beforeEvent->getResult();
        }

        // Use the controller built by an beforeDispatch
        // event handler if there is one.
        if ($beforeEvent->getData('controller') instanceof Controller) {
            $controller = $beforeEvent->getData('controller');
        } else {
            $controller = $this->factory->create($request, $response);
        }

        $response = $this->_invoke($controller);
        if ($request->getParam('return')) {
            return $response;
        }

        $afterEvent = $this->dispatchEvent('Dispatcher.afterDispatch', compact('request', 'response'));

        return $afterEvent->getData('response');
    }

    /**
     * Invoke a controller's action and wrapping methods.
     *
     * @param \Cake\Controller\Controller $controller The controller to invoke.
     * @return \Cake\Http\Response The response
     * @throws \LogicException If the controller action returns a non-response value.
     */
    protected function _invoke(Controller $controller)
    {
        $this->dispatchEvent('Dispatcher.invokeController', ['controller' => $controller]);

        $result = $controller->startupProcess();
        if ($result instanceof Response) {
            return $result;
        }

        $response = $controller->invokeAction();
        if ($response !== null && !($response instanceof Response)) {
            throw new LogicException('Controller actions can only return Cake\Http\Response or null.');
        }

        if (!$response && $controller->isAutoRenderEnabled()) {
            $controller->render();
        }

        $result = $controller->shutdownProcess();
        if ($result instanceof Response) {
            return $result;
        }
        if (!$response) {
            $response = $controller->getResponse();
        }

        return $response;
    }

    /**
     * Get the connected filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
}
