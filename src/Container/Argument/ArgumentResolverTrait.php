<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

use Cake\Container\Attribute\AttributeInterface;
use Cake\Container\Attribute\ContextualAttributeInterface;
use Cake\Container\ContainerAwareInterface;
use Cake\Container\ContainerInterface;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Exception\NotFoundException;
use Cake\Container\ReflectionContainer;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionAttribute;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

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
            if ($container instanceof PsrContainerInterface && $container->has($argValue)) {
                try {
                    $arg = $container->get($argValue);

                    if ($arg instanceof ArgumentInterface) {
                        $arg = $arg->getValue();
                    }

                    continue;
                } catch (NotFoundException) {
                    // @mago-expect lint:no-empty-catch-clause
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

            // next we see if we have an attribute that can resolve the argument
            foreach ($param->getAttributes() as $attribute) {
                $argument = $this->resolveContextualArgumentFromAttribute($attribute, $param);
                if ($argument === false) {
                    $argument = $this->resolveArgumentFromAttribute($attribute);
                }
                if ($argument !== false) {
                    $arguments[] = $argument;
                    continue 2;
                }
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
     * Attempt to resolve a parameter's value from a contextual PHP attribute.
     *
     * Contextual attributes receive the parameter being resolved alongside
     * the container, enabling resolution by parameter name/type. Attributes
     * not implementing {@see ContextualAttributeInterface} return false here
     * and fall through to {@see resolveArgumentFromAttribute()}.
     *
     * The container is deliberately passed as an argument instead of using
     * {@see ContainerAwareInterface}: with a standalone ReflectionContainer
     * there is no parent container to inject, while the resolving instance
     * itself always satisfies the PSR interface expected here.
     *
     * @param \ReflectionAttribute<object> $attribute The attribute to attempt to resolve.
     * @param \ReflectionParameter $parameter The parameter being resolved.
     * @return \Cake\Container\Argument\LiteralArgumentInterface|false
     */
    protected function resolveContextualArgumentFromAttribute(
        ReflectionAttribute $attribute,
        ReflectionParameter $parameter,
    ): LiteralArgumentInterface|false {
        if (!is_subclass_of($attribute->getName(), ContextualAttributeInterface::class)) {
            return false;
        }

        try {
            $container = $this->getContainer();
        } catch (ContainerException) {
            $container = $this instanceof PsrContainerInterface ? $this : throw new ContainerException(
                'No container implementation has been set.',
            );
        }

        /** @var \Cake\Container\Attribute\ContextualAttributeInterface $instance */
        $instance = $attribute->newInstance();

        return new LiteralArgument($instance->resolve($parameter, $container));
    }

    /**
     * Attempt to resolve a parameter's value from one of its PHP attributes.
     *
     * @param \ReflectionAttribute<object> $attribute The attribute to attempt to resolve.
     * @return \Cake\Container\Argument\LiteralArgumentInterface|false
     */
    protected function resolveArgumentFromAttribute(ReflectionAttribute $attribute): LiteralArgumentInterface|false
    {
        $attrClass = $attribute->getName();

        if (!is_subclass_of($attrClass, AttributeInterface::class)) {
            return false;
        }

        $instance = $attribute->newInstance();
        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->getContainer());
        }

        // purposely don't define a type here so that any typing errors
        // from the consuming code bubble up
        /** @var \Cake\Container\Attribute\AttributeInterface $instance */
        return new LiteralArgument($instance->resolve());
    }

    /**
     * @inheritDoc
     */
    abstract public function getContainer(): ContainerInterface;
}
