<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container;

use Cake\Container\Container;
use Cake\Container\Exception\NotFoundException;
use Cake\Container\ReflectionContainer;
use Cake\Test\TestCase\Container\Asset\Bar;
use Cake\Test\TestCase\Container\Asset\Foo;
use Cake\Test\TestCase\Container\Asset\FooCallable;
use Cake\Test\TestCase\Container\Asset\ProBar;
use Cake\Test\TestCase\Container\Asset\ProFoo;
use PHPUnit\Framework\TestCase;
use stdClass;

class ReflectionContainerTest extends TestCase
{
    private function getContainer(array $items = []): Container
    {
        $container = new Container();
        $container->disableAutoWiring();
        foreach ($items as $alias => $item) {
            $container->addShared($alias, $item);
        }

        return $container;
    }

    public function testHasReturnsTrueIfClassExists(): void
    {
        $container = new ReflectionContainer();
        self::assertTrue($container->has(ReflectionContainer::class));
    }

    public function testHasReturnsFalseIfClassDoesNotExist(): void
    {
        $container = new ReflectionContainer();
        self::assertFalse($container->has('blah'));
    }

    public function testContainerInstantiatesClassWithoutConstructor(): void
    {
        $classWithoutConstructor = stdClass::class;
        $container = new ReflectionContainer();
        self::assertInstanceOf($classWithoutConstructor, $container->get($classWithoutConstructor));
    }

    public function testContainerInstantiatesAndCachesClassWithoutConstructor(): void
    {
        $classWithoutConstructor = stdClass::class;
        $container = new ReflectionContainer(true);

        $classWithoutConstructorOne = $container->get($classWithoutConstructor);
        $classWithoutConstructorTwo = $container->get($classWithoutConstructor);

        self::assertInstanceOf($classWithoutConstructor, $classWithoutConstructorOne);
        self::assertInstanceOf($classWithoutConstructor, $classWithoutConstructorTwo);
        self::assertSame($classWithoutConstructorOne, $classWithoutConstructorTwo);
    }

    public function testGetInstantiatesClassWithConstructor(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass = Bar::class;

        $container = new ReflectionContainer();
        $item = $container->get($classWithConstructor);

        self::assertInstanceOf($classWithConstructor, $item);
        self::assertInstanceOf($dependencyClass, $item->bar);
    }

    public function testGetInstantiatesAndCachedClassWithConstructor(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass = Bar::class;

        $container = new ReflectionContainer(true);

        $itemOne = $container->get($classWithConstructor);
        $itemTwo = $container->get($classWithConstructor);

        self::assertInstanceOf($classWithConstructor, $itemOne);
        self::assertInstanceOf($dependencyClass, $itemOne->bar);

        self::assertInstanceOf($classWithConstructor, $itemTwo);
        self::assertInstanceOf($dependencyClass, $itemTwo->bar);

        self::assertSame($itemOne, $itemTwo);
        self::assertSame($itemOne->bar, $itemTwo->bar);
    }

    public function testGetInstantiatesClassWithConstructorAndUsesContainer(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass = Bar::class;

        $dependency = new $dependencyClass();
        $container = new ReflectionContainer();

        $container->setContainer($this->getContainer([
            $dependencyClass => $dependency,
        ]));

        $item = $container->get($classWithConstructor);

        self::assertInstanceOf($classWithConstructor, $item);
        self::assertSame($dependency, $item->bar);
    }

    public function testGetInstantiatesClassWithConstructorAndUsesArguments(): void
    {
        $classWithConstructor = Foo::class;
        $dependencyClass = Bar::class;

        $dependency = new $dependencyClass();
        $container = new ReflectionContainer();

        $item = $container->get($classWithConstructor, [
            'bar' => $dependency,
        ]);

        self::assertInstanceOf($classWithConstructor, $item);
        self::assertSame($dependency, $item->bar);
    }

    public function testThrowsWhenGettingNonExistentClass(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new ReflectionContainer();
        $container->get('Whoooo');
    }

    public function testCallReflectsOnClosureArguments(): void
    {
        $container = new ReflectionContainer();

        $foo = $container->call(function (Foo $foo) {
            return $foo;
        });

        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallReflectsOnInstanceMethodArguments(): void
    {
        $container = new ReflectionContainer();
        $foo = new Foo();
        $container->call($foo->setBar(...));
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallReflectsOnStaticMethodArguments(): void
    {
        $container = new ReflectionContainer();
        $container->call('Cake\Test\TestCase\Container\Asset\Foo::staticSetBar');
        self::assertInstanceOf(Bar::class, Asset\Foo::$staticBar);
        self::assertEquals('hello world', Asset\Foo::$staticHello);
    }

    public function testCallThrowsWhenArgumentCannotBeResolved(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new ReflectionContainer();
        $container->call([new Bar(), 'setSomething']);
    }

    public function testCallResolvesInvokableClass(): void
    {
        $container = new ReflectionContainer();
        $foo = $container->call(new FooCallable(), [new Bar()]);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallResolvesFunction(): void
    {
        $container = new ReflectionContainer();
        $foo = $container->call('\Cake\Test\TestCase\Container\Asset\test', [new Bar()]);
        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallThrowsOnUnknownString(): void
    {
        $container = new ReflectionContainer();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Callable (Unknown) is not a valid callable');
        $container->call('Unknown', [new Bar()]);
    }

    public function testGetInstantiatesClassWithConstructorAndSkipsProtectedConstructor(): void
    {
        $classWithConstructor = ProFoo::class;

        $container = new Container();
        $container->delegate(new ReflectionContainer());

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertNull($item->bar);
    }

    public function testGetInstantiatesClassWithConstructorAndUsesFactory(): void
    {
        $classWithConstructor = ProFoo::class;
        $dependencyClass = ProBar::class;

        $container = new Container();
        $container->delegate(new ReflectionContainer());

        $container->add($dependencyClass, [$dependencyClass, 'factory']);

        $item = $container->get($classWithConstructor);

        $this->assertInstanceOf($classWithConstructor, $item);
        $this->assertInstanceOf($dependencyClass, $item->bar);
    }
}
