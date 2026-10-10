<?php
declare(strict_types=1);

namespace Cake\Container\Attribute;

use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Interface for contextual PHP attributes that resolve an argument
 * with access to the reflection parameter and the container.
 *
 * Unlike {@see AttributeInterface::resolve()}, which is parameter-blind,
 * this receives the parameter being resolved (name, type, default) and
 * the container, enabling Laravel-style contextual resolution:
 * resolve by parameter name/type, pull dependencies from the container,
 * or fall back to attribute constructor configuration.
 */
interface ContextualAttributeInterface
{
    /**
     * Resolve the value for the given parameter.
     *
     * @param \ReflectionParameter $parameter The parameter being resolved.
     * @param \Psr\Container\ContainerInterface $container The container (parent container when available).
     * @return mixed
     */
    public function resolve(ReflectionParameter $parameter, ContainerInterface $container): mixed;
}
