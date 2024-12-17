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

        $container = $this->getMockBuilder(Container::class)->getMock();
        $class->setContainer($container);
    }

    public function testNonExistentClassCausesException(): void
    {
        $container = new Container();
        $container->add(NonExistent::class);

        self::assertTrue($container->has(NonExistent::class));
        self::assertSame(NonExistent::class, $container->get(NonExistent::class));
    }
}
