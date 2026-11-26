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
 * the container, enabling resolution by parameter name/type, pulling
 * dependencies from the container, or combining configuration with the
 * parameter default as fallback:
 *
 * ```
 * #[Attribute(Attribute::TARGET_PARAMETER)]
 * final class ConfigValue implements ContextualAttributeInterface
 * {
 *     public function __construct(private string $key) {}
 *
 *     public function resolve(ReflectionParameter $parameter, ContainerInterface $container): mixed
 *     {
 *         return Configure::read($this->key)
 *             ?? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
 *     }
 * }
 * ```
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
