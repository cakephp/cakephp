<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use Attribute;
use Cake\Container\Attribute\ContextualAttributeInterface;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Proves contextual resolution: combines attribute configuration
 * with the resolved parameter name (impossible with plain AttributeInterface).
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ContextualValueAttribute implements ContextualAttributeInterface
{
    public function __construct(protected string $value = 'resolved-value')
    {
    }

    public function resolve(ReflectionParameter $parameter, ContainerInterface $container): mixed
    {
        return $this->value . ':' . $parameter->getName();
    }
}
