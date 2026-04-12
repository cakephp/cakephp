<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Definition;

use Cake\Container\Container;
use Cake\Container\Definition\Definition;
use Cake\Container\Definition\DefinitionAggregate;
use Cake\Container\Definition\DefinitionInterface;
use Cake\Container\Exception\NotFoundException;
use Cake\Test\TestCase\Container\Asset\Foo;
use Mockery;
use PHPUnit\Framework\TestCase;

class DefinitionAggregateTest extends TestCase
{
    public function testAggregateAddsDefinition(): void
    {
        $container = new Container();
        $definition = Mockery::mock(DefinitionInterface::class);

        $definition
            ->shouldReceive('setAlias')
            ->once()
            ->with('alias')
            ->andReturnSelf();

        $aggregate = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', $definition);

        self::assertInstanceOf(DefinitionInterface::class, $definition);
    }

    public function testAggregateCreatesDefinition(): void
    {
        $container = new Container();
        $aggregate = (new DefinitionAggregate())->setContainer($container);
        $definition = $aggregate->add('alias', Foo::class);
        self::assertSame('alias', $definition->getAlias());
    }

    public function testAggregateHasDefinition(): void
    {
        $container = new Container();
        $aggregate = (new DefinitionAggregate())->setContainer($container);
        $aggregate->add('alias', Foo::class);
        self::assertTrue($aggregate->has('alias'));
        self::assertFalse($aggregate->has('nope'));
    }

    public function testAggregateAddsAndIteratesMultipleDefinitions(): void
    {
        $container = new Container();
        $aggregate = (new DefinitionAggregate())->setContainer($container);

        $definitions = [];

        for ($i = 0; $i < 10; $i++) {
            $definitions[] = $aggregate->add('alias' . $i, Foo::class);
        }

        foreach ($aggregate->getIterator() as $key => $definition) {
            self::assertSame($definitions[$key], $definition);
        }
    }

    public function testAggregateIteratesAndResolvesDefinition(): void
    {
        $aggregate = new DefinitionAggregate();
        $definition1 = Mockery::mock(DefinitionInterface::class);
        $definition2 = Mockery::mock(DefinitionInterface::class);
        $container = new Container();

        $definition1
            ->shouldReceive('getAlias')
            ->once()
            ->andReturn('alias1');

        $definition1
            ->shouldReceive('setAlias')
            ->once()
            ->with('alias1')
            ->andReturnSelf();

        $definition2
            ->shouldReceive('getAlias')
            ->once()
            ->andReturn('alias2');

        $definition2
            ->shouldReceive('setContainer')
            ->once()
            ->with($container)
            ->andReturnSelf();

        $definition2
            ->shouldReceive('setShared')
            ->once()
            ->with(true)
            ->andReturnSelf();

        $definition2
            ->shouldReceive('setAlias')
            ->once()
            ->with('alias2')
            ->andReturnSelf();

        $definition2
            ->shouldReceive('resolve')
            ->once()
            ->andReturnSelf();

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $resolved = $aggregate->resolve('alias2');
        self::assertSame($definition2, $resolved);
    }

    public function testAggregateCanResolveArrayOfTaggedDefinitions(): void
    {
        $definition1 = Mockery::mock(DefinitionInterface::class);
        $definition2 = Mockery::mock(DefinitionInterface::class);
        $container = new Container();

        $definition1
            ->shouldReceive('setContainer')
            ->once()
            ->with($container)
            ->andReturnSelf();

        $definition1
            ->shouldReceive('hasTag')
            ->twice()
            ->with('tag')
            ->andReturn(true);

        $definition1
            ->shouldReceive('resolve')
            ->once()
            ->andReturn('definition1');

        $definition2
            ->shouldReceive('setContainer')
            ->once()
            ->with($container)
            ->andReturnSelf();

        $definition2
            ->shouldReceive('hasTag')
            ->once()
            ->with('tag')
            ->andReturn(true);

        $definition2
            ->shouldReceive('resolve')
            ->once()
            ->andReturn('definition2');

        $aggregate = new DefinitionAggregate([$definition1, $definition2]);

        $aggregate->setContainer($container);
        self::assertTrue($aggregate->hasTag('tag'));
        $resolved = $aggregate->resolveTagged('tag');
        self::assertSame(['definition1', 'definition2'], $resolved);
    }

    public function testAggregateThrowsExceptionWhenCannotResolve(): void
    {
        $this->expectException(NotFoundException::class);

        $aggregate = new DefinitionAggregate();
        $definition1 = Mockery::mock(DefinitionInterface::class);
        $definition2 = Mockery::mock(DefinitionInterface::class);
        $container = new Container();

        $definition1
            ->shouldReceive('getAlias')
            ->once()
            ->andReturn('alias1');

        $definition1
            ->shouldReceive('setAlias')
            ->once()
            ->with('alias1')
            ->andReturnSelf();

        $definition2
            ->shouldReceive('getAlias')
            ->once()
            ->andReturn('alias2');

        $definition2
            ->shouldReceive('setShared')
            ->once()
            ->with(true)
            ->andReturnSelf();

        $definition2
            ->shouldReceive('setAlias')
            ->once()
            ->with('alias2')
            ->andReturnSelf();

        $aggregate->setContainer($container);

        $aggregate->add('alias1', $definition1);
        $aggregate->addShared('alias2', $definition2);

        $aggregate->resolveNew('alias');
    }

    public function testDefinitionPrecedingSlash(): void
    {
        $container = new Container();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = '\\Cake\\Test\\TestCase\\Container\\Asset\\Foo';
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition(Foo::class);

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testGetPrecedingSlash(): void
    {
        $container = new Container();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = Foo::class;
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition('\\Cake\\Test\\TestCase\\Container\\Asset\\Foo');

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testDefinitionPrecedingSlashSingularQuotes(): void
    {
        $container = new Container();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = '\\Cake\\Test\\TestCase\\Container\\Asset\\Foo';
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition(Foo::class);

        self::assertInstanceOf(Definition::class, $definition);
    }

    public function testGetPrecedingSlashSingularQuote(): void
    {
        $container = new Container();
        $aggregate = new DefinitionAggregate();
        $aggregate->setContainer($container);

        $some_class = Foo::class;
        $aggregate->add($some_class, null);

        $definition = $aggregate->getDefinition('\\Cake\\Test\\TestCase\\Container\\Asset\\Foo');

        self::assertInstanceOf(Definition::class, $definition);
    }
}
