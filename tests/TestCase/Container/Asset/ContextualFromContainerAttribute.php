<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use Attribute;
use Cake\Container\Attribute\ContextualAttributeInterface;
use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Proves container access: resolves the parameter type from the container.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ContextualFromContainerAttribute implements ContextualAttributeInterface
{
    public function resolve(ReflectionParameter $parameter, ContainerInterface $container): mixed
    {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $container->get($type->getName());
        }

        return $container->get($parameter->getName());
    }
}
