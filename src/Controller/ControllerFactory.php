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

use Cake\Controller\Attribute\ParameterAttributeInterface;
use Cake\Controller\Exception\InvalidParameterException;
use Cake\Core\App;
use Cake\Core\ContainerInterface;
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\Http\ServerRequest;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use function Cake\Core\toBool;
use function Cake\Core\toFloat;
use function Cake\Core\toInt;

/**
 * Factory method for building controllers for request.
 *
 * @implements \Cake\Http\ControllerFactoryInterface<\Cake\Controller\Controller>
 */
class ControllerFactory implements ControllerFactoryInterface, RequestHandlerInterface
{
    /**
     * @var \Cake\Controller\Controller
     */
    protected Controller $controller;

    /**
     * Constructor
     *
     * @param \Cake\Core\ContainerInterface $container The container to build controllers with.
     */
    public function __construct(protected ContainerInterface $container)
    {
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
        assert($request instanceof ServerRequest);
        $className = $this->getControllerClass($request);
        if ($className === null) {
            throw $this->missingController($request);
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract()) {
            throw $this->missingController($request);
        }
        $this->container->addShared(
            ComponentRegistry::class,
            new ComponentRegistry(container: $this->container),
        );

        // Get the controller from the container if the user explicitly defined it.
        // `has()` also returns true for any existing class due to auto-wiring,
        // so `hasDefinition()` is used here to check for an explicit registration only.
        if ($this->container->hasDefinition($className)) {
            $controller = $this->container->get($className);
        } else {
            $components = $this->container->get(ComponentRegistry::class);
            $constructor = $reflection->getConstructor();
            assert($constructor !== null);
            $hasComponents = false;
            foreach ($constructor->getParameters() as $parameter) {
                $paramType = $parameter->getType();
                // TODO: In a future minor release it would be good to start requiring the components parameter
                if (
                    $parameter->getName() === 'components' &&
                    $paramType instanceof ReflectionNamedType &&
                    $paramType->getName() === ComponentRegistry::class
                ) {
                    $hasComponents = true;
                    break;
                }
            }
            if ($hasComponents) {
                $controller = $reflection->newInstance(request: $request, components: $components);
            } else {
                $controller = $reflection->newInstance($request);
            }
        }

        return $controller;
    }

