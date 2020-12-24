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
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;

/**
 * Factory method for building controllers for request.
 *
 * @implements \Cake\Http\ControllerFactoryInterface<\Cake\Controller\Controller>
 */
class ControllerFactory implements ControllerFactoryInterface
{
    /**
     * @var \Cake\Core\ContainerInterface
     */
    protected $container;

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
    public function invoke($controller): ResponseInterface
    {
        $result = $controller->startupProcess();
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $action = $controller->getAction();

        $args = [];
        $reflection = new ReflectionFunction($action);
        $passed = array_values((array)$controller->getRequest()->getParam('pass'));
        foreach ($reflection->getParameters() as $i => $parameter) {
            $position = $parameter->getPosition();
            $hasDefault = $parameter->isDefaultValueAvailable();

            // If there is no type we can't look in the container
            // assume the parameter is a passed param
            $type = $parameter->getType();
            if (!$type) {
                if (count($passed)) {
                    $args[$position] = array_shift($passed);
                } elseif ($hasDefault) {
                    $args[$position] = $parameter->getDefaultValue();
                }
                continue;
            }
            $typeName = $type instanceof ReflectionNamedType ? ltrim($type->getName(), '?') : null;

            // Primitive types are passed args as they can't be looked up in the container.
            // We only handle strings currently.
            if ($typeName === 'string') {
                if (count($passed)) {
                    $args[$position] = array_shift($passed);
                } elseif ($hasDefault) {
                    $args[$position] = $parameter->getDefaultValue();
                } else {
                    $args[$position] = null;
                }
                continue;
            }

            // Check the container and parameter default value.
            if ($typeName && $this->container->has($typeName)) {
                $args[$position] = $this->container->get($typeName);
            } elseif ($hasDefault) {
                $args[$position] = $parameter->getDefaultValue();
            }
            if (!array_key_exists($position, $args)) {
                throw new InvalidArgumentException(
                    "Could not resolve action argument `{$parameter->getName()}`. " .
                    'It has no definition in the container, no passed parameter, and no default value.'
                );
            }
        }
        if (count($passed)) {
            $args = array_merge($args, $passed);
        }
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

            $firstChar = substr($prefix, 0, 1);
            if ($firstChar !== strtoupper($firstChar)) {
                deprecationWarning(
                    "The `{$prefix}` prefix did not start with an upper case character. " .
                    'Routing prefixes should be defined as CamelCase values. ' .
                    'Prefix inflection will be removed in 5.0'
                );

                if (strpos($prefix, '/') === false) {
                    $namespace .= '/' . Inflector::camelize($prefix);
                } else {
                    $prefixes = array_map(
                        function ($val) {
                            return Inflector::camelize($val);
                        },
                        explode('/', $prefix)
                    );
                    $namespace .= '/' . implode('/', $prefixes);
                }
            } else {
                $namespace .= '/' . $prefix;
            }
        }
        $firstChar = substr($controller, 0, 1);

        // Disallow plugin short forms, / and \\ from
        // controller names as they allow direct references to
        // be created.
        if (
            strpos($controller, '\\') !== false ||
            strpos($controller, '/') !== false ||
            strpos($controller, '.') !== false ||
            $firstChar === strtolower($firstChar)
        ) {
            throw $this->missingController($request);
        }

        // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.InvalidFormat
        /** @var class-string<\Cake\Controller\Controller>|null */
        return App::className($pluginPath . $controller, $namespace, 'Controller');
    }

    /**
     * Throws an exception when a controller is missing.
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @return \Cake\Http\Exception\MissingControllerException
     */
    protected function missingController(ServerRequest $request)
    {
        return new MissingControllerException([
            'class' => $request->getParam('controller'),
            'plugin' => $request->getParam('plugin'),
            'prefix' => $request->getParam('prefix'),
            '_ext' => $request->getParam('_ext'),
        ]);
    }
}
