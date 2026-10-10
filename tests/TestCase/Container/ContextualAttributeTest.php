<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container;

use Cake\Container\Container;
use Cake\Container\ReflectionContainer;
use Cake\Test\TestCase\Container\Asset\AttributeClient;
use Cake\Test\TestCase\Container\Asset\Bar;
use Cake\Test\TestCase\Container\Asset\ContextualService;
use Cake\Test\TestCase\Container\Asset\ContextualTypedService;
use Cake\Test\TestCase\Container\Asset\ContextualValueAttribute;
use PHPUnit\Framework\TestCase;

/**
 * Covers contextual attribute resolution (port of 22fe318 for 6.x,
 * reimplemented natively in ArgumentResolverTrait without league/container).
 */
class ContextualAttributeTest extends TestCase
{
    public function testGetResolvesScalarByParameterNameAndAutowiresDependency(): void
    {
        $container = new Container();
        $container->add(Bar::class);

        $service = $container->get(ContextualService::class);

        $this->assertInstanceOf(ContextualService::class, $service);
        // 'resolved-value' (attribute config) + ':' + 'injected' (parameter name) — needs ReflectionParameter.
        $this->assertSame('resolved-value:injected', $service->injected);
        $this->assertInstanceOf(Bar::class, $service->dependency);
    }

    public function testGetResolvesTypedParameterFromContainer(): void
    {
        $container = new Container();
        $bar = new Bar();
        $container->add(Bar::class, $bar);

        $service = $container->get(ContextualTypedService::class);

        $this->assertInstanceOf(ContextualTypedService::class, $service);
        $this->assertSame($bar, $service->bar);
    }

    public function testStandaloneReflectionContainerResolvesContextual(): void
    {
        $container = new ReflectionContainer();

        $service = $container->get(ContextualService::class);

        $this->assertSame('resolved-value:injected', $service->injected);
        $this->assertInstanceOf(Bar::class, $service->dependency);
    }

    public function testCallResolvesContextualParameter(): void
    {
        $container = new ReflectionContainer();

        $result = $container->call(function (#[ContextualValueAttribute('cb')] string $name) {
            return $name;
        });

        $this->assertSame('cb:name', $result);
    }

    public function testExplicitArgsWinOverContextualAttribute(): void
    {
        $container = new ReflectionContainer();

        $service = $container->get(ContextualService::class, ['injected' => 'explicit']);

        $this->assertSame('explicit', $service->injected);
    }

    public function testPlainAttributeInterfaceStillWorks(): void
    {
        $container = new ReflectionContainer();
        $item = $container->get(AttributeClient::class);

        $this->assertInstanceOf(AttributeClient::class, $item);
        $this->assertSame('RESOLVED-VALUE', $item->value);
    }

    public function testNonAttributeParameterFallsBackToAutowiring(): void
    {
        $container = new Container();
        $container->add(Bar::class);

        $service = $container->get(ContextualTypedService::class);

        $this->assertInstanceOf(Bar::class, $service->bar);
    }
}