    /**
     * Invoke a controller's action and wrapping methods.
     *
     * @param \Cake\Controller\Controller $controller The controller to invoke.
     * @return \Psr\Http\Message\ResponseInterface The response
     * @throws \Cake\Controller\Exception\MissingActionException If controller action is not found.
     * @throws \UnexpectedValueException If return value of action method is not null or ResponseInterface instance.
     */
    public function invoke(mixed $controller): ResponseInterface
    {
        $this->controller = $controller;

        $middlewares = $controller->getMiddleware();

        if ($middlewares) {
            $middlewareQueue = new MiddlewareQueue($middlewares, $this->container);
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
        assert($request instanceof ServerRequest);
        $controller = $this->controller;
        $controller->setRequest($request);

        $result = $controller->startupProcess();
        if ($result !== null) {
            return $result;
        }

        $action = $controller->getAction();
        $args = $this->getActionArgs(
            $action,
            (array)$controller->getRequest()->getParam('pass'),
            (array)$controller->getRequest()->getParam('_argsByName'),
        );
        $controller->invokeAction($action, $args);

        $result = $controller->shutdownProcess();
        if ($result !== null) {
            return $result;
        }

        return $controller->getResponse();
    }

    /**
     * Get the arguments for the controller action invocation.
     *
     * @param \Closure $action Controller action.
     * @param array<int|string, mixed> $passedParams Params passed by the router.
     * @param array<string, int> $argsByName Parameter-name to positional-index map.
     * @return array<int, mixed>
     */
    protected function getActionArgs(Closure $action, array $passedParams, array $argsByName = []): array
    {
        if ($argsByName !== [] && array_is_list($passedParams)) {
            $passedParams = $this->applyArgsByNameMap($passedParams, $argsByName);
        }

        $resolved = [];
        $namedPass = !array_is_list($passedParams);
        $function = new ReflectionFunction($action);
        $request = $this->controller->getRequest();
        foreach ($function->getParameters() as $parameter) {
            $attributeValue = $this->resolveParameterAttribute($parameter, $request);
            if ($attributeValue !== null) {
                $resolved[] = $attributeValue;
                continue;
            }

            $type = $parameter->getType();

            // Check for dependency injection for classes
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                // `has()` also returns true for any existing class due to auto-wiring,
                // so `hasDefinition()` is used here to check for an explicit registration only.
                if ($this->container->hasDefinition($typeName)) {
                    $resolved[] = $this->container->get($typeName);
                    continue;
                }

                if ($namedPass && array_key_exists($parameter->getName(), $passedParams)) {
                    $argument = $passedParams[$parameter->getName()];
                    if ($argument instanceof $typeName) {
                        unset($passedParams[$parameter->getName()]);
                        $resolved[] = $argument;

                        continue;
                    }
                }

                $firstPositionalKey = $this->getFirstPositionalParamKey($passedParams);

                // Use passedParams as a source of typed dependencies.
                // The accepted types for passedParams was never defined and userland code relies on that.
                if ($firstPositionalKey !== null && $passedParams[$firstPositionalKey] instanceof $typeName) {
                    $resolved[] = $passedParams[$firstPositionalKey];
                    unset($passedParams[$firstPositionalKey]);

                    continue;
                }

                // Add default value if provided
                // Do not allow positional arguments for classes
                if ($parameter->isDefaultValueAvailable()) {
                    $resolved[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new InvalidParameterException([
                    'template' => 'missing_dependency',
                    'parameter' => $parameter->getName(),
                    'type' => $typeName,
                    'controller' => $this->controller->getName(),
                    'action' => $this->controller->getRequest()->getParam('action'),
                    'prefix' => $this->controller->getRequest()->getParam('prefix'),
                    'plugin' => $this->controller->getRequest()->getParam('plugin'),
                ]);
            }

            // Use any passed params as positional arguments
            $hasArgument = false;
            $argument = null;
            if ($namedPass && array_key_exists($parameter->getName(), $passedParams)) {
                $argument = $passedParams[$parameter->getName()];
                unset($passedParams[$parameter->getName()]);
                $hasArgument = true;
            } else {
                $firstPositionalKey = $this->getFirstPositionalParamKey($passedParams);
                if ($firstPositionalKey !== null) {
                    $argument = $passedParams[$firstPositionalKey];
                    unset($passedParams[$firstPositionalKey]);
                    $hasArgument = true;
                }
            }
            if ($hasArgument) {
                if (is_string($argument) && $type instanceof ReflectionNamedType) {
                    $typedArgument = $this->coerceStringToType($argument, $type);

                    if ($typedArgument === null) {
                        throw new InvalidParameterException([
                            'template' => 'failed_coercion',
                            'passed' => $argument,
                            'type' => $type->getName(),
                            'parameter' => $parameter->getName(),
                            'controller' => $this->controller->getName(),
                            'action' => $this->controller->getRequest()->getParam('action'),
                            'prefix' => $this->controller->getRequest()->getParam('prefix'),
                            'plugin' => $this->controller->getRequest()->getParam('plugin'),
                        ]);
                    }
                    $argument = $typedArgument;
                }

                $resolved[] = $argument;
                continue;
            }

            // Add default value if provided
            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            // Variadic parameter can have 0 arguments
            if ($parameter->isVariadic()) {
                continue;
            }

            throw new InvalidParameterException([
                'template' => 'missing_parameter',
                'parameter' => $parameter->getName(),
                'controller' => $this->controller->getName(),
                'action' => $this->controller->getRequest()->getParam('action'),
                'prefix' => $this->controller->getRequest()->getParam('prefix'),
                'plugin' => $this->controller->getRequest()->getParam('plugin'),
            ]);
        }

        return array_merge($resolved, array_values($passedParams));
    }

    /**
     * Applies named argument mapping to positional route parameters.
     *
     * @param array<int, mixed> $passedParams Positional route parameters.
     * @param array<string, int> $argsByName Parameter-name to index map.
     * @return array<int|string, mixed>
     */
    protected function applyArgsByNameMap(array $passedParams, array $argsByName): array
    {
        $mapped = [];
        foreach ($argsByName as $name => $index) {
            if (!array_key_exists($index, $passedParams)) {
                continue;
            }
            $mapped[$name] = $passedParams[$index];
            unset($passedParams[$index]);
        }

        return array_replace($mapped, $passedParams);
    }

    /**
     * Returns the first positional argument key from route params.
     *
     * @param array<int|string, mixed> $passedParams Passed route parameters.
     * @return int|null
     */
    protected function getFirstPositionalParamKey(array $passedParams): ?int
    {
        foreach ($passedParams as $key => $_value) {
            if (is_int($key)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Resolve parameter value from attributes implementing ParameterAttributeInterface.
     *
     * @param \ReflectionParameter $parameter The parameter to resolve
     * @param \Cake\Http\ServerRequest $request The server request
     * @return mixed The resolved value or null if no matching attribute found
     */
    protected function resolveParameterAttribute(ReflectionParameter $parameter, ServerRequest $request): mixed
    {
        foreach ($parameter->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof ParameterAttributeInterface) {
                return $instance->resolve($parameter, $request);
            }
        }

        return null;
    }

    /**
     * Coerces string argument to primitive type.
     *
     * @param string $argument Argument to coerce
     * @param \ReflectionNamedType $type Parameter type
     * @return array|string|float|int|bool|null
     */
    protected function coerceStringToType(string $argument, ReflectionNamedType $type): array|string|float|int|bool|null
    {
        return match ($type->getName()) {
            'string' => $argument,
            'float' => toFloat($argument),
            'int' => toInt($argument),
            'bool' => toBool($argument),
            'array' => $argument === '' ? [] : explode(',', $argument),
            default => null,
        };
    }

    /**
     * Determine the controller class name based on current request and controller param
     *
     * @param \Cake\Http\ServerRequest $request The request to build a controller for.
     * @return class-string<\Cake\Controller\Controller>|null
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
            'controller' => $request->getParam('controller'),
            'plugin' => $request->getParam('plugin'),
            'prefix' => $request->getParam('prefix'),
            '_ext' => $request->getParam('_ext'),
        ]);
    }
}
