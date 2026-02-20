<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Definition;

use Cake\Container\Argument\Literal;
use Cake\Container\Argument\ResolvableArgument;
use Cake\Container\Container;
use Cake\Container\Definition\Definition;
use Cake\Test\TestCase\Container\Asset\Bar;
use Cake\Test\TestCase\Container\Asset\Foo;
use Cake\Test\TestCase\Container\Asset\FooCallable;
use Error;
use PHPUnit\Framework\TestCase;

class DefinitionTest extends TestCase
{
    public function testDefinitionResolvesClosureWithDefinedArgs(): void
    {
        $definition = new Definition('callable', function (...$args) {
            return implode(' ', $args);
        });

        $definition->addArguments(['hello', 'world']);
        $actual = $definition->resolve();
        self::assertSame('hello world', $actual);
    }

    public function testDefinitionResolvesClosureReturningRawArgument(): void
    {
        $definition = new Definition('callable', function () {
            return new Literal\StringArgument('hello world');
        });

        $actual = $definition->resolve();
        self::assertSame('hello world', $actual);
    }

    public function testDefinitionResolvesCallableClass(): void
    {
        $definition = new Definition('callable', new FooCallable());
        $definition->addArgument(new Bar());
        $actual = $definition->resolve();
        self::assertInstanceOf(Foo::class, $actual);
    }

    public function testDefinitionResolvesArrayCallable(): void
    {
        $definition = new Definition('callable', [new FooCallable(), '__invoke']);
        $definition->addArgument(new Bar());
        $actual = $definition->resolve();
        self::assertInstanceOf(Foo::class, $actual);
    }

    public function testDefinitionAddsArgumentWithName(): void
    {
        $definition = new Definition('class', Foo::class);
        $definition->addArgument('something', 'myString');
        $actual = $definition->resolve();
        self::assertInstanceOf(Foo::class, $actual);
        self::assertSame('something', $actual->myString);
    }

    public function testDefinitionAddsArgumentWithUnknownNameThrows(): void
    {
        $definition = new Definition('class', Foo::class);
        $definition->addArgument('something', 'unknown');
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Unknown named parameter $unknown');
        $definition->resolve();
    }

    public function testDefinitionResolvesClassWithMethodCalls(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->expects(self::once())->method('has')->with(self::equalTo(Bar::class))->willReturn(true);
        $container->expects(self::once())->method('get')->with(self::equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addMethodCalls(['setBar' => [Bar::class]]);

        $actual = $definition->resolve();
        self::assertInstanceOf(Foo::class, $actual);
        self::assertInstanceOf(Bar::class, $actual->bar);
    }

    public function testDefinitionResolvesClassWithDefinedArgs(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $bar = new Bar();

        $container->expects(self::once())->method('has')->with(self::equalTo(Bar::class))->willReturn(true);
        $container->expects(self::once())->method('get')->with(self::equalTo(Bar::class))->willReturn($bar);

        $definition = new Definition('callable', Foo::class);

        $definition->setContainer($container);
        $definition->addArgument(Bar::class);

        $actual = $definition->resolve();
        self::assertInstanceOf(Foo::class, $actual);
        self::assertInstanceOf(Bar::class, $actual->bar);
    }

    public function testDefinitionResolvesSharedItemOnlyOnce(): void
    {
        $definition = new Definition('class', Foo::class);
        $definition->setShared(true);
        $actual1 = $definition->resolve();
        $actual2 = $definition->resolve();
        $actual3 = $definition->resolveNew();
        self::assertSame($actual1, $actual2);
        self::assertNotSame($actual1, $actual3);
    }

    public function testDefinitionResolvesNestedAlias(): void
    {
        $aliasDefinition = new Definition('alias', new ResolvableArgument('class'));
        $definition = new Definition('class', Foo::class);
        $container = $this->getMockBuilder(Container::class)->getMock();

        $expected = $definition->resolve();

        $container->expects(self::once())->method('has')->with(self::equalTo('class'))->willReturn(true);
        $container->expects(self::once())->method('get')->with(self::equalTo('class'))->willReturn($expected);

        $aliasDefinition->setContainer($container);
        $actual = $aliasDefinition->resolve();
        self::assertSame($expected, $actual);
    }

    public function testDefinitionCanAddTags(): void
    {
        $definition = new Definition('class', Foo::class);
        $definition->addTag('tag1')->addTag('tag2');
        self::assertTrue($definition->hasTag('tag1'));
        self::assertTrue($definition->hasTag('tag2'));
        self::assertFalse($definition->hasTag('tag3'));
    }

    public function testDefinitionCanGetConcrete(): void
    {
        $concrete = new Literal\StringArgument(Foo::class);
        $definition = new Definition('class', $concrete);
        self::assertSame($concrete, $definition->getConcrete());
    }

    public function testDefinitionCanSetConcrete(): void
    {
        $definition = new Definition('class');
        $concrete = new Literal\StringArgument(Foo::class);
        $definition->setConcrete($concrete);
        self::assertSame($concrete, $definition->getConcrete());
    }
}
