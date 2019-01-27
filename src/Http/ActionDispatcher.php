<?php
declare(strict_types=1);
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
use Cake\Http\Response;
use Cake\Routing\Router;
use LogicException;
use Psr\Http\Message\ResponseInterface;

/**
 * Dispatch the request to a controller for generating a response.
 */
class ActionDispatcher
{
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
     */
    public function __construct(?ControllerFactory $factory = null)
    {
        $this->factory = $factory ?: new ControllerFactory();
    }

    /**
     * Dispatches a Request & Response
     *
     * @param \Cake\Http\ServerRequest $request The request to dispatch.
     * @param \Psr\Http\Message\ResponseInterface $response The response to dispatch.
     * @return \Psr\Http\Message\ResponseInterface A modified/replaced response.
     */
    public function dispatch(ServerRequest $request, ?ResponseInterface $response = null): ResponseInterface
    {
        if ($response === null) {
            $response = new Response();
        }
        if (Router::getRequest(true) !== $request) {
            Router::pushRequest($request);
        }

        $controller = $this->factory->create($request, $response);

        return $this->_invoke($controller);
    }

    /**
     * Invoke a controller's action and wrapping methods.
     *
     * @param \Cake\Controller\Controller $controller The controller to invoke.
     * @return \Psr\Http\Message\ResponseInterface The response
     * @throws \LogicException If the controller action returns a non-response value.
     */
    protected function _invoke(Controller $controller): ResponseInterface
    {
        $result = $controller->startupProcess();
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $response = $controller->invokeAction();
        if ($response !== null && !($response instanceof ResponseInterface)) {
            throw new LogicException('Controller actions can only return Cake\Http\Response or null.');
        }

        if (!$response && $controller->isAutoRenderEnabled()) {
            $controller->render();
        }

        $result = $controller->shutdownProcess();
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        if (!$response) {
            $response = $controller->getResponse();
        }

        return $response;
    }
}
