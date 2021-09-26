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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;

class Invoker
{
    /**
     * @var \Cake\Core\ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @param \Cake\Core\ContainerInterface $container Containter instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Invokes callable with supplied arguments and loads missing
     * arguments from the container if the type is valid.
     *
     * @param \Closure $closure Function to invoke
     * @param array<mixed> $arguments Function arguments
     * @return mixed
     * @throws \InvalidArgumentException When unable to find argument for parameters.
     */
    public function invoke(Closure $closure, array $arguments): mixed
    {
        return $closure(...$this->resolveArguments($closure, $arguments));
    }

    /**
     * Invokes callable with supplied arguments and loads missing
     * arguments from the container if the type is valid.
     *
     * String arguments are coerced to parameter type for
     * float, int and bool types.
     *
     * @param \Closure $closure Function to invoke
     * @param array<mixed> $arguments Function arguments
     * @return mixed
     * @throws \InvalidArgumentException When unable to find argument for parameters.
     */
    public function invokeWithCoercion(Closure $closure, array $arguments)
    {
        return $closure(...$this->resolveArguments($closure, $arguments, ['coerce' => true]));
    }

    /**
     * Resolves passed in arguments to function parameters.
     *
     * Loads missing parameters from container if the type is valid.
     *
     * @param \Closure $closure Function to resolve
     * @param array<mixed> $arguments Function arguments
     * @param array<string, mixed> $options Resolution options
     */
    public function resolveArguments(Closure $closure, array $arguments, array $options = []): array
    {
        $resolved = [];
        $function = new ReflectionFunction($closure);
        foreach ($function->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType ? ltrim($type->getName(), '?') : null;

            if (array_key_exists($name, $arguments)) {
                $argument = $arguments[$name];
                unset($arguments[$name]);

                if (!empty($options['coerce']) && is_string($argument)) {
                    $resolved[] = $this->coercePrimitiveType($argument, $typeName);
                } else {
                    $resolved[] = $argument;
                }
                continue;
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                if ($this->container->has($typeName)) {
                    $resolved[] = $this->container->get($typeName);
                    continue;
                }
            }

            if ($arguments) {
                $argument = array_shift($arguments);
                if (!empty($options['coerce']) && is_string($argument)) {
                    $resolved[] = $this->coercePrimitiveType($argument, $typeName);
                } else {
                    $resolved[] = $argument;
                }
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->isVariadic()) {
                continue;
            }

            throw new InvalidArgumentException("Unable to find argument for parameter `$name`.");
        }

        return array_merge($resolved, $arguments);
    }

    /**
     * Coerces string argument to primitive type.
     *
     * @param string $argument Argument to coerce
     * @param string|null $typeName Parameter type name or null for no type
     * @return string|float|int|bool
     */
    protected function coercePrimitiveType(string $argument, ?string $typeName): string|float|int|bool
    {
        if ($typeName === null) {
            return $argument;
        }

        switch ($typeName) {
            case 'string':
                return $argument;
        }

        throw new InvalidArgumentException("Coercing argument to `$typeName` is not supported.");
    }
}
