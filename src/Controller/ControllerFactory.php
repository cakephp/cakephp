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
namespace Cake\Controller;

use Cake\Core\App;
use Cake\Core\ContainerInterface;
use Cake\Core\Invoker;
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

/**
 * Factory method for building controllers for request.
 *
 * @implements \Cake\Http\ControllerFactoryInterface<\Cake\Controller\Controller>
 */
class ControllerFactory implements ControllerFactoryInterface, RequestHandlerInterface
{
    /**
     * @var \Cake\Core\ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var \Cake\Controller\Controller
     */
    protected Controller $controller;

    /**
     * Constructor
     *
     * @param \Cake\Core\ContainerInterface $container The container to build controllers with.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Create a controller for a given request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to build a controller for.
     * @return \Cake\Controller\Controller
     * @throws \Cake\Http\Exception\MissingControllerException
     */
    public function create(ServerRequestInterface $request): Controller
    {
        $className = $this->getControllerClass($request);
        if ($className === null) {
            throw $this->missingController($request);
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract()) {
            throw $this->missingController($request);
        }

        // If the controller has a container definition
        // add the request as a service.
        if ($this->container->has($className)) {
            $this->container->add(ServerRequest::class, $request);
            $controller = $this->container->get($className);
        } else {
            $controller = $reflection->newInstance($request);
        }

        return $controller;
    }

    /**
     * Invoke a controller's action and wrapping methods.
     *
     * @param mixed $controller The controller to invoke.
     * @return \Psr\Http\Message\ResponseInterface The response
     * @throws \Cake\Controller\Exception\MissingActionException If controller action is not found.
     * @throws \UnexpectedValueException If return value of action method is not null or ResponseInterface instance.
     * @psalm-param \Cake\Controller\Controller $controller
     */
    public function invoke(mixed $controller): ResponseInterface
    {
        $this->controller = $controller;

        $middlewares = $controller->getMiddleware();

        if ($middlewares) {
            $middlewareQueue = new MiddlewareQueue($middlewares);
            $runner = new Runner();

            return $runner->run($middlewareQueue, $controller->getRequest(), $this);
        }

        return $this->handle($controller->getRequest());
    }

    /**
     * Invoke the action.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request instance.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = $this->controller;
        /** @psalm-suppress ArgumentTypeCoercion */
        $controller->setRequest($request);

        $result = $controller->startupProcess();
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $action = $controller->getAction();
        $invoker = new Invoker($this->container);
        $args = $invoker->resolveArguments(
            $action,
            (array)$controller->getRequest()->getParam('pass'),
            ['coerce' => true]
        );
        $controller->invokeAction($action, $args);

        $result = $controller->shutdownProcess();
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        return $controller->getResponse();
    }

    /**
     * Determine the controller class name based on current request and controller param
     *
     * @param \Cake\Http\ServerRequest $request The request to build a controller for.
     * @return string|null
     * @psalm-return class-string<\Cake\Controller\Controller>|null
     */
    public function getControllerClass(ServerRequest $request): ?string
    {
        $pluginPath = '';
        $namespace = 'Controller';
        $controller = $request->getParam('controller', '');
        if ($request->getParam('plugin')) {
            $pluginPath = $request->getParam('plugin') . '.';
        }
        if ($request->getParam('prefix')) {
            $prefix = $request->getParam('prefix');
            $namespace .= '/' . $prefix;
        }
        $firstChar = substr($controller, 0, 1);

        // Disallow plugin short forms, / and \\ from
        // controller names as they allow direct references to
        // be created.
        if (
            str_contains($controller, '\\') ||
            str_contains($controller, '/') ||
            str_contains($controller, '.') ||
            $firstChar === strtolower($firstChar)
        ) {
            throw $this->missingController($request);
        }

        /** @var class-string<\Cake\Controller\Controller>|null */
        return App::className($pluginPath . $controller, $namespace, 'Controller');
    }

    /**
     * Throws an exception when a controller is missing.
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @return \Cake\Http\Exception\MissingControllerException
     */
    protected function missingController(ServerRequest $request): MissingControllerException
    {
        return new MissingControllerException([
            'class' => $request->getParam('controller'),
            'plugin' => $request->getParam('plugin'),
            'prefix' => $request->getParam('prefix'),
            '_ext' => $request->getParam('_ext'),
        ]);
    }
}
