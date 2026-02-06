<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

use Cake\Container\DefinitionContainerInterface;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Exception\NotFoundException;
use Cake\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

trait ArgumentResolverTrait
{
    /**
     * @inheritDoc
     */
    public function resolveArguments(array $arguments): array
    {
        try {
            $container = $this->getContainer();
        } catch (ContainerException) {
            $container = $this instanceof ReflectionContainer ? $this : null;
        }

        foreach ($arguments as &$arg) {
            // if we have a literal, we don't want to do anything more with it
            if ($arg instanceof LiteralArgumentInterface) {
                $arg = $arg->getValue();
                continue;
            }

            if ($arg instanceof ArgumentInterface) {
                $argValue = $arg->getValue();
            } else {
                $argValue = $arg;
            }

            if (!is_string($argValue)) {
                 continue;
            }

            // resolve the argument from the container, if it happens to be another
            // argument wrapper, use that value
            if ($container instanceof ContainerInterface && $container->has($argValue)) {
                try {
                    $arg = $container->get($argValue);

                    if ($arg instanceof ArgumentInterface) {
                        $arg = $arg->getValue();
                    }

                    continue;
                } catch (NotFoundException) {
                }
            }

            // if we have a default value, we use that, no more resolution as
            // we expect a default/optional argument value to be literal
            if ($arg instanceof DefaultValueInterface) {
                $arg = $arg->getDefaultValue();
            }
        }

        return $arguments;
    }

    /**
     * @inheritDoc
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array
    {
        $params = $method->getParameters();
        $arguments = [];

        foreach ($params as $param) {
            $name = $param->getName();

            // if we've been given a value for the argument, treat as literal
            if (array_key_exists($name, $args)) {
                $arguments[] = new LiteralArgument($args[$name]);
                continue;
            }

            $type = $param->getType();

            if ($type instanceof ReflectionNamedType) {
                // in PHP 8, nullable arguments have "?" prefix
                $typeHint = ltrim($type->getName(), '?');

                if ($param->isDefaultValueAvailable()) {
                    $arguments[] = new DefaultValueArgument($typeHint, $param->getDefaultValue());
                    continue;
                }

                $arguments[] = new ResolvableArgument($typeHint);
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $arguments[] = new LiteralArgument($param->getDefaultValue());
                continue;
            }

            throw new NotFoundException(sprintf(
                'Unable to resolve a value for parameter (%s) in the function/method (%s)',
                $name,
                $method->getName(),
            ));
        }

        return $this->resolveArguments($arguments);
    }

    /**
     * @inheritDoc
     */
    abstract public function getContainer(): DefinitionContainerInterface;
}
