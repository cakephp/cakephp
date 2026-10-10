<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use Attribute;
use Cake\Container\Attribute\ContextualAttributeInterface;
use Cake\Container\ContainerAwareInterface;
use Cake\Container\ContainerAwareTrait;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Proves the container injected into the attribute is the parent
 * container (which holds explicit definitions), not the autowiring
 * delegate itself.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ContextualContainerAwareAttribute implements ContextualAttributeInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function resolve(ReflectionParameter $parameter, ContainerInterface $container): mixed
    {
        return $this->getContainer()->get(Bar::class);
    }
}
