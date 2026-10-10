<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container;

use Cake\Container\Argument\ArgumentResolverInterface;
use Cake\Container\Argument\ArgumentResolverTrait;
use Cake\Container\Container;
use Cake\Container\ContainerAwareTrait;
use Cake\Container\ReflectionContainer;
use Cake\Test\TestCase\Container\Asset\AttributeClient;
use Cake\Test\TestCase\Container\Asset\Bar;
use Cake\Test\TestCase\Container\Asset\ContainerAwareValueService;
use Cake\Test\TestCase\Container\Asset\ContextualAwareService;
use Cake\Test\TestCase\Container\Asset\ContextualService;
use Cake\Test\TestCase\Container\Asset\ContextualTypedService;
use Cake\Test\TestCase\Container\Asset\ContextualValueAttribute;
use Cake\Test\TestCase\Container\Asset\PlainMarkerService;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionParameter;

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

    public function testGetInjectsParentContainerIntoContainerAwareAttribute(): void
    {
        $container = new Container();
        $bar = new Bar();
        $container->add(Bar::class, $bar);

        $service = $container->get(ContextualAwareService::class);

        $this->assertInstanceOf(ContextualAwareService::class, $service);
        // Resolved through the parent container (explicit definition),
        // not through the autowiring delegate itself.
        $this->assertSame($bar, $service->bar);
    }

    public function testPlainAttributeInterfaceStillWorks(): void
    {
        $container = new ReflectionContainer();
        $item = $container->get(AttributeClient::class);

        $this->assertInstanceOf(AttributeClient::class, $item);
        $this->assertSame('RESOLVED-VALUE', $item->value);
    }

    public function testNonContainerAttributeFallsThroughToAutowiring(): void
    {
        $container = new Container();
        $container->add(Bar::class);

        // #[SensitiveParameter] implements neither container interface,
        // so the parameter resolves by type as if untagged.
        $service = $container->get(PlainMarkerService::class);

        $this->assertInstanceOf(PlainMarkerService::class, $service);
        $this->assertInstanceOf(Bar::class, $service->bar);
    }

    public function testPlainContainerAwareAttributeReceivesParentContainer(): void
    {
        $container = new Container();
        $bar = new Bar();
        $container->add(Bar::class, $bar);

        $service = $container->get(ContainerAwareValueService::class);

        $this->assertInstanceOf(ContainerAwareValueService::class, $service);
        $this->assertSame($bar, $service->bar);
    }

    public function testResolveArgumentFromAttributeWithNullParameterReturnsFalse(): void
    {
        $resolver = new class implements ArgumentResolverInterface {
            use ArgumentResolverTrait;
            use ContainerAwareTrait;

            public function expose(ReflectionAttribute $attribute): mixed
            {
                return $this->resolveArgumentFromAttribute($attribute);
            }
        };

        $param = new ReflectionParameter(
            function (#[ContextualValueAttribute] string $value): void {
            },
            'value',
        );

        // BC guard: external callers/subclasses invoking without a parameter.
        $this->assertFalse($resolver->expose($param->getAttributes()[0]));
    }

    public function testNonAttributeParameterFallsBackToAutowiring(): void
    {
        $container = new Container();
        $container->add(Bar::class);

        $service = $container->get(ContextualTypedService::class);

        $this->assertInstanceOf(Bar::class, $service->bar);
    }
}
