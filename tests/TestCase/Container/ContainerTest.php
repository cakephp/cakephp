<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container;

use BadMethodCallException;
use Cake\Container\Container;
use Cake\Container\ContainerAwareTrait;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Exception\NotFoundException;
use Cake\Container\ReflectionContainer;
use Cake\Container\ServiceProvider\AbstractServiceProvider;
use Cake\Test\TestCase\Container\Asset\Bar;
use Cake\Test\TestCase\Container\Asset\BarInterface;
use Cake\Test\TestCase\Container\Asset\Foo;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainerAddsAndGets(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerAddsAndGetsRecursively(): void
    {
        $container = new Container();
        $container->add(Bar::class, Foo::class);
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));
        $foo = $container->get(Bar::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerAddsMultipleAndGets(): void
    {
        $container = new Container();
        $container->addDefinitions([
            Foo::class,
            Bar::class,
        ]);
        self::assertTrue($container->has(Foo::class));
        self::assertTrue($container->has(Bar::class));
        $foo = $container->get(Foo::class);
        $bar = $container->get(Bar::class);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $bar);
    }

    public function testContainerAddsMultipleWithArgsAndGets(): void
    {
        $container = new Container();
        $container->addDefinitions([
            Foo::class => [Bar::class],
            Bar::class,
        ]);
        self::assertTrue($container->has(Foo::class));
        self::assertTrue($container->has(Bar::class));
        $foo = $container->get(Foo::class);
        $bar = $container->get(Bar::class);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $bar);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testContainerAddsMultipleWithCustomNamesAndGets(): void
    {
        $container = new Container();
        $container->addDefinitions([
            'foo' => Foo::class,
            'bar' => Bar::class,
        ]);
        self::assertTrue($container->has('foo'));
        self::assertTrue($container->has('bar'));
        $foo = $container->get('foo');
        $bar = $container->get('bar');
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $bar);
    }

    public function testContainerAddsAndGetsShared(): void
    {
        $container = new Container();
        $container->addShared(Foo::class);
        self::assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $fooOne);
        self::assertInstanceOf(Foo::class, $fooTwo);
        self::assertSame($fooOne, $fooTwo);
    }

    public function testContainerAddsAndGetsSharedByDefault(): void
    {
        $container = (new Container())->defaultToShared();
        $container->add(Foo::class);
        self::assertTrue($container->has(Foo::class));

        $fooOne = $container->get(Foo::class);
        $fooTwo = $container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $fooOne);
        self::assertInstanceOf(Foo::class, $fooTwo);
        self::assertSame($fooOne, $fooTwo);
    }

    public function testContainerAddsAndGetsFromTag(): void
    {
        $container = new Container();
        $container->add(Foo::class)->addTag('foobar');
        $container->add(Bar::class)->addTag('foobar');
        self::assertTrue($container->has(Foo::class));

        $arrayOf = $container->get('foobar');

        self::assertTrue($container->has('foobar'));
        self::assertIsArray($arrayOf);
        self::assertCount(2, $arrayOf);
        self::assertInstanceOf(Foo::class, $arrayOf[0]);
        self::assertInstanceOf(Bar::class, $arrayOf[1]);
    }

    public function testContainerAddsAndGetsNewFromTag(): void
    {
        $container = new Container();
        $container->add(Foo::class)->addTag('foobar');
        $container->add(Bar::class)->addTag('foobar');
        self::assertTrue($container->has(Foo::class));

        $arrayOf = $container->get('foobar');

        self::assertTrue($container->has('foobar'));
        self::assertIsArray($arrayOf);
        self::assertCount(2, $arrayOf);
        self::assertInstanceOf(Foo::class, $arrayOf[0]);
        self::assertInstanceOf(Bar::class, $arrayOf[1]);

        $arrayOfTwo = $container->getNew('foobar');
        self::assertNotSame($arrayOfTwo, $arrayOf);
    }

    public function testContainerAddsAndGetsWithServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            public function provides(string $id): bool
            {
                return $id === Foo::class;
            }

            public function register(): void
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();

        $container->addServiceProvider($provider);
        self::assertTrue($container->has(Foo::class));

        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testThrowsWhenServiceProviderLies(): void
    {
        $liar = new class extends AbstractServiceProvider
        {
            public function provides(string $id): bool
            {
                return true;
            }

            public function register(): void
            {
            }
        };

        $container = new Container();

        $container->addServiceProvider($liar);
        self::assertTrue($container->has('lie'));

        $this->expectException(ContainerException::class);
        $container->get('lie');
    }

    public function testContainerAddsAndGetsFromDelegate(): void
    {
        $delegate = new ReflectionContainer();
        $container = new Container();
        $container->delegate($delegate);
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
    }

    public function testContainerThrowsWhenCannotGetService(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        $container->disableAutoWiring();
        self::assertFalse($container->has(Foo::class));
        $container->get(Foo::class);
    }

    public function testContainerCanExtendDefinition(): void
    {
        $container = new Container();
        $container->add(Foo::class);
        $definition = $container->extend(Foo::class);
        self::assertSame(Foo::class, $definition->getAlias());
        self::assertSame(Foo::class, $definition->getConcrete());
    }

    public function testContainerCanExtendDefinitionFromServiceProvider(): void
    {
        $provider = new class extends AbstractServiceProvider
        {
            public function provides(string $id): bool
            {
                return $id === Foo::class;
            }

            public function register(): void
            {
                $this->getContainer()->add(Foo::class);
            }
        };

        $container = new Container();
        $container->addServiceProvider($provider);
        $definition = $container->extend(Foo::class);
        self::assertSame(Foo::class, $definition->getAlias());
        self::assertSame(Foo::class, $definition->getConcrete());
    }

    public function testContainerThrowsWhenCannotGetDefinitionToExtend(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        $container->disableAutoWiring();
        self::assertFalse($container->has(Foo::class));
        $container->extend(Foo::class);
    }

    public function testContainerAddsAndInvokesInflector(): void
    {
        $container = new Container();
        $container->inflector(Foo::class)->setProperty('bar', Bar::class);
        $container->add(Foo::class);
        $container->add(Bar::class);
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testContainerAwareCannotBeUsedWithoutImplementingInterface(): void
    {
        $this->expectException(BadMethodCallException::class);

        $class = new class {
            use ContainerAwareTrait;
        };

        $container = new Container();
        $class->setContainer($container);
    }

    public function testNonExistentClassCausesException(): void
    {
        $container = new Container();
        $nonExistent = 'Cake\Test\TestCase\Container\NonExistent';
        $container->add($nonExistent);

        self::assertTrue($container->has($nonExistent));
        self::assertSame($nonExistent, $container->get($nonExistent));
    }

    /**
     * Test that named arguments work when all required arguments are provided.
     *
     * Note: Partial autowiring (where some args are named and others are auto-wired)
     * is not yet supported in Definition. For that use case, use Container::make().
     */
    public function testContainerResolvesWithNamedArgument(): void
    {
        $container = new Container();
        $container->add(Foo::class)
            ->addArgument(Bar::class, 'bar')
            ->addArgument('something', 'myString');
        self::assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
        self::assertSame('something', $foo->myString);
    }

    public function testContainerMakeWithArgs(): void
    {
        $container = new Container();
        $foo = $container->make(Foo::class, ['myString' => 'hello world']);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
        self::assertSame('hello world', $foo->myString);
    }

    public function testContainerMakeAlwaysReturnsNewInstance(): void
    {
        $container = new Container();
        $fooOne = $container->make(Foo::class);
        $fooTwo = $container->make(Foo::class);
        self::assertNotSame($fooOne, $fooTwo);
    }

    public function testInterfaceToImplementationUsesExistingDefinition(): void
    {
        $container = new Container();
        // Register Bar with specific configuration
        $container->addShared(Bar::class);

        // Map interface to the concrete class
        $container->add(BarInterface::class, Bar::class);

        // Get via interface should use the existing Bar definition
        $barFromInterface = $container->get(BarInterface::class);
        $barDirect = $container->get(Bar::class);

        self::assertInstanceOf(Bar::class, $barFromInterface);
        // Should be the same instance because Bar is shared
        self::assertSame($barDirect, $barFromInterface);
    }

    public function testHasDefinitionOnlyChecksExplicitDefinitions(): void
    {
        $container = new Container();
        $container->add(Foo::class);

        // hasDefinition should return true for explicitly added definitions
        self::assertTrue($container->hasDefinition(Foo::class));

        // hasDefinition should return false for autowirable classes not explicitly added
        self::assertFalse($container->hasDefinition(Bar::class));

        // but has() should return true because autowiring can resolve it
        self::assertTrue($container->has(Bar::class));
    }
}
